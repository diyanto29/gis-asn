<?php

namespace Database\Seeders;

use App\Models\FormasiJabatan;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

class FormasiJabatanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi data riwayat formasi jabatan multi-tahun (2025 historis & 2026 aktif berjalan)
     * untuk mendukung audit tracing dan kesesuaian formasi ABK.
     */
    public function run(): void
    {
        $bkpsdm = UnitKerja::where('kode_unit', 'SKPD-001')->first();
        $setda = UnitKerja::where('kode_unit', 'SKPD-002')->first();
        $jatibarang = UnitKerja::where('kode_unit', 'KEC-001')->first();
        $haurgeulis = UnitKerja::where('kode_unit', 'KEC-002')->first();
        $patrol = UnitKerja::where('kode_unit', 'KEC-003')->first();
        $rsud = UnitKerja::where('kode_unit', 'UPTD-001')->first();

        $formasiList = [];

        // =========================================================================
        // TAHUN ANGGARAN 2025 (HISTORIS TRACING - PERNAH DIBUKA & TELAH TERISI)
        // =========================================================================
        if ($bkpsdm) {
            $formasiList[] = [
                'id_unit_kerja' => $bkpsdm->id,
                'tahun_anggaran' => 2025,
                'periode' => 'Reguler 2025',
                'nama_jabatan' => 'Analis Kepegawaian Ahli Pertama',
                'jenjang_jabatan' => 'Ahli Pertama (III/a)',
                'kualifikasi_pendidikan' => 'S-1 Ilmu Administrasi Negara / Manajemen SDM',
                'kebutuhan_abk' => 4,
                'bezetting_riil' => 4,
                'kuota_lowong' => 0,
                'status' => 'terisi',
                'keterangan' => 'Telah terisi penuh melalui Mutasi Internal Baperjakat Periode November 2025.',
            ];
            $formasiList[] = [
                'id_unit_kerja' => $bkpsdm->id,
                'tahun_anggaran' => 2025,
                'periode' => 'Reguler 2025',
                'nama_jabatan' => 'Pengelola Sistem Informasi Kepegawaian',
                'jenjang_jabatan' => 'Pelaksana / Terampil (II/c)',
                'kualifikasi_pendidikan' => 'D-III Teknik Informatika / Manajemen Informatika',
                'kebutuhan_abk' => 2,
                'bezetting_riil' => 2,
                'kuota_lowong' => 0,
                'status' => 'terisi',
                'keterangan' => 'Terisi dari alokasi CPNS formasi 2024/2025.',
            ];
        }

        if ($haurgeulis) {
            $formasiList[] = [
                'id_unit_kerja' => $haurgeulis->id,
                'tahun_anggaran' => 2025,
                'periode' => 'Reguler 2025',
                'nama_jabatan' => 'Pengadministrasi Pelayanan Paten',
                'jenjang_jabatan' => 'Pelaksana (II/b - II/d)',
                'kualifikasi_pendidikan' => 'SLTA / D-III Semua Jurusan',
                'kebutuhan_abk' => 3,
                'bezetting_riil' => 3,
                'kuota_lowong' => 0,
                'status' => 'terisi',
                'keterangan' => 'Penempatan rotasi wilayah barat tahun 2025.',
            ];
        }

        if ($rsud) {
            $formasiList[] = [
                'id_unit_kerja' => $rsud->id,
                'tahun_anggaran' => 2025,
                'periode' => 'Reguler 2025',
                'nama_jabatan' => 'Perawat Ahli Pertama',
                'jenjang_jabatan' => 'Ahli Pertama (III/a)',
                'kualifikasi_pendidikan' => 'Profesi Ners / S-1 Keperawatan',
                'kebutuhan_abk' => 25,
                'bezetting_riil' => 25,
                'kuota_lowong' => 0,
                'status' => 'terisi',
                'keterangan' => 'Pemenuhan tenaga fungsional nakes 2025.',
            ];
        }

        // =========================================================================
        // TAHUN ANGGARAN 2026 (AKTIF BERJALAN - SEDANG DIBUKA UNTUK MUTASI MASUK)
        // =========================================================================
        if ($bkpsdm) {
            $formasiList[] = [
                'id_unit_kerja' => $bkpsdm->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Analis SDM Aparatur Ahli Muda',
                'jenjang_jabatan' => 'Ahli Muda (III/c - III/d)',
                'kualifikasi_pendidikan' => 'S-1 / S-2 Manajemen / Administrasi Publik / Hukum',
                'kebutuhan_abk' => 5,
                'bezetting_riil' => 4,
                'kuota_lowong' => 1,
                'status' => 'terbuka',
                'keterangan' => 'Kebutuhan tim penataan mutasi dan pengembangan karir BKPSDM.',
            ];
            $formasiList[] = [
                'id_unit_kerja' => $bkpsdm->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Pranata Komputer Ahli Pertama',
                'jenjang_jabatan' => 'Ahli Pertama (III/a - III/b)',
                'kualifikasi_pendidikan' => 'S-1 Ilmu Komputer / Teknik Informatika / Sistem Informasi',
                'kebutuhan_abk' => 3,
                'bezetting_riil' => 2,
                'kuota_lowong' => 1,
                'status' => 'terbuka',
                'keterangan' => 'Pengembangan arsitektur SPBE & Sistem Geospasial Kepegawaian.',
            ];
        }

        if ($jatibarang) {
            $formasiList[] = [
                'id_unit_kerja' => $jatibarang->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Pengelola Kepegawaian & Kinerja',
                'jenjang_jabatan' => 'Mahir / Terampil (II/c - III/b)',
                'kualifikasi_pendidikan' => 'D-III / S-1 Manajemen / Administrasi Negara',
                'kebutuhan_abk' => 3,
                'bezetting_riil' => 1,
                'kuota_lowong' => 2,
                'status' => 'terbuka',
                'keterangan' => 'Penguatan pengelolaan administrasi kepegawaian kecamatan.',
            ];
            $formasiList[] = [
                'id_unit_kerja' => $jatibarang->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Pengadministrasi Perizinan & Layanan Umum',
                'jenjang_jabatan' => 'Pelaksana (II/a - II/c)',
                'kualifikasi_pendidikan' => 'D-III / S-1 Semua Jurusan',
                'kebutuhan_abk' => 4,
                'bezetting_riil' => 3,
                'kuota_lowong' => 1,
                'status' => 'terbuka',
                'keterangan' => 'Pelayanan administrasi terpadu kecamatan Jatibarang.',
            ];
        }

        if ($haurgeulis) {
            $formasiList[] = [
                'id_unit_kerja' => $haurgeulis->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Pengelola Pelayanan Umum & Kependudukan',
                'jenjang_jabatan' => 'Pelaksana / Mahir (II/c - III/a)',
                'kualifikasi_pendidikan' => 'D-III / S-1 Administrasi Publik / Sosial / Komputer',
                'kebutuhan_abk' => 5,
                'bezetting_riil' => 3,
                'kuota_lowong' => 2,
                'status' => 'terbuka',
                'keterangan' => 'Pelayanan terpadu kecamatan wilayah barat daya.',
            ];
            $formasiList[] = [
                'id_unit_kerja' => $haurgeulis->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Pengelola Keuangan & Anggaran',
                'jenjang_jabatan' => 'Ahli Pertama (III/a - III/b)',
                'kualifikasi_pendidikan' => 'S-1 Akuntansi / Manajemen Keuangan',
                'kebutuhan_abk' => 3,
                'bezetting_riil' => 1,
                'kuota_lowong' => 2,
                'status' => 'terbuka',
                'keterangan' => 'Percepatan pelaporan keuangan dan aset kecamatan.',
            ];
        }

        if ($patrol) {
            $formasiList[] = [
                'id_unit_kerja' => $patrol->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Pengawas Tata Ruang & Ketentraman Wilayah',
                'jenjang_jabatan' => 'Pelaksana / Mahir (II/c - III/b)',
                'kualifikasi_pendidikan' => 'D-III / S-1 Teknik Sipil / Perencanaan / Hukum',
                'kebutuhan_abk' => 3,
                'bezetting_riil' => 2,
                'kuota_lowong' => 1,
                'status' => 'terbuka',
                'keterangan' => 'Pengawasan tata ruang koridor pantura barat.',
            ];
        }

        if ($rsud) {
            $formasiList[] = [
                'id_unit_kerja' => $rsud->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Administrator Kesehatan Ahli Pertama',
                'jenjang_jabatan' => 'Ahli Pertama (III/a - III/b)',
                'kualifikasi_pendidikan' => 'S-1 Kesehatan Masyarakat / Manajemen RS',
                'kebutuhan_abk' => 6,
                'bezetting_riil' => 3,
                'kuota_lowong' => 3,
                'status' => 'terbuka',
                'keterangan' => 'Kebutuhan akreditasi rumah sakit dan penjaminan mutu mutu RSUD.',
            ];
            $formasiList[] = [
                'id_unit_kerja' => $rsud->id,
                'tahun_anggaran' => 2026,
                'periode' => 'Semester 1 - 2026',
                'nama_jabatan' => 'Pranata Laboratorium Kesehatan',
                'jenjang_jabatan' => 'Terampil / Mahir (II/c - III/a)',
                'kualifikasi_pendidikan' => 'D-III / D-IV Analis Kesehatan / TLM',
                'kebutuhan_abk' => 5,
                'bezetting_riil' => 3,
                'kuota_lowong' => 2,
                'status' => 'terbuka',
                'keterangan' => 'Operasional laboratorium diagnostik RSUD.',
            ];
        }

        // Simpan seluruh data formasi
        foreach ($formasiList as $data) {
            FormasiJabatan::create($data);
        }

        // Sinkronkan kuota_formasi_kosong di unit_kerja untuk tahun 2026
        $units = UnitKerja::all();
        foreach ($units as $u) {
            $u->syncKuotaFormasi(2026);
        }
    }
}

