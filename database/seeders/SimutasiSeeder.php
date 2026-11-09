<?php

namespace Database\Seeders;

use App\Models\AsnMaster;
use App\Models\TopsisKriteria;
use App\Models\UnitKerja;
use App\Services\HaversineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SimutasiSeeder extends Seeder
{
    public function run(): void
    {
        $haversine = new HaversineService();

        // 1. Seed Master Kriteria TOPSIS (Dynamic Weighting)
        $kriteriaData = [
            [
                'kode_kriteria' => 'C1',
                'nama_kriteria' => 'Jarak Domisili ke Unit Baru (Km)',
                'bobot' => 0.35,
                'atribut' => 'cost',
                'keterangan' => 'Makin dekat jarak tempuh dari rumah ke unit tujuan, makin tinggi prioritas mutasi.',
            ],
            [
                'kode_kriteria' => 'C2',
                'nama_kriteria' => 'Masa Kerja di Unit Asal (Tahun)',
                'bobot' => 0.25,
                'atribut' => 'benefit',
                'keterangan' => 'Pegawai yang sudah lama mengabdi di unit saat ini diprioritaskan untuk rotasi/penyegaran.',
            ],
            [
                'kode_kriteria' => 'C3',
                'nama_kriteria' => 'Pangkat / Golongan Ruang',
                'bobot' => 0.15,
                'atribut' => 'benefit',
                'keterangan' => 'Kesesuaian jenjang karir dan kepangkatan ruang ASN.',
            ],
            [
                'kode_kriteria' => 'C4',
                'nama_kriteria' => 'Penghematan Beban Jarak Harian (Km)',
                'bobot' => 0.25,
                'atribut' => 'benefit',
                'keterangan' => 'Selisih penghematan kilometer antara jarak lama dikurangi jarak baru.',
            ],
        ];

        foreach ($kriteriaData as $k) {
            TopsisKriteria::updateOrCreate(['kode_kriteria' => $k['kode_kriteria']], $k);
        }

        // 2. Seed Master Unit Kerja Nyata (Kabupaten Indramayu & Sekitarnya)
        $units = [
            [
                'kode_unit' => 'SKPD-001',
                'nama_unit' => 'Badan Kepegawaian dan Pengembangan SDM (BKPSDM)',
                'kategori' => 'Badan',
                'kantor_lat' => -6.326350,
                'kantor_lng' => 108.320420,
                'alamat_kantor' => 'Jl. Mayor Oking No. 1, Indramayu',
                'kuota_formasi_kosong' => 2,
                'bezetting_saat_ini' => 48,
                'kebutuhan_abk' => 50,
            ],
            [
                'kode_unit' => 'SKPD-002',
                'nama_unit' => 'Sekretariat Daerah (Setda Kabupaten Indramayu)',
                'kategori' => 'Dinas',
                'kantor_lat' => -6.328100,
                'kantor_lng' => 108.323500,
                'alamat_kantor' => 'Jl. Mayjen Sutoyo No. 1E, Indramayu',
                'kuota_formasi_kosong' => 0, // Penuh (tidak boleh rekomendasi mutasi masuk)
                'bezetting_saat_ini' => 120,
                'kebutuhan_abk' => 120,
            ],
            [
                'kode_unit' => 'KEC-001',
                'nama_unit' => 'Kantor Kecamatan Jatibarang',
                'kategori' => 'Kecamatan',
                'kantor_lat' => -6.471500,
                'kantor_lng' => 108.314200,
                'alamat_kantor' => 'Jl. Mayor Dasuki No. 84, Jatibarang',
                'kuota_formasi_kosong' => 3,
                'bezetting_saat_ini' => 22,
                'kebutuhan_abk' => 25,
            ],
            [
                'kode_unit' => 'KEC-002',
                'nama_unit' => 'Kantor Kecamatan Haurgeulis',
                'kategori' => 'Kecamatan',
                'kantor_lat' => -6.459800,
                'kantor_lng' => 107.954600,
                'alamat_kantor' => 'Jl. Jenderal Sudirman No. 12, Haurgeulis',
                'kuota_formasi_kosong' => 4,
                'bezetting_saat_ini' => 18,
                'kebutuhan_abk' => 22,
            ],
            [
                'kode_unit' => 'KEC-003',
                'nama_unit' => 'Kantor Kecamatan Karangampel',
                'kategori' => 'Kecamatan',
                'kantor_lat' => -6.462800,
                'kantor_lng' => 108.452100,
                'alamat_kantor' => 'Jl. Raya Karangampel No. 45, Karangampel',
                'kuota_formasi_kosong' => 2,
                'bezetting_saat_ini' => 20,
                'kebutuhan_abk' => 22,
            ],
            [
                'kode_unit' => 'KEC-004',
                'nama_unit' => 'Kantor Kecamatan Patrol',
                'kategori' => 'Kecamatan',
                'kantor_lat' => -6.312900,
                'kantor_lng' => 108.006800,
                'alamat_kantor' => 'Jl. Raya Patrol No. 10, Patrol',
                'kuota_formasi_kosong' => 1,
                'bezetting_saat_ini' => 19,
                'kebutuhan_abk' => 20,
            ],
            [
                'kode_unit' => 'RS-001',
                'nama_unit' => 'RSUD Indramayu',
                'kategori' => 'UPTD',
                'kantor_lat' => -6.338500,
                'kantor_lng' => 108.318800,
                'alamat_kantor' => 'Jl. Murahnara No. 7, Sindang, Indramayu',
                'kuota_formasi_kosong' => 5,
                'bezetting_saat_ini' => 310,
                'kebutuhan_abk' => 315,
            ],
        ];

        $unitMap = [];
        foreach ($units as $u) {
            $created = UnitKerja::updateOrCreate(['kode_unit' => $u['kode_unit']], $u);
            $unitMap[$u['kode_unit']] = $created;
        }

        // 3. Seed ASN Master (Kombinasi Variasi Jarak: Dekat, Sedang, Jauh)
        $asnList = [
            [
                'nip' => '198507122010011005',
                'nama' => 'Ahmad Fauzi',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.IP, M.Si',
                'jabatan' => 'Analis Kepegawaian Ahli Muda',
                'pangkat_golongan' => 'III/c',
                'masa_kerja_tahun' => 7,
                'tmt_kerja_unit' => '2019-03-01',
                'unit_kode' => 'SKPD-001', // Kantor saat ini di BKPSDM (Indramayu Kota)
                // Domisili di Haurgeulis (Jauh sekali dari kota Indramayu: ~44 km)
                'domisili_lat' => -6.463200,
                'domisili_lng' => 107.951200,
                'provinsi' => 'Jawa Barat',
                'kabupaten_kota' => 'Kabupaten Indramayu',
                'kecamatan' => 'Haurgeulis',
                'kelurahan_desa' => 'Haurkolot',
                'kode_desa' => '32.12.18.2001',
                'alamat_detail' => 'Dusun Sukamulya RT 03/RW 01, Haurkolot',
                'status_permohonan_mutasi' => true,
                'alasan_mutasi' => 'Beban jarak perjalanan harian > 40 km (PP 85 km) mengganggu kondisi fisik dan efisiensi waktu kerja.',
            ],
            [
                'nip' => '199002152014022003',
                'nama' => 'Siti Nurhaliza',
                'gelar_depan' => 'dr.',
                'gelar_belakang' => 'M.K.M',
                'jabatan' => 'Dokter Umum Pertama',
                'pangkat_golongan' => 'III/b',
                'masa_kerja_tahun' => 5,
                'tmt_kerja_unit' => '2021-01-15',
                'unit_kode' => 'RS-001', // Kantor saat ini di RSUD Indramayu
                // Domisili di Jatibarang (~17 km)
                'domisili_lat' => -6.474200,
                'domisili_lng' => 108.311500,
                'provinsi' => 'Jawa Barat',
                'kabupaten_kota' => 'Kabupaten Indramayu',
                'kecamatan' => 'Jatibarang',
                'kelurahan_desa' => 'Bulak',
                'kode_desa' => '32.12.08.2004',
                'alamat_detail' => 'Jl. Terusan Jatibarang No. 14, Bulak',
                'status_permohonan_mutasi' => true,
                'alasan_mutasi' => 'Merawat orang tua lanjut usia di Jatibarang dan mendekati anak sekolah.',
            ],
            [
                'nip' => '198203202008011002',
                'nama' => 'Bambang Irawan',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Sos',
                'jabatan' => 'Pengadministrasi Perkantoran',
                'pangkat_golongan' => 'III/a',
                'masa_kerja_tahun' => 9,
                'tmt_kerja_unit' => '2017-06-01',
                'unit_kode' => 'KEC-001', // Kantor saat ini di Kecamatan Jatibarang
                // Domisili di Indramayu Kota dekat BKPSDM (~16 km)
                'domisili_lat' => -6.331500,
                'domisili_lng' => 108.324200,
                'provinsi' => 'Jawa Barat',
                'kabupaten_kota' => 'Kabupaten Indramayu',
                'kecamatan' => 'Indramayu',
                'kelurahan_desa' => 'Lemahmekar',
                'kode_desa' => '32.12.01.1002',
                'alamat_detail' => 'Gg. Kenanga No. 22, Lemahmekar',
                'status_permohonan_mutasi' => true,
                'alasan_mutasi' => 'Ingin mutasi ke lingkungan kantor dinas kabupaten/BKPSDM di kota.',
            ],
            [
                'nip' => '199211052019032011',
                'nama' => 'Rina Marlina',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.Pd',
                'jabatan' => 'Pranata Komputer Ahli Pertama',
                'pangkat_golongan' => 'III/a',
                'masa_kerja_tahun' => 4,
                'tmt_kerja_unit' => '2022-04-01',
                'unit_kode' => 'SKPD-001', // Kantor di BKPSDM (Indramayu Kota)
                // Domisili di Karangampel (~22 km)
                'domisili_lat' => -6.465100,
                'domisili_lng' => 108.455000,
                'provinsi' => 'Jawa Barat',
                'kabupaten_kota' => 'Kabupaten Indramayu',
                'kecamatan' => 'Karangampel',
                'kelurahan_desa' => 'Karangampel Kidul',
                'kode_desa' => '32.12.12.2003',
                'alamat_detail' => 'Jl. Pasar Karangampel Blok C-3',
                'status_permohonan_mutasi' => true,
                'alasan_mutasi' => 'Memohon rotasi ke Kantor Kecamatan Karangampel dekat domisili.',
            ],
            [
                'nip' => '198704182011011006',
                'nama' => 'Dedi Supriadi',
                'gelar_depan' => null,
                'gelar_belakang' => 'S.E',
                'jabatan' => 'Bendahara Pengeluaran',
                'pangkat_golongan' => 'III/b',
                'masa_kerja_tahun' => 6,
                'tmt_kerja_unit' => '2020-02-10',
                'unit_kode' => 'KEC-002', // Kantor di Haurgeulis
                // Domisili di Patrol (~20 km)
                'domisili_lat' => -6.314200,
                'domisili_lng' => 108.012000,
                'provinsi' => 'Jawa Barat',
                'kabupaten_kota' => 'Kabupaten Indramayu',
                'kecamatan' => 'Patrol',
                'kelurahan_desa' => 'Patrol Baru',
                'kode_desa' => '32.12.23.2002',
                'alamat_detail' => 'Jl. Pantura Patrol KM 132',
                'status_permohonan_mutasi' => true,
                'alasan_mutasi' => 'Memohon mutasi ke Kantor Kecamatan Patrol.',
            ],
        ];

        foreach ($asnList as $asn) {
            $unit = $unitMap[$asn['unit_kode']];

            // Hitung jarak riil ke kantor saat ini menggunakan Haversine
            $jarak = $haversine->calculateDistance(
                $asn['domisili_lat'],
                $asn['domisili_lng'],
                $unit->kantor_lat,
                $unit->kantor_lng
            );

            $zona = $haversine->classifyZone($jarak);

            AsnMaster::updateOrCreate(
                ['nip' => $asn['nip']],
                [
                    'nip' => $asn['nip'],
                    'nama' => $asn['nama'],
                    'gelar_depan' => $asn['gelar_depan'],
                    'gelar_belakang' => $asn['gelar_belakang'],
                    'jabatan' => $asn['jabatan'],
                    'pangkat_golongan' => $asn['pangkat_golongan'],
                    'masa_kerja_tahun' => $asn['masa_kerja_tahun'],
                    'tmt_kerja_unit' => $asn['tmt_kerja_unit'],
                    'id_unit_saat_ini' => $unit->id,
                    'domisili_lat' => $asn['domisili_lat'],
                    'domisili_lng' => $asn['domisili_lng'],
                    'provinsi' => $asn['provinsi'],
                    'kabupaten_kota' => $asn['kabupaten_kota'],
                    'kecamatan' => $asn['kecamatan'],
                    'kelurahan_desa' => $asn['kelurahan_desa'],
                    'kode_desa' => $asn['kode_desa'],
                    'alamat_detail' => $asn['alamat_detail'],
                    'jarak_ke_kantor_km' => $jarak,
                    'zona_jarak' => $zona,
                    'status_permohonan_mutasi' => $asn['status_permohonan_mutasi'],
                    'alasan_mutasi' => $asn['alasan_mutasi'],
                ]
            );
        }
    }
}
