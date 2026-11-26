<?php

namespace Database\Seeders;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\MasterJabatan;
use App\Models\UnitKerja;
use App\Services\HaversineService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeder MasterJabatanSampleSeeder
 *
 * Mengimpor 80 sampel terkurasi (40 Jabatan Fungsional + 40 Jabatan Pelaksana)
 * dari dataset resmi SIASN BKN (`jabatan_fungsional.xlsx` & `jabatan_pelaksana.xlsx`),
 * lalu membangun data sampel `formasi_jabatan` (2025 & 2026) dan `asn_master`
 * yang 100% terhubung relasinya (FK `id_master_jabatan`, `id_formasi_jabatan`, `id_unit_kerja`).
 */
class MasterJabatanSampleSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/master_jabatan_curated.json');
        if (!File::exists($jsonPath)) {
            $this->command?->error("File fixture tidak ditemukan: {$jsonPath}");
            return;
        }

        $items = json_decode(File::get($jsonPath), true);
        if (!is_array($items)) {
            $this->command?->error('Format JSON master_jabatan_curated.json tidak valid.');
            return;
        }

        // =========================================================================
        // 1. SEED MASTER JABATAN (80 SAMPEL TERKURASI SIASN BKN)
        // =========================================================================
        $jfCounter = 0;
        $jpCounter = 0;
        $masterMap = [];

        foreach ($items as $item) {
            $isJf = ($item['jenis_jabatan'] ?? 'pelaksana') === 'fungsional';
            $kode = $isJf
                ? sprintf('JF-%04d', ++$jfCounter)
                : sprintf('JP-%04d', ++$jpCounter);

            $record = MasterJabatan::updateOrCreate(
                ['nama_jabatan' => $item['nama_jabatan']],
                [
                    'kode_jabatan' => $kode,
                    'siasn_id' => $item['siasn_id'] ?? null,
                    'kode_cepat' => $item['kode_cepat'] ?? null,
                    'jenis_jabatan' => $item['jenis_jabatan'],
                    'jenjang_jabatan' => $item['jenjang_jabatan'] ?? null,
                    'kelas_jabatan' => $item['kelas_jabatan'] ?? null,
                    'bup_usia' => $item['bup_usia'] ?? 58,
                    'rumpun_jabatan' => $item['rumpun_jabatan'] ?? null,
                    'kualifikasi_pendidikan' => $item['kualifikasi_pendidikan'] ?? null,
                ]
            );

            $masterMap[$record->nama_jabatan] = $record;
        }

        // =========================================================================
        // 2. SEED FORMASI JABATAN TERHUBUNG KE MASTER_JABATAN & UNIT_KERJA
        // =========================================================================
        $units = UnitKerja::whereIn('kode_unit', [
            'SKPD-001', // BKPSDM
            'SKPD-002', // Setda (penuh sebagai kontrol anti-overstaffing)
            'KEC-001',  // Kecamatan Jatibarang
            'KEC-002',  // Kecamatan Haurgeulis
            'KEC-003',  // Kecamatan Karangampel
            'KEC-004',  // Kecamatan Patrol
            'RS-001',   // RSUD Indramayu
        ])->get()->keyBy('kode_unit');

        $formasiSpecs = [
            // --- HISTORIS 2025 (TRACING AUDIT) ---
            ['unit' => 'SKPD-001', 'tahun' => 2025, 'periode' => 'Reguler 2025', 'jabatan' => 'Analis Sumber Daya Manusia Aparatur Ahli Pertama', 'abk' => 4, 'bez' => 4, 'status' => 'terisi', 'ket' => 'Terisi penuh melalui Mutasi Internal Baperjakat 2025.'],
            ['unit' => 'SKPD-001', 'tahun' => 2025, 'periode' => 'Reguler 2025', 'jabatan' => 'Pengelola Sistem Informasi', 'abk' => 2, 'bez' => 2, 'status' => 'terisi', 'ket' => 'Terisi alokasi CPNS & rotasi teknis 2025.'],
            ['unit' => 'KEC-002',  'tahun' => 2025, 'periode' => 'Reguler 2025', 'jabatan' => 'Pengadministrasi Perkantoran', 'abk' => 3, 'bez' => 3, 'status' => 'terisi', 'ket' => 'Penempatan rotasi wilayah barat tahun 2025.'],
            ['unit' => 'RS-001',   'tahun' => 2025, 'periode' => 'Reguler 2025', 'jabatan' => 'Perawat Ahli Pertama', 'abk' => 12, 'bez' => 12, 'status' => 'terisi', 'ket' => 'Pemenuhan tenaga fungsional keperawatan 2025.'],

            // --- AKTIF 2026 (TERBUKA UNTUK REKOMENDASI TOPSIS & MUTASI) ---
            ['unit' => 'SKPD-001', 'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Analis Sumber Daya Manusia Aparatur Ahli Muda', 'abk' => 5, 'bez' => 3, 'status' => 'terbuka', 'ket' => 'Kebutuhan tim penataan mutasi dan pengembangan karir BKPSDM.'],
            ['unit' => 'SKPD-001', 'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pranata Komputer Ahli Pertama', 'abk' => 4, 'bez' => 2, 'status' => 'terbuka', 'ket' => 'Pengembangan arsitektur SPBE & SIMUTASI-GIS BKPSDM.'],
            ['unit' => 'SKPD-001', 'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengelola Kepegawaian', 'abk' => 4, 'bez' => 3, 'status' => 'terbuka', 'ket' => 'Dukungan operasional administrasi mutasi & kepangkatan.'],

            // Setda penuh (kuota 0 -> kontrol validasi anti-overstaffing)
            ['unit' => 'SKPD-002', 'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Analis Kebijakan Ahli Muda', 'abk' => 4, 'bez' => 4, 'status' => 'terisi', 'ket' => 'Kuota ABK Setda 2026 telah terpenuhi (anti-overstaffing).'],
            ['unit' => 'SKPD-002', 'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Perencana Ahli Pertama', 'abk' => 3, 'bez' => 3, 'status' => 'terisi', 'ket' => 'Formasi perencanaan Setda sudah penuh.'],

            // Kecamatan Jatibarang
            ['unit' => 'KEC-001',  'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengelola Kepegawaian', 'abk' => 3, 'bez' => 1, 'status' => 'terbuka', 'ket' => 'Penguatan pengelolaan administrasi kepegawaian Kecamatan Jatibarang.'],
            ['unit' => 'KEC-001',  'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengadministrasi Perizinan', 'abk' => 4, 'bez' => 2, 'status' => 'terbuka', 'ket' => 'Pelayanan administrasi terpadu Kecamatan Jatibarang.'],

            // Kecamatan Haurgeulis
            ['unit' => 'KEC-002',  'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengadministrasi Pemerintahan', 'abk' => 4, 'bez' => 2, 'status' => 'terbuka', 'ket' => 'Kebutuhan pelayanan pemerintahan umum wilayah barat.'],
            ['unit' => 'KEC-002',  'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengelola Keuangan', 'abk' => 3, 'bez' => 1, 'status' => 'terbuka', 'ket' => 'Penataan laporan keuangan dan SPJ Kecamatan Haurgeulis.'],

            // Kecamatan Karangampel
            ['unit' => 'KEC-003',  'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengadministrasi Perkantoran', 'abk' => 4, 'bez' => 2, 'status' => 'terbuka', 'ket' => 'Dukungan tata usaha dan pelayanan masyarakat Karangampel.'],
            ['unit' => 'KEC-003',  'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pranata Komputer Terampil', 'abk' => 2, 'bez' => 1, 'status' => 'terbuka', 'ket' => 'Digitalisasi layanan kependudukan dan surat kecamatan.'],

            // Kecamatan Patrol
            ['unit' => 'KEC-004',  'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengelola Data Dan Informasi', 'abk' => 3, 'bez' => 1, 'status' => 'terbuka', 'ket' => 'Pengelolaan basis data pelayanan Kecamatan Patrol.'],

            // RSUD Indramayu
            ['unit' => 'RS-001',   'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Perawat Ahli Muda', 'abk' => 10, 'bez' => 7, 'status' => 'terbuka', 'ket' => 'Pemenuhan tenaga keperawatan rawat inap RSUD Indramayu.'],
            ['unit' => 'RS-001',   'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Bidan Ahli Pertama', 'abk' => 8, 'bez' => 6, 'status' => 'terbuka', 'ket' => 'Penguatan layanan kebidanan dan KIA RSUD Indramayu.'],
            ['unit' => 'RS-001',   'tahun' => 2026, 'periode' => 'Semester 1 - 2026', 'jabatan' => 'Pengadministrasi Umum Rumah Sakit', 'abk' => 6, 'bez' => 4, 'status' => 'terbuka', 'ket' => 'Administrasi rekam medis dan layanan operasional RSUD.'],
        ];

        $formasiMap = [];
        foreach ($formasiSpecs as $spec) {
            $unit = $units->get($spec['unit']);
            $master = $masterMap[$spec['jabatan']] ?? null;
            if (!$unit || !$master) {
                continue;
            }

            $lowong = $spec['status'] === 'terbuka'
                ? max(0, $spec['abk'] - $spec['bez'])
                : 0;

            $formasi = FormasiJabatan::updateOrCreate(
                [
                    'id_unit_kerja' => $unit->id,
                    'tahun_anggaran' => $spec['tahun'],
                    'nama_jabatan' => $master->nama_jabatan,
                ],
                [
                    'id_master_jabatan' => $master->id,
                    'periode' => $spec['periode'],
                    'jenis_jabatan' => $master->jenis_jabatan,
                    'jenjang_jabatan' => $master->jenjang_jabatan,
                    'kualifikasi_pendidikan' => $master->kualifikasi_pendidikan,
                    'kebutuhan_abk' => $spec['abk'],
                    'bezetting_riil' => $spec['bez'],
                    'kuota_lowong' => $lowong,
                    'status' => $spec['status'],
                    'keterangan' => $spec['ket'],
                ]
            );

            $formasiMap[$spec['unit'] . '|' . $spec['tahun'] . '|' . $master->nama_jabatan] = $formasi;
        }

        // Sinkronkan kuota unit kerja berdasarkan formasi aktif 2026
        foreach ($units as $unit) {
            $unit->syncKuotaFormasi(2026);
        }

        // =========================================================================
        // 3. SEED ASN MASTER TERHUBUNG KE MASTER_JABATAN, FORMASI_JABATAN & UNIT
        // =========================================================================
        $haversine = new HaversineService();

        $asnSpecs = [
            // --- ZONA JAUH (> 20 KM) - PRIORITAS MUTASI ---
            [
                'nip' => '198507122010011005', 'nama' => 'Ahmad Fauzi', 'gelar_depan' => null, 'gelar_belakang' => 'S.IP., M.Si.',
                'jabatan' => 'Analis Sumber Daya Manusia Aparatur Ahli Muda', 'unit' => 'KEC-002', 'formasi_key' => 'SKPD-001|2026|Analis Sumber Daya Manusia Aparatur Ahli Muda',
                'gol' => 'III/c', 'masa_kerja' => 8, 'skp' => 92.5, 'tanggungan' => 3,
                'tgl_cpns' => '2010-01-01', 'tgl_pns' => '2011-03-01', 'tgl_jabatan' => '2019-06-01', 'tmt_unit' => '2018-04-01', 'tmt_gol' => '2022-04-01',
                'tingkat_pend' => 'S-2', 'pend' => 'Magister Administrasi Publik',
                'lat' => -6.3295, 'lng' => 108.3250, 'kec' => 'Indramayu', 'desa' => 'Karanganyar', 'alamat' => 'Jl. DI Panjaitan No. 18, Indramayu',
                'mohon' => true, 'alasan' => 'Domisili di pusat Kota Indramayu namun bertugas di Kecamatan Haurgeulis (> 40 km PP harian).',
            ],
            [
                'nip' => '198903152014022003', 'nama' => 'Siti Rahmawati', 'gelar_depan' => null, 'gelar_belakang' => 'S.Kom.',
                'jabatan' => 'Pranata Komputer Ahli Pertama', 'unit' => 'KEC-004', 'formasi_key' => 'SKPD-001|2026|Pranata Komputer Ahli Pertama',
                'gol' => 'III/b', 'masa_kerja' => 6, 'skp' => 91.0, 'tanggungan' => 2,
                'tgl_cpns' => '2014-02-01', 'tgl_pns' => '2015-04-01', 'tgl_jabatan' => '2020-01-01', 'tmt_unit' => '2020-01-01', 'tmt_gol' => '2023-04-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Teknik Informatika',
                'lat' => -6.3402, 'lng' => 108.3160, 'kec' => 'Sindang', 'desa' => 'Panyindangan Kulon', 'alamat' => 'Perum Sindang Asri Blok C4, Sindang',
                'mohon' => true, 'alasan' => 'Jarak tempuh harian Sindang–Patrol > 35 km; linier dengan kebutuhan Pranata Komputer BKPSDM.',
            ],
            [
                'nip' => '198711202011011008', 'nama' => 'Dedi Kurniawan', 'gelar_depan' => null, 'gelar_belakang' => 'A.Md.',
                'jabatan' => 'Pengelola Kepegawaian', 'unit' => 'SKPD-001', 'formasi_key' => 'KEC-001|2026|Pengelola Kepegawaian',
                'gol' => 'III/a', 'masa_kerja' => 7, 'skp' => 88.5, 'tanggungan' => 3,
                'tgl_cpns' => '2011-01-01', 'tgl_pns' => '2012-03-01', 'tgl_jabatan' => '2019-01-01', 'tmt_unit' => '2019-01-01', 'tmt_gol' => '2022-10-01',
                'tingkat_pend' => 'D-III', 'pend' => 'Manajemen Administrasi',
                'lat' => -6.4730, 'lng' => 108.3125, 'kec' => 'Jatibarang', 'desa' => 'Bulak', 'alamat' => 'Jl. Mayor Dasuki Gg. Melati No. 5, Jatibarang',
                'mohon' => true, 'alasan' => 'Mengajukan mutasi mendekati domisili keluarga di Jatibarang.',
            ],
            [
                'nip' => '199104102015032004', 'nama' => 'Nurul Hidayah', 'gelar_depan' => null, 'gelar_belakang' => 'S.Kep., Ners',
                'jabatan' => 'Perawat Ahli Muda', 'unit' => 'KEC-002', 'formasi_key' => 'RS-001|2026|Perawat Ahli Muda',
                'gol' => 'III/c', 'masa_kerja' => 6, 'skp' => 93.2, 'tanggungan' => 2,
                'tgl_cpns' => '2015-03-01', 'tgl_pns' => '2016-05-01', 'tgl_jabatan' => '2021-02-01', 'tmt_unit' => '2020-03-01', 'tmt_gol' => '2024-04-01',
                'tingkat_pend' => 'Profesi', 'pend' => 'Profesi Ners',
                'lat' => -6.3370, 'lng' => 108.3210, 'kec' => 'Sindang', 'desa' => 'Dermayu', 'alamat' => 'Jl. Murahnara Gg. Anggrek No. 11, Sindang',
                'mohon' => true, 'alasan' => 'Domisili dekat RSUD Indramayu (< 1 km), saat ini bertugas di wilayah Haurgeulis.',
            ],
            [
                'nip' => '198808172012121002', 'nama' => 'Hendra Wijaya', 'gelar_depan' => null, 'gelar_belakang' => 'S.E.',
                'jabatan' => 'Pengelola Keuangan', 'unit' => 'SKPD-002', 'formasi_key' => 'KEC-002|2026|Pengelola Keuangan',
                'gol' => 'III/b', 'masa_kerja' => 9, 'skp' => 89.0, 'tanggungan' => 4,
                'tgl_cpns' => '2012-12-01', 'tgl_pns' => '2014-01-01', 'tgl_jabatan' => '2018-07-01', 'tmt_unit' => '2017-01-01', 'tmt_gol' => '2021-10-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Akuntansi',
                'lat' => -6.4585, 'lng' => 107.9570, 'kec' => 'Haurgeulis', 'desa' => 'Sukajati', 'alamat' => 'Jl. Raya Haurgeulis No. 42, Haurgeulis',
                'mohon' => true, 'alasan' => 'Rumah di Haurgeulis, saat ini berdinas di Setda Indramayu (> 42 km).',
            ],
            [
                'nip' => '199006222014031001', 'nama' => 'Budi Santoso', 'gelar_depan' => null, 'gelar_belakang' => 'S.Sos.',
                'jabatan' => 'Pengadministrasi Perkantoran', 'unit' => 'KEC-004', 'formasi_key' => 'KEC-003|2026|Pengadministrasi Perkantoran',
                'gol' => 'III/a', 'masa_kerja' => 5, 'skp' => 86.5, 'tanggungan' => 2,
                'tgl_cpns' => '2014-03-01', 'tgl_pns' => '2015-06-01', 'tgl_jabatan' => '2021-01-01', 'tmt_unit' => '2021-01-01', 'tmt_gol' => '2023-10-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Ilmu Administrasi Negara',
                'lat' => -6.4640, 'lng' => 108.4505, 'kec' => 'Karangampel', 'desa' => 'Karangampel Kidul', 'alamat' => 'Jl. Raya Karangampel No. 78, Karangampel',
                'mohon' => true, 'alasan' => 'Domisili di Karangampel namun bertugas di Patrol (> 50 km).',
            ],

            // --- ZONA SEDANG (5 - 20 KM) ---
            [
                'nip' => '199205142018012006', 'nama' => 'Rina Marlina', 'gelar_depan' => null, 'gelar_belakang' => 'S.Tr.Keb.',
                'jabatan' => 'Bidan Ahli Pertama', 'unit' => 'RS-001', 'formasi_key' => 'RS-001|2026|Bidan Ahli Pertama',
                'gol' => 'III/a', 'masa_kerja' => 5, 'skp' => 90.0, 'tanggungan' => 1,
                'tgl_cpns' => '2018-01-01', 'tgl_pns' => '2019-03-01', 'tgl_jabatan' => '2019-03-01', 'tmt_unit' => '2021-02-01', 'tmt_gol' => '2022-04-01',
                'tingkat_pend' => 'D-IV', 'pend' => 'Kebidanan',
                'lat' => -6.4210, 'lng' => 108.3400, 'kec' => 'Sliyeg', 'desa' => 'Sliyeg Lor', 'alamat' => 'Jl. Raya Sliyeg No. 23, Sliyeg',
                'mohon' => false, 'alasan' => null,
            ],
            [
                'nip' => '198602092011011004', 'nama' => 'Agus Supriyanto', 'gelar_depan' => null, 'gelar_belakang' => 'A.Md.Kom.',
                'jabatan' => 'Pranata Komputer Terampil', 'unit' => 'SKPD-001', 'formasi_key' => 'KEC-003|2026|Pranata Komputer Terampil',
                'gol' => 'II/d', 'masa_kerja' => 8, 'skp' => 87.5, 'tanggungan' => 3,
                'tgl_cpns' => '2011-01-01', 'tgl_pns' => '2012-04-01', 'tgl_jabatan' => '2018-01-01', 'tmt_unit' => '2018-01-01', 'tmt_gol' => '2021-04-01',
                'tingkat_pend' => 'D-III', 'pend' => 'Manajemen Informatika',
                'lat' => -6.4510, 'lng' => 108.4350, 'kec' => 'Karangampel', 'desa' => 'Dukuh Tengah', 'alamat' => 'Blok Balai Desa No. 9, Karangampel',
                'mohon' => true, 'alasan' => 'Ingin lebih dekat dengan keluarga di wilayah timur (Karangampel).',
            ],
            [
                'nip' => '199309012019022007', 'nama' => 'Dewi Lestari', 'gelar_depan' => null, 'gelar_belakang' => 'S.A.P.',
                'jabatan' => 'Pengadministrasi Perizinan', 'unit' => 'KEC-001', 'formasi_key' => 'KEC-001|2026|Pengadministrasi Perizinan',
                'gol' => 'III/a', 'masa_kerja' => 4, 'skp' => 89.5, 'tanggungan' => 1,
                'tgl_cpns' => '2019-02-01', 'tgl_pns' => '2020-04-01', 'tgl_jabatan' => '2020-04-01', 'tmt_unit' => '2022-01-01', 'tmt_gol' => '2023-04-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Administrasi Publik',
                'lat' => -6.3820, 'lng' => 108.3520, 'kec' => 'Balongan', 'desa' => 'Sudimampir', 'alamat' => 'Jl. Raya Balongan No. 15, Balongan',
                'mohon' => false, 'alasan' => null,
            ],
            [
                'nip' => '198410252009011003', 'nama' => 'Taufik Hidayat', 'gelar_depan' => null, 'gelar_belakang' => 'S.H.',
                'jabatan' => 'Analis Kebijakan Ahli Muda', 'unit' => 'SKPD-002', 'formasi_key' => 'SKPD-002|2026|Analis Kebijakan Ahli Muda',
                'gol' => 'III/d', 'masa_kerja' => 10, 'skp' => 94.0, 'tanggungan' => 3,
                'tgl_cpns' => '2009-01-01', 'tgl_pns' => '2010-03-01', 'tgl_jabatan' => '2017-05-01', 'tmt_unit' => '2016-06-01', 'tmt_gol' => '2023-04-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Ilmu Hukum',
                'lat' => -6.4120, 'lng' => 108.2950, 'kec' => 'Lohbener', 'desa' => 'Legok', 'alamat' => 'Jl. Raya Lohbener No. 66, Lohbener',
                'mohon' => false, 'alasan' => null,
            ],
            [
                'nip' => '199012112015021005', 'nama' => 'Fajar Pratama', 'gelar_depan' => null, 'gelar_belakang' => 'S.Kom.',
                'jabatan' => 'Pengelola Data Dan Informasi', 'unit' => 'KEC-002', 'formasi_key' => 'KEC-004|2026|Pengelola Data Dan Informasi',
                'gol' => 'III/b', 'masa_kerja' => 6, 'skp' => 88.0, 'tanggungan' => 2,
                'tgl_cpns' => '2015-02-01', 'tgl_pns' => '2016-04-01', 'tgl_jabatan' => '2020-01-01', 'tmt_unit' => '2020-01-01', 'tmt_gol' => '2023-10-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Sistem Informasi',
                'lat' => -6.3150, 'lng' => 108.0120, 'kec' => 'Patrol', 'desa' => 'Patrol Lor', 'alamat' => 'Jl. Pantura Patrol No. 31, Patrol',
                'mohon' => true, 'alasan' => 'Ingin mendekat ke domisili Patrol (~17 km dari Haurgeulis).',
            ],

            // --- ZONA DEKAT (< 5 KM) ---
            [
                'nip' => '198305082008012002', 'nama' => 'Yuniarti Kusuma', 'gelar_depan' => null, 'gelar_belakang' => 'S.Sos., M.M.',
                'jabatan' => 'Analis Sumber Daya Manusia Aparatur Ahli Muda', 'unit' => 'SKPD-001', 'formasi_key' => 'SKPD-001|2026|Analis Sumber Daya Manusia Aparatur Ahli Muda',
                'gol' => 'III/d', 'masa_kerja' => 9, 'skp' => 95.0, 'tanggungan' => 2,
                'tgl_cpns' => '2008-01-01', 'tgl_pns' => '2009-03-01', 'tgl_jabatan' => '2018-02-01', 'tmt_unit' => '2017-02-01', 'tmt_gol' => '2022-04-01',
                'tingkat_pend' => 'S-2', 'pend' => 'Magister Manajemen',
                'lat' => -6.3280, 'lng' => 108.3180, 'kec' => 'Indramayu', 'desa' => 'Lemahmekar', 'alamat' => 'Jl. Veteran No. 14, Indramayu',
                'mohon' => false, 'alasan' => null,
            ],
            [
                'nip' => '199401202019031002', 'nama' => 'Rizky Maulana', 'gelar_depan' => null, 'gelar_belakang' => 'S.Kom.',
                'jabatan' => 'Pranata Komputer Ahli Pertama', 'unit' => 'SKPD-001', 'formasi_key' => 'SKPD-001|2026|Pranata Komputer Ahli Pertama',
                'gol' => 'III/a', 'masa_kerja' => 4, 'skp' => 92.0, 'tanggungan' => 1,
                'tgl_cpns' => '2019-03-01', 'tgl_pns' => '2020-05-01', 'tgl_jabatan' => '2020-05-01', 'tmt_unit' => '2022-03-01', 'tmt_gol' => '2023-04-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Ilmu Komputer',
                'lat' => -6.3310, 'lng' => 108.3290, 'kec' => 'Indramayu', 'desa' => 'Margadadi', 'alamat' => 'Jl. Olahraga No. 22, Margadadi',
                'mohon' => false, 'alasan' => null,
            ],
            [
                'nip' => '198907302014021006', 'nama' => 'Eko Prasetyo', 'gelar_depan' => null, 'gelar_belakang' => null,
                'jabatan' => 'Pengadministrasi Umum Rumah Sakit', 'unit' => 'RS-001', 'formasi_key' => 'RS-001|2026|Pengadministrasi Umum Rumah Sakit',
                'gol' => 'II/d', 'masa_kerja' => 7, 'skp' => 87.0, 'tanggungan' => 2,
                'tgl_cpns' => '2014-02-01', 'tgl_pns' => '2015-04-01', 'tgl_jabatan' => '2019-01-01', 'tmt_unit' => '2019-01-01', 'tmt_gol' => '2023-04-01',
                'tingkat_pend' => 'D-III', 'pend' => 'Administrasi Perkantoran',
                'lat' => -6.3420, 'lng' => 108.3150, 'kec' => 'Sindang', 'desa' => 'Kenanga', 'alamat' => 'Jl. Raya Kenanga No. 8, Sindang',
                'mohon' => false, 'alasan' => null,
            ],
            [
                'nip' => '199108192016012003', 'nama' => 'Laila Sari', 'gelar_depan' => null, 'gelar_belakang' => 'S.E.',
                'jabatan' => 'Perencana Ahli Pertama', 'unit' => 'SKPD-002', 'formasi_key' => 'SKPD-002|2026|Perencana Ahli Pertama',
                'gol' => 'III/b', 'masa_kerja' => 6, 'skp' => 91.5, 'tanggungan' => 2,
                'tgl_cpns' => '2016-01-01', 'tgl_pns' => '2017-03-01', 'tgl_jabatan' => '2020-02-01', 'tmt_unit' => '2020-02-01', 'tmt_gol' => '2024-04-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Ekonomi Pembangunan',
                'lat' => -6.3250, 'lng' => 108.3270, 'kec' => 'Indramayu', 'desa' => 'Paoman', 'alamat' => 'Jl. Jenderal Ahmad Yani No. 50, Indramayu',
                'mohon' => false, 'alasan' => null,
            ],
            [
                'nip' => '198804122013011009', 'nama' => 'Wahyu Hidayat', 'gelar_depan' => null, 'gelar_belakang' => 'S.IP.',
                'jabatan' => 'Pengadministrasi Pemerintahan', 'unit' => 'KEC-002', 'formasi_key' => 'KEC-002|2026|Pengadministrasi Pemerintahan',
                'gol' => 'III/a', 'masa_kerja' => 8, 'skp' => 88.0, 'tanggungan' => 3,
                'tgl_cpns' => '2013-01-01', 'tgl_pns' => '2014-03-01', 'tgl_jabatan' => '2018-06-01', 'tmt_unit' => '2018-06-01', 'tmt_gol' => '2022-04-01',
                'tingkat_pend' => 'S-1', 'pend' => 'Ilmu Pemerintahan',
                'lat' => -6.4620, 'lng' => 107.9590, 'kec' => 'Haurgeulis', 'desa' => 'Haurgeulis', 'alamat' => 'Jl. Siliwangi No. 19, Haurgeulis',
                'mohon' => false, 'alasan' => null,
            ],
        ];

        foreach ($asnSpecs as $a) {
            $unit = $units->get($a['unit']);
            $master = $masterMap[$a['jabatan']] ?? null;
            $formasi = $formasiMap[$a['formasi_key']] ?? null;
            if (!$unit || !$master) {
                continue;
            }

            $jarakKm = $haversine->calculateDistance(
                $a['lat'],
                $a['lng'],
                (float)$unit->kantor_lat,
                (float)$unit->kantor_lng
            );
            $zona = $haversine->classifyZone($jarakKm);

            AsnMaster::updateOrCreate(
                ['nip' => $a['nip']],
                [
                    'nama' => $a['nama'],
                    'gelar_depan' => $a['gelar_depan'],
                    'gelar_belakang' => $a['gelar_belakang'],
                    'jabatan' => $master->nama_jabatan,
                    'id_master_jabatan' => $master->id,
                    'id_formasi_jabatan' => $formasi?->id,
                    'jenis_jabatan' => $master->jenis_jabatan,
                    'pangkat_golongan' => $a['gol'],
                    'tmt_golongan' => $a['tmt_gol'],
                    'masa_kerja_tahun' => $a['masa_kerja'],
                    'nilai_skp' => $a['skp'],
                    'tanggungan_keluarga' => $a['tanggungan'],
                    'tmt_kerja_unit' => $a['tmt_unit'],
                    'tgl_jabatan' => $a['tgl_jabatan'],
                    'tgl_cpns' => $a['tgl_cpns'],
                    'tgl_pns' => $a['tgl_pns'],
                    'tingkat_pendidikan' => $a['tingkat_pend'],
                    'pendidikan_terakhir' => $a['pend'],
                    'id_unit_saat_ini' => $unit->id,
                    'unit_kerja_induk' => $unit->nama_unit,
                    'domisili_lat' => $a['lat'],
                    'domisili_lng' => $a['lng'],
                    'provinsi' => 'Jawa Barat',
                    'kabupaten_kota' => 'Kabupaten Indramayu',
                    'kecamatan' => $a['kec'],
                    'kelurahan_desa' => $a['desa'],
                    'alamat_detail' => $a['alamat'],
                    'jarak_ke_kantor_km' => $jarakKm,
                    'zona_jarak' => $zona,
                    'status_permohonan_mutasi' => $a['mohon'],
                    'alasan_mutasi' => $a['alasan'],
                ]
            );
        }

        $this->command?->info('MasterJabatanSampleSeeder selesai: 80 Master Jabatan, ' . count($formasiMap) . ' Formasi Jabatan, dan ' . count($asnSpecs) . ' ASN Master terhubung penuh.');
    }
}
