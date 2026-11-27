<?php

namespace Database\Seeders;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\MasterJabatan;
use App\Models\MasterLokasi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\HaversineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder PnsFixSampleSeeder
 *
 * Memasukkan data sampel riil Kabupaten Indramayu dari file Excel:
 * - `RECORD_DATA_PNS_-_NOVEMBER_2026_000_500_ORANG-2.xlsx` (Sheet `DATA PNS FIX` & `DATA UNIT KERJA FIX`)
 * - `jabatan_fungsional.xlsx` & `jabatan_pelaksana.xlsx` (Referensi SIASN BKN)
 *
 * Seluruh 165 PNS, Formasi Jabatan 2026, Master Jabatan SIASN, dan 31 Unit Kerja
 * dihubungkan secara utuh melalui Foreign Key (`id_master_jabatan`, `id_formasi_jabatan`, `id_unit_saat_ini`).
 */
class PnsFixSampleSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/pns_fix_sample.json');
        if (!File::exists($jsonPath)) {
            $this->command?->error("File fixture tidak ditemukan: {$jsonPath}");
            return;
        }

        $payload = json_decode(File::get($jsonPath), true);
        if (!is_array($payload)) {
            $this->command?->error('Format JSON pns_fix_sample.json tidak valid.');
            return;
        }

        $haversine = new HaversineService();

        // Bersihkan data lama (rekomendasi, asn, formasi, dan unit_kerja) agar hanya 31 unit sampel FIX yang tampil bersih di Peta GIS
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('rekomendasi_mutasi')->truncate();
        DB::table('asn_master')->truncate();
        DB::table('formasi_jabatan')->truncate();
        DB::table('unit_kerja')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // =========================================================================
        // 1. SINKRONKAN MASTER JABATAN SIASN (40 JABATAN RIIL DARI DATA PNS FIX)
        // =========================================================================
        $masterByKey = [];
        $nextJf = MasterJabatan::where('jenis_jabatan', 'fungsional')->count();
        $nextJp = MasterJabatan::where('jenis_jabatan', 'pelaksana')->count();

        foreach ($payload['master_jabatan'] as $item) {
            $key = strtolower(trim($item['nama_jabatan']));
            $existing = MasterJabatan::where('siasn_id', $item['siasn_id'])
                ->orWhereRaw('LOWER(TRIM(nama_jabatan)) = ?', [$key])
                ->first();

            if ($existing) {
                $existing->update([
                    'siasn_id' => $item['siasn_id'] ?? $existing->siasn_id,
                    'kode_cepat' => $item['kode_cepat'] ?? $existing->kode_cepat,
                    'jenis_jabatan' => $item['jenis_jabatan'],
                    'jenjang_jabatan' => $item['jenjang_jabatan'] ?? $existing->jenjang_jabatan,
                    'kelas_jabatan' => $item['kelas_jabatan'] ?? $existing->kelas_jabatan,
                    'bup_usia' => $item['bup_usia'] ?? $existing->bup_usia,
                    'rumpun_jabatan' => $item['rumpun_jabatan'] ?? $existing->rumpun_jabatan,
                ]);
                $masterByKey[$key] = $existing;
            } else {
                $kode = $item['jenis_jabatan'] === 'fungsional'
                    ? sprintf('JF-%04d', ++$nextJf)
                    : sprintf('JP-%04d', ++$nextJp);

                $created = MasterJabatan::create([
                    'kode_jabatan' => $kode,
                    'siasn_id' => $item['siasn_id'] ?? null,
                    'kode_cepat' => $item['kode_cepat'] ?? null,
                    'nama_jabatan' => $item['nama_jabatan'],
                    'jenis_jabatan' => $item['jenis_jabatan'],
                    'jenjang_jabatan' => $item['jenjang_jabatan'] ?? null,
                    'kelas_jabatan' => $item['kelas_jabatan'] ?? null,
                    'bup_usia' => $item['bup_usia'] ?? 58,
                    'rumpun_jabatan' => $item['rumpun_jabatan'] ?? null,
                ]);
                $masterByKey[$key] = $created;
            }
        }

        // =========================================================================
        // 2. SINKRONKAN 31 UNIT KERJA FIX DENGAN TABEL unit_kerja
        // =========================================================================
        $unitByKey = [];
        $unitSeq = UnitKerja::count();

        foreach ($payload['unit_kerja_fix'] as $u) {
            $key = strtolower(trim($u['nama_unit']));
            $unit = UnitKerja::whereRaw('LOWER(TRIM(nama_unit)) = ?', [$key])->first();

            if ($unit) {
                if (!empty($u['kantor_lat']) && !empty($u['kantor_lng'])) {
                    $unit->update([
                        'kantor_lat' => $u['kantor_lat'],
                        'kantor_lng' => $u['kantor_lng'],
                    ]);
                }
            } else {
                $kategori = 'Dinas';
                if (str_contains($key, 'badan')) {
                    $kategori = 'Badan';
                } elseif (str_contains($key, 'kecamatan')) {
                    $kategori = 'Kecamatan';
                } elseif (str_contains($key, 'uptd') || str_contains($key, 'puskesmas') || str_contains($key, 'sd negeri') || str_contains($key, 'smp negeri')) {
                    $kategori = 'UPTD';
                }

                $unit = UnitKerja::create([
                    'kode_unit' => sprintf('UKF-%03d', ++$unitSeq),
                    'nama_unit' => $u['nama_unit'],
                    'kategori' => $kategori,
                    'kantor_lat' => $u['kantor_lat'] ?? -6.3275,
                    'kantor_lng' => $u['kantor_lng'] ?? 108.3224,
                    'alamat_kantor' => $u['nama_unit'] . ', Kabupaten Indramayu',
                    'kuota_formasi_kosong' => 0,
                    'bezetting_saat_ini' => 0,
                    'kebutuhan_abk' => 0,
                ]);
            }

            $unitByKey[$key] = $unit;
        }

        // =========================================================================
        // 3. BANGUN FORMASI JABATAN 2026 PER (UNIT KERJA x MASTER JABATAN)
        // =========================================================================
        $pairCounts = [];
        $pairPendidikan = [];
        foreach ($payload['pns_fix'] as $p) {
            $uKey = strtolower(trim($p['unit_kerja_canonical'] ?? $p['unit_induk']));
            $jKey = $p['jabatan_key'];
            $pairKey = $uKey . '|' . $jKey;
            $pairCounts[$pairKey] = ($pairCounts[$pairKey] ?? 0) + 1;
            if (!isset($pairPendidikan[$pairKey]) && !empty($p['pendidikan_terakhir'])) {
                $pairPendidikan[$pairKey] = $p['pendidikan_terakhir'];
            }
        }

        $formasiByPair = [];
        $idxPair = 0;
        foreach ($pairCounts as $pairKey => $bezetting) {
            [$uKey, $jKey] = explode('|', $pairKey, 2);
            $unit = $unitByKey[$uKey] ?? null;
            $master = $masterByKey[$jKey] ?? null;
            if (!$unit || !$master) {
                continue;
            }

            $idxPair++;
            // Sekitar 20% formasi berstatus penuh/terisi sebagai kontrol anti-overstaffing, 80% terbuka dengan kuota 1-2
            $isPenuh = ($idxPair % 5 === 0);
            $lowong = $isPenuh ? 0 : (($bezetting >= 3) ? 2 : 1);
            $abk = $bezetting + $lowong;

            $formasi = FormasiJabatan::create([
                'id_unit_kerja' => $unit->id,
                'id_master_jabatan' => $master->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => $master->nama_jabatan,
                'jenis_jabatan' => $master->jenis_jabatan,
                'jenjang_jabatan' => $master->jenjang_jabatan,
                'kualifikasi_pendidikan' => $pairPendidikan[$pairKey] ?? $master->kualifikasi_pendidikan,
                'kebutuhan_abk' => $abk,
                'bezetting_riil' => $bezetting,
                'kuota_lowong' => $lowong,
                'status' => $isPenuh ? 'terisi' : 'terbuka',
                'keterangan' => $isPenuh
                    ? 'Formasi ABK 2026 telah terisi penuh (kontrol validasi anti-overstaffing).'
                    : "Alokasi formasi terbuka ABK 2026 ({$lowong} kuota lowong) berdasarkan proyeksi kebutuhan unit.",
            ]);

            $formasiByPair[$pairKey] = $formasi;
        }

        foreach ($unitByKey as $unit) {
            $unit->syncKuotaFormasi(2026);
        }

        // =========================================================================
        // 4. PETA KOORDINAT KECAMATAN (MASTER_LOKASI + DAERAH PENYANGGA)
        // =========================================================================
        $lokasiMap = [];
        foreach (MasterLokasi::all() as $lok) {
            $lokasiMap[strtolower(trim($lok->nama_kecamatan))] = [
                'kec' => $lok->nama_kecamatan,
                'kab' => 'Kabupaten Indramayu',
                'lat' => (float)$lok->pusat_lat,
                'lng' => (float)$lok->pusat_lng,
            ];
        }

        // Daerah tetangga yang muncul pada alamat PNS (Cirebon, Majalengka, Subang)
        $neighborMap = [
            'gunungjati' => ['kec' => 'Gunungjati', 'kab' => 'Kabupaten Cirebon', 'lat' => -6.6650, 'lng' => 108.5320],
            'cirebon'    => ['kec' => 'Cirebon', 'kab' => 'Kota Cirebon', 'lat' => -6.7063, 'lng' => 108.5570],
            'majalengka' => ['kec' => 'Majalengka', 'kab' => 'Kabupaten Majalengka', 'lat' => -6.8364, 'lng' => 108.2275],
            'subang'     => ['kec' => 'Subang', 'kab' => 'Kabupaten Subang', 'lat' => -6.5715, 'lng' => 107.7587],
            'pusakanagara' => ['kec' => 'Pusakanagara', 'kab' => 'Kabupaten Subang', 'lat' => -6.2715, 'lng' => 107.8820],
        ];

        $allGeo = array_merge($lokasiMap, $neighborMap);
        $geoKeys = array_keys($allGeo);
        usort($geoKeys, fn ($a, $b) => strlen($b) <=> strlen($a));

        // =========================================================================
        // 5. SEED 165 PNS RIIL DARI DATA PNS FIX TERHUBUNG PENUH
        // =========================================================================
        $inserted = 0;
        $geocodedExact = 0;

        foreach ($payload['pns_fix'] as $idx => $p) {
            $uKey = strtolower(trim($p['unit_kerja_canonical'] ?? $p['unit_induk']));
            $jKey = $p['jabatan_key'];
            $unit = $unitByKey[$uKey] ?? null;
            $master = $masterByKey[$jKey] ?? null;
            $formasi = $formasiByPair[$uKey . '|' . $jKey] ?? null;

            if (!$unit || !$master) {
                continue;
            }

            // Deteksi kecamatan dari teks ALAMAT
            $alamatLow = strtolower($p['alamat'] ?? '');
            $matchedGeo = null;
            foreach ($geoKeys as $gk) {
                if ($gk !== '' && str_contains($alamatLow, $gk)) {
                    $matchedGeo = $allGeo[$gk];
                    $geocodedExact++;
                    break;
                }
            }

            // Jika alamat terlalu singkat (mis. "Blok Suwiti" tanpa kecamatan), fallback deterministik ke kecamatan sekitar Indramayu
            if (!$matchedGeo) {
                $fallbackKeys = array_keys($lokasiMap);
                $pick = $fallbackKeys[crc32($p['nip']) % max(1, count($fallbackKeys))];
                $matchedGeo = $lokasiMap[$pick] ?? [
                    'kec' => 'Indramayu',
                    'kab' => 'Kabupaten Indramayu',
                    'lat' => -6.32635,
                    'lng' => 108.32042,
                ];
            }

            // Tambahkan jitter mikro deterministik (+/- ~600m) berdasarkan hash NIP agar pin peta tidak saling menumpuk
            $hash = crc32($p['nip']);
            $jitterLat = ((($hash % 1000) / 1000.0) - 0.5) * 0.012;
            $jitterLng = (((($hash >> 10) % 1000) / 1000.0) - 0.5) * 0.012;

            $domLat = round($matchedGeo['lat'] + $jitterLat, 6);
            $domLng = round($matchedGeo['lng'] + $jitterLng, 6);

            $jarakKm = $haversine->calculateDistance(
                $domLat,
                $domLng,
                (float)$unit->kantor_lat,
                (float)$unit->kantor_lng
            );
            $zona = $haversine->classifyZone($jarakKm);

            // Nilai SKP & tanggungan deterministik realistis
            $skp = round(84.0 + (($hash % 120) / 10.0), 1); // 84.0 - 95.9
            $tanggungan = ($hash % 4) + 1; // 1 - 4

            AsnMaster::updateOrCreate(
                ['nip' => $p['nip']],
                [
                    'nama' => $p['nama'],
                    'gelar_depan' => $p['gelar_depan'],
                    'gelar_belakang' => $p['gelar_belakang'],
                    'jabatan' => $master->nama_jabatan,
                    'id_master_jabatan' => $master->id,
                    'id_formasi_jabatan' => $formasi?->id,
                    'jenis_jabatan' => $master->jenis_jabatan,
                    'pangkat_golongan' => $p['pangkat_golongan'],
                    'tmt_golongan' => $p['tmt_golongan'],
                    'masa_kerja_tahun' => $p['masa_kerja_tahun'],
                    'nilai_skp' => $skp,
                    'tanggungan_keluarga' => $tanggungan,
                    'tmt_kerja_unit' => $p['tgl_jabatan'] ?? $p['tgl_pns'] ?? $p['tgl_cpns'],
                    'tgl_jabatan' => $p['tgl_jabatan'],
                    'tgl_cpns' => $p['tgl_cpns'],
                    'tgl_pns' => $p['tgl_pns'],
                    'tingkat_pendidikan' => $p['tingkat_pendidikan'] ?: null,
                    'pendidikan_terakhir' => $p['pendidikan_terakhir'] ?: null,
                    'id_unit_saat_ini' => $unit->id,
                    'unit_kerja_induk' => $unit->nama_unit,
                    'domisili_lat' => $domLat,
                    'domisili_lng' => $domLng,
                    'provinsi' => 'Jawa Barat',
                    'kabupaten_kota' => $matchedGeo['kab'],
                    'kecamatan' => $matchedGeo['kec'],
                    'alamat_detail' => $p['alamat'] ?: ($matchedGeo['kec'] . ', ' . $matchedGeo['kab']),
                    'jarak_ke_kantor_km' => $jarakKm,
                    'zona_jarak' => $zona,
                    'status_permohonan_mutasi' => $zona === 'jauh',
                    'alasan_mutasi' => $zona === 'jauh'
                        ? "Beban jarak tempuh harian {$jarakKm} km (Zona Jauh) dari {$matchedGeo['kec']} menuju {$unit->nama_unit}."
                        : null,
                ]
            );
            $inserted++;
        }

        // =========================================================================
        // 6. SINKRONKAN AKUN LOGIN SAMPLE (ADMIN + 5 SAMPLE AKUN PNS)
        // =========================================================================
        User::updateOrCreate(
            ['email' => 'admin@bkpsdm.go.id'],
            [
                'name' => 'Administrator BKPSDM',
                'nip' => '198501012010011001',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'asn_id' => null,
            ]
        );

        // Hapus akun PNS lama yang asn_id-nya sudah tidak valid lalu buat 5 sampel akun PNS aktif
        User::where('role', 'asn')->delete();
        foreach (AsnMaster::orderBy('id')->limit(5)->get() as $sampleAsn) {
            User::updateOrCreate(
                ['nip' => $sampleAsn->nip],
                [
                    'name' => $sampleAsn->nama,
                    'email' => $sampleAsn->nip . '@bkpsdm.indramayukab.go.id',
                    'password' => Hash::make($sampleAsn->nip),
                    'role' => 'asn',
                    'asn_id' => $sampleAsn->id,
                ]
            );
        }

        $this->command?->info("PnsFixSampleSeeder selesai: {$inserted} PNS (alamat terdeteksi kecamatan: {$geocodedExact}), " . count($formasiByPair) . ' Formasi Jabatan 2026, ' . count($unitByKey) . ' Unit Kerja aktif, dan ' . count($masterByKey) . ' Master Jabatan SIASN terhubung penuh.');
    }
}
