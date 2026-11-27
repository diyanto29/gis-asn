<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyelaraskan struktur kriteria TOPSIS sesuai ketentuan akhir BKPSDM:
     * - Mode 1 (pns_to_dinas) : 5 Kriteria (C1 Jarak, C2 Masa Jabatan + subkriteria,
     *                           C3 Masa Kerja + subkriteria, C4 Formasi Jabatan,
     *                           C5 Kebutuhan Pegawai Unit Kerja Saat Ini) -> Total 100%
     * - Mode 2 (dinas_to_pns) : 4 Kriteria (C1 Jarak, C2 Masa Jabatan + subkriteria,
     *                           C3 Masa Kerja + subkriteria,
     *                           C4 Kebutuhan Pegawai Unit Kerja Saat Ini) -> Total 100%
     */
    public function up(): void
    {
        $now = now();

        $mode1 = [
            [
                'kode_kriteria' => 'C1',
                'parameter_key' => 'jarak_dinas',
                'nama_kriteria' => 'Jarak Domisili ke Tempat Kerja (Km)',
                'bobot' => 0.35,
                'atribut' => 'cost',
                'keterangan' => 'Melihat seberapa jauh atau dekat antara domisili PNS dengan tempat kerja tujuan.',
            ],
            [
                'kode_kriteria' => 'C2',
                'parameter_key' => 'masa_jabatan',
                'nama_kriteria' => 'Masa Jabatan (TMT Jabatan)',
                'bobot' => 0.20,
                'atribut' => 'benefit',
                'keterangan' => 'Menghitung lama menjabat dari TMT Jabatan. Subkriteria: memenuhi batas minimal masa jabatan (default >= 2 tahun, dinamis).',
            ],
            [
                'kode_kriteria' => 'C3',
                'parameter_key' => 'masa_kerja',
                'nama_kriteria' => 'Masa Kerja (TMT CPNS)',
                'bobot' => 0.15,
                'atribut' => 'benefit',
                'keterangan' => 'Menghitung lama kerja dari TMT CPNS. Subkriteria: memenuhi batas minimal keluar Kabupaten XYZ (default > 5 tahun, dinamis).',
            ],
            [
                'kode_kriteria' => 'C4',
                'parameter_key' => 'formasi_jabatan',
                'nama_kriteria' => 'Formasi Jabatan',
                'bobot' => 0.15,
                'atribut' => 'benefit',
                'keterangan' => 'Fungsional: wajib sama nama & jenjang jabatan + tersedia. Pelaksana: fleksibel sama/turun/naik dengan tingkat pendidikan sesuai + tersedia.',
            ],
            [
                'kode_kriteria' => 'C5',
                'parameter_key' => 'kebutuhan_unit_asal',
                'nama_kriteria' => 'Kebutuhan Pegawai Unit Kerja Saat Ini',
                'bobot' => 0.15,
                'atribut' => 'benefit',
                'keterangan' => 'Persentase Bezetting/ABK unit saat ini: ==100% Lengkap (3), >=50% Cukup (2), <50% Kurang (1). Contoh: Bezetting 10, ABK 12, Lowong -2 = Cukup.',
            ],
        ];

        $mode2 = [
            [
                'kode_kriteria' => 'C1',
                'parameter_key' => 'jarak_pns',
                'nama_kriteria' => 'Jarak Domisili ke Tempat Kerja (Km)',
                'bobot' => 0.35,
                'atribut' => 'cost',
                'keterangan' => 'Melihat seberapa jauh atau dekat antara domisili PNS dengan dinas tujuan.',
            ],
            [
                'kode_kriteria' => 'C2',
                'parameter_key' => 'masa_jabatan_pns',
                'nama_kriteria' => 'Masa Jabatan (TMT Jabatan)',
                'bobot' => 0.25,
                'atribut' => 'benefit',
                'keterangan' => 'Menghitung lama menjabat dari TMT Jabatan. Subkriteria: memenuhi batas minimal masa jabatan (default >= 2 tahun, dinamis).',
            ],
            [
                'kode_kriteria' => 'C3',
                'parameter_key' => 'masa_kerja_pns',
                'nama_kriteria' => 'Masa Kerja (TMT CPNS)',
                'bobot' => 0.20,
                'atribut' => 'benefit',
                'keterangan' => 'Menghitung lama kerja dari TMT CPNS. Subkriteria: memenuhi batas minimal keluar Kabupaten XYZ (default > 5 tahun, dinamis).',
            ],
            [
                'kode_kriteria' => 'C4',
                'parameter_key' => 'kebutuhan_unit_asal',
                'nama_kriteria' => 'Kebutuhan Pegawai Unit Kerja Saat Ini',
                'bobot' => 0.20,
                'atribut' => 'benefit',
                'keterangan' => 'Persentase Bezetting/ABK unit asal: ==100% Lengkap (3), >=50% Cukup (2), <50% Kurang (1).',
            ],
        ];

        foreach ($mode1 as $item) {
            DB::table('topsis_kriteria')->updateOrInsert(
                ['kategori_mode' => 'pns_to_dinas', 'kode_kriteria' => $item['kode_kriteria']],
                array_merge($item, ['is_active' => true, 'updated_at' => $now, 'created_at' => $now])
            );
        }

        // Mode 2 hanya menggunakan 4 kriteria (C1-C4)
        DB::table('topsis_kriteria')
            ->where('kategori_mode', 'dinas_to_pns')
            ->whereNotIn('kode_kriteria', ['C1', 'C2', 'C3', 'C4'])
            ->delete();

        foreach ($mode2 as $item) {
            DB::table('topsis_kriteria')->updateOrInsert(
                ['kategori_mode' => 'dinas_to_pns', 'kode_kriteria' => $item['kode_kriteria']],
                array_merge($item, ['is_active' => true, 'updated_at' => $now, 'created_at' => $now])
            );
        }
    }

    public function down(): void
    {
        // Tidak menghapus kriteria agar riwayat konfigurasi tetap aman.
    }
};
