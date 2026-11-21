<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\UnitKerja;
use App\Models\FormasiJabatan;
use App\Models\AsnMaster;
use App\Models\User;
use App\Services\HaversineService;

class ClientRealDataSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('data/parsed_client_data.json');
        if (!file_exists($jsonPath)) {
            $this->command->error("File parsed_client_data.json tidak ditemukan!");
            return;
        }

        $this->command->info("Membaca file parsed_client_data.json...");
        $data = json_decode(file_get_contents($jsonPath), true);

        // 1. IMPORT UNIT KERJA
        $this->command->info("Mengimpor Unit Kerja (" . count($data['units']) . " unit)...");
        
        $unitMap = []; // nama_unit (uppercase) => id
        $batchUnits = [];
        $now = now();
        
        foreach ($data['units'] as $idx => $u) {
            $nama = trim($u['nama_unit']);
            $key = strtoupper($nama);
            $kode = 'UK-' . str_pad($idx + 1, 4, '0', STR_PAD_LEFT);

            $existing = DB::table('unit_kerja')->where('nama_unit', $nama)->first();
            if ($existing) {
                DB::table('unit_kerja')->where('id', $existing->id)->update([
                    'kategori' => $u['kategori'],
                    'kantor_lat' => $u['kantor_lat'],
                    'kantor_lng' => $u['kantor_lng'],
                    'updated_at' => $now,
                ]);
                $unitMap[$key] = $existing->id;
            } else {
                $id = DB::table('unit_kerja')->insertGetId([
                    'kode_unit' => $kode,
                    'nama_unit' => $nama,
                    'kategori' => $u['kategori'],
                    'kantor_lat' => $u['kantor_lat'],
                    'kantor_lng' => $u['kantor_lng'],
                    'alamat_kantor' => 'Kabupaten Indramayu, Jawa Barat',
                    'kuota_formasi_kosong' => 0,
                    'bezetting_saat_ini' => 0,
                    'kebutuhan_abk' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $unitMap[$key] = $id;
            }
        }
        $this->command->info("Unit Kerja selesai diimpor.");

        // Helper fallback unit (BKPSDM)
        $defaultUnitId = $unitMap['BADAN KEPEGAWAIAN DAN PENGEMBANGAN SUMBER DAYA MANUSIA'] ?? array_values($unitMap)[0];

        // 2. IMPORT FORMASI JABATAN & REKAP ABK UNIT
        $this->command->info("Mengimpor Formasi Jabatan (" . count($data['formasi']) . " formasi)...");
        
        // Bersihkan data formasi lama agar tidak duplikat
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('formasi_jabatan')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $formasiChunks = array_chunk($data['formasi'], 500);
        $unitStats = []; // unit_id => ['bezetting' => x, 'abk' => y, 'lowong' => z]

        foreach ($formasiChunks as $chunk) {
            $insertRows = [];
            foreach ($chunk as $f) {
                $unitName = strtoupper(trim($f['unit_org']));
                // Cari ID unit kerja yang paling cocok
                $unitId = $unitMap[$unitName] ?? null;
                if (!$unitId) {
                    // Cari parsial match
                    foreach ($unitMap as $uName => $uId) {
                        if (str_contains($unitName, $uName) || str_contains($uName, $unitName)) {
                            $unitId = $uId;
                            break;
                        }
                    }
                }
                if (!$unitId) {
                    $unitId = $defaultUnitId;
                }

                if (!isset($unitStats[$unitId])) {
                    $unitStats[$unitId] = ['bezetting' => 0, 'abk' => 0, 'lowong' => 0];
                }
                $unitStats[$unitId]['bezetting'] += $f['bezetting_riil'];
                $unitStats[$unitId]['abk'] += $f['kebutuhan_abk'];
                $unitStats[$unitId]['lowong'] += $f['kuota_lowong'];

                $insertRows[] = [
                    'id_unit_kerja' => $unitId,
                    'tahun_anggaran' => 2026,
                    'periode' => 'Reguler',
                    'nama_jabatan' => $f['nama_jabatan'],
                    'jenis_jabatan' => $f['jenis_jabatan'],
                    'jenjang_jabatan' => $f['jenis_jabatan'] === 'fungsional' ? 'Ahli Pertama/Muda' : 'Pelaksana',
                    'kualifikasi_pendidikan' => 'Kualifikasi linear sesuai rumpun urusan',
                    'kebutuhan_abk' => $f['kebutuhan_abk'],
                    'bezetting_riil' => $f['bezetting_riil'],
                    'kuota_lowong' => $f['kuota_lowong'],
                    'status' => $f['status'],
                    'keterangan' => 'Selisih ABK: ' . $f['selisih'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('formasi_jabatan')->insert($insertRows);
        }

        // Update akumulasi ABK, Bezetting, dan Kuota Lowong pada masing-masing unit kerja
        $this->command->info("Memperbarui rekap akumulasi ABK pada Unit Kerja...");
        foreach ($unitStats as $uId => $stat) {
            DB::table('unit_kerja')->where('id', $uId)->update([
                'bezetting_saat_ini' => $stat['bezetting'],
                'kebutuhan_abk' => $stat['abk'],
                'kuota_formasi_kosong' => $stat['lowong'],
                'updated_at' => $now,
            ]);
        }

        // 3. IMPORT DATA PEGAWAI PNS (500 PEGAWAI)
        $this->command->info("Mengimpor Data Pegawai PNS (" . count($data['pns']) . " pegawai)...");
        
        $haversine = new HaversineService();
        $defaultPassword = Hash::make('password');

        // Ambil koordinat unit map untuk hitung jarak
        $unitCoords = DB::table('unit_kerja')->pluck('kantor_lat', 'id')->toArray();
        $unitLngs = DB::table('unit_kerja')->pluck('kantor_lng', 'id')->toArray();

        foreach ($data['pns'] as $p) {
            $nip = trim($p['nip']);
            $nama = trim($p['nama']);

            // Match unit
            $targetUnitName = strtoupper(trim($p['unit_unor'] ?: $p['unit_induk']));
            $assignedUnitId = $unitMap[$targetUnitName] ?? null;
            if (!$assignedUnitId) {
                foreach ($unitMap as $uName => $uId) {
                    if (str_contains($targetUnitName, $uName) || str_contains($uName, $targetUnitName)) {
                        $assignedUnitId = $uId;
                        break;
                    }
                }
            }
            if (!$assignedUnitId) {
                $assignedUnitId = $defaultUnitId;
            }

            // Hitung jarak domisili ke kantor
            $uLat = $unitCoords[$assignedUnitId] ?? DEFAULT_LAT;
            $uLng = $unitLngs[$assignedUnitId] ?? DEFAULT_LNG;
            $dLat = $p['domisili_lat'];
            $dLng = $p['domisili_lng'];

            $jarakKm = $haversine->calculateDistance($dLat, $dLng, $uLat, $uLng);
            $zona = 'dekat';
            if ($jarakKm > 20.0) {
                $zona = 'jauh';
            } elseif ($jarakKm >= 5.0) {
                $zona = 'sedang';
            }

            // Simpan / update ke asn_master
            $asnData = [
                'nip' => $nip,
                'nama' => $nama,
                'jabatan' => $p['jabatan'],
                'jenis_jabatan' => $p['jenis_jabatan'],
                'pangkat_golongan' => $p['pangkat_golongan'],
                'tmt_golongan' => $p['tmt_golongan'],
                'masa_kerja_tahun' => $p['masa_kerja_tahun'],
                'nilai_skp' => 88.50,
                'tanggungan_keluarga' => 2,
                'tmt_kerja_unit' => $p['tgl_jabatan'] ?: $p['tgl_cpns'],
                'tgl_jabatan' => $p['tgl_jabatan'],
                'tgl_cpns' => $p['tgl_cpns'],
                'tgl_pns' => $p['tgl_pns'],
                'tingkat_pendidikan' => $p['tingkat_pendidikan'],
                'pendidikan_terakhir' => $p['pendidikan_terakhir'],
                'id_unit_saat_ini' => $assignedUnitId,
                'unit_kerja_induk' => $p['unit_induk'],
                'domisili_lat' => $dLat,
                'domisili_lng' => $dLng,
                'provinsi' => 'Jawa Barat',
                'kabupaten_kota' => 'Kabupaten Indramayu',
                'alamat_detail' => $p['alamat_detail'],
                'jarak_ke_kantor_km' => $jarakKm,
                'zona_jarak' => $zona,
                'status_permohonan_mutasi' => false,
                'updated_at' => $now,
            ];

            $existingAsn = DB::table('asn_master')->where('nip', $nip)->first();
            if ($existingAsn) {
                DB::table('asn_master')->where('id', $existingAsn->id)->update($asnData);
                $asnId = $existingAsn->id;
            } else {
                $asnData['created_at'] = $now;
                $asnId = DB::table('asn_master')->insertGetId($asnData);
            }

            // Buat akun user login otomatis untuk ASN
            $userExisting = DB::table('users')->where('nip', $nip)->first();
            if (!$userExisting) {
                // Email format: nip@bkpsdm.indramayukab.go.id
                $email = $nip . '@bkpsdm.indramayukab.go.id';
                DB::table('users')->insert([
                    'name' => $nama,
                    'email' => $email,
                    'password' => $defaultPassword,
                    'role' => 'asn',
                    'nip' => $nip,
                    'asn_id' => $asnId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $this->command->info("Impor data riil klien SELESAI!");
        $this->command->info("- Unit Kerja: " . count($unitMap));
        $this->command->info("- Formasi Jabatan: " . count($data['formasi']));
        $this->command->info("- Pegawai ASN: " . count($data['pns']));
    }
}
