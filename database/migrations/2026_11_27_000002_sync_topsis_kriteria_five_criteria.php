<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Menyelaraskan 5 kriteria standar TOPSIS untuk Mode 1 (PNS to Dinas)
     * dan Mode 2 (Dinas to PNS) sesuai ketentuan BKPSDM:
     * C1: Jarak (Cost, 35%)
     * C2: Masa Jabatan (Benefit, 20%)
     * C3: Masa Kerja (Benefit, 15%)
     * C4: Formasi Jabatan (Benefit, 15%)
     * C5: Kebutuhan Pegawai Unit Kerja Saat Ini (Benefit, 15%)
     */
    public function up(): void
    {
        $now = now();

        $modes = [
            'pns_to_dinas' => [
                [
                    'kode_kriteria' => 'C1',
                    'parameter_key' => 'jarak_dinas',
                    'nama_kriteria' => 'Jarak Domisili ke Unit Tujuan (Km)',
                    'bobot' => 0.35,
                    'atribut' => 'cost',
                    'keterangan' => 'Program mendekatkan penugasan dengan domisili PNS (bobot prioritas terbesar).',
                ],
                [
                    'kode_kriteria' => 'C2',
                    'parameter_key' => 'masa_jabatan',
                    'nama_kriteria' => 'Masa Jabatan Saat Ini (Tahun)',
                    'bobot' => 0.20,
                    'atribut' => 'benefit',
                    'keterangan' => 'Syarat mutlak minimal > 2 tahun (dihitung dari TMT SK Jabatan).',
                ],
                [
                    'kode_kriteria' => 'C3',
                    'parameter_key' => 'masa_kerja',
                    'nama_kriteria' => 'Masa Kerja PNS (Tahun)',
                    'bobot' => 0.15,
                    'atribut' => 'benefit',
                    'keterangan' => 'Pembanding senioritas dalam daerah; untuk mutasi keluar daerah wajib > 5 tahun (dinamis).',
                ],
                [
                    'kode_kriteria' => 'C4',
                    'parameter_key' => 'formasi_jabatan',
                    'nama_kriteria' => 'Formasi Jabatan (Kesesuaian & Kuota)',
                    'bobot' => 0.15,
                    'atribut' => 'benefit',
                    'keterangan' => 'Mutlak: Fungsional wajib sama nama & jenjang jabatan; Pelaksana fleksibel sama/turun/naik (jika naik tingkat pendidikan wajib sesuai); kuota lowong > 0.',
                ],
                [
                    'kode_kriteria' => 'C5',
                    'parameter_key' => 'kebutuhan_unit_asal',
                    'nama_kriteria' => 'Kebutuhan Pegawai Unit Kerja Saat Ini (%)',
                    'bobot' => 0.15,
                    'atribut' => 'benefit',
                    'keterangan' => 'Persentase Bezetting/ABK unit asal: >=100% Lengkap (3), >=50% Cukup (2), <50% Kurang (1).',
                ],
            ],
            'dinas_to_pns' => [
                [
                    'kode_kriteria' => 'C1',
                    'parameter_key' => 'jarak_pns',
                    'nama_kriteria' => 'Jarak Domisili PNS ke Dinas (Km)',
                    'bobot' => 0.35,
                    'atribut' => 'cost',
                    'keterangan' => 'Program mendekatkan penugasan dengan domisili PNS (bobot prioritas terbesar).',
                ],
                [
                    'kode_kriteria' => 'C2',
                    'parameter_key' => 'masa_jabatan_pns',
                    'nama_kriteria' => 'Masa Jabatan PNS (Tahun)',
                    'bobot' => 0.20,
                    'atribut' => 'benefit',
                    'keterangan' => 'Syarat mutlak minimal > 2 tahun pada jabatan saat ini.',
                ],
                [
                    'kode_kriteria' => 'C3',
                    'parameter_key' => 'masa_kerja_pns',
                    'nama_kriteria' => 'Masa Kerja PNS (Tahun)',
                    'bobot' => 0.15,
                    'atribut' => 'benefit',
                    'keterangan' => 'Senioritas masa kerja PNS sejak TMT CPNS.',
                ],
                [
                    'kode_kriteria' => 'C4',
                    'parameter_key' => 'formasi_jabatan_dinas',
                    'nama_kriteria' => 'Formasi Jabatan Tersedia di Dinas',
                    'bobot' => 0.15,
                    'atribut' => 'benefit',
                    'keterangan' => 'Fungsional wajib sama nama & jenjang jabatan; Pelaksana fleksibel sama/turun/naik (jika naik tingkat pendidikan wajib sesuai); kuota lowong > 0.',
                ],
                [
                    'kode_kriteria' => 'C5',
                    'parameter_key' => 'kebutuhan_unit_asal',
                    'nama_kriteria' => 'Kebutuhan Pegawai Unit Kerja Asal (%)',
                    'bobot' => 0.15,
                    'atribut' => 'benefit',
                    'keterangan' => 'Persentase Bezetting/ABK unit asal: >=100% Lengkap (3), >=50% Cukup (2), <50% Kurang (1).',
                ],
            ],
        ];

        foreach ($modes as $mode => $items) {
            foreach ($items as $item) {
                DB::table('topsis_kriteria')->updateOrInsert(
                    [
                        'kategori_mode' => $mode,
                        'kode_kriteria' => $item['kode_kriteria'],
                    ],
                    [
                        'parameter_key' => $item['parameter_key'],
                        'nama_kriteria' => $item['nama_kriteria'],
                        'bobot' => $item['bobot'],
                        'atribut' => $item['atribut'],
                        'is_active' => true,
                        'keterangan' => $item['keterangan'],
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Tidak menghapus kriteria agar riwayat konfigurasi tetap aman.
    }
};
