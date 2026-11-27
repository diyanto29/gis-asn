<?php

namespace App\Services;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\UnitKerja;
use Illuminate\Support\Collection;

/**
 * Class MutasiRuleService
 *
 * Layanan evaluasi aturan bisnis mutasi ASN (BKPSDM) & penyusunan skor 5 kriteria TOPSIS:
 *
 * ATURAN MUTLAK MUTASI DALAM INSTANSI (BY SYSTEM — POIN 1 s/d 3):
 * --------------------------------------------------------------------------
 * 1. Kesesuaian Jabatan (Matching):
 *    - Jabatan Fungsional : Wajib cocok persis (exact match) dengan Master/Nama Jabatan di unit tujuan.
 *    - Jabatan Pelaksana  : Wajib cocok berdasarkan Kelas Jabatan (misal Kelas 6 -> Kelas 6).
 * 2. Ketersediaan Formasi : Formasi cocok di unit tujuan wajib memiliki kuota_lowong > 0 (ABK - Bezetting > 0).
 * 3. Masa Jabatan Minimal : Masa jabatan PNS saat ini wajib >= 2 tahun (terhitung dari tgl_jabatan).
 *
 * PERTIMBANGAN HAK PREROGATIF PIMPINAN (POIN 4 — KRITERIA C5):
 * --------------------------------------------------------------------------
 * 4. Kebutuhan Pegawai Unit Kerja Saat Ini (Unit Asal):
 *    Persentase Keterisian = (Bezetting / ABK) * 100%
 *    - Lengkap : >= 100% (Bezetting >= ABK, skor = 3)
 *    - Cukup   : >= 50% dan < 100% (skor = 2)
 *    - Kurang  : < 50% (skor = 1)
 *    Selisih Lowong = Bezetting - ABK (contoh: Bezetting 10, ABK 12 -> Lowong -2).
 *
 * ATURAN MUTASI KELUAR INSTANSI (DINAMIS / DAPAT DIUBAH ADMIN):
 * --------------------------------------------------------------------------
 * - Masa kerja minimal keluar instansi default > 5 tahun (dapat dikonfigurasi oleh Admin).
 *
 * @package App\Services
 */
class MutasiRuleService
{
    private const SETTINGS_RELATIVE_PATH = 'app/mutasi_rules.json';

    /**
     * Mengambil aturan mutasi dinamis (masa jabatan minimal & masa kerja keluar instansi).
     *
     * @return array{min_masa_jabatan_tahun: float, min_masa_kerja_keluar_tahun: int}
     */
    public static function getRules(): array
    {
        $defaults = [
            'min_masa_jabatan_tahun' => 2.0,
            'min_masa_kerja_keluar_tahun' => 5,
        ];

        $path = storage_path(self::SETTINGS_RELATIVE_PATH);
        if (!file_exists($path)) {
            return $defaults;
        }

        $decoded = json_decode((string)file_get_contents($path), true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return [
            'min_masa_jabatan_tahun' => max(0.0, (float)($decoded['min_masa_jabatan_tahun'] ?? $defaults['min_masa_jabatan_tahun'])),
            'min_masa_kerja_keluar_tahun' => max(1, (int)($decoded['min_masa_kerja_keluar_tahun'] ?? $defaults['min_masa_kerja_keluar_tahun'])),
        ];
    }

    /**
     * Menyimpan perubahan aturan mutasi dinamis dari Admin BKPSDM.
     */
    public static function saveRules(float $minMasaJabatan, int $minMasaKerjaKeluar): array
    {
        $data = [
            'min_masa_jabatan_tahun' => round(max(0.0, $minMasaJabatan), 1),
            'min_masa_kerja_keluar_tahun' => max(1, $minMasaKerjaKeluar),
        ];

        $path = storage_path(self::SETTINGS_RELATIVE_PATH);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));

        return $data;
    }

    /**
     * Menghitung Masa Jabatan PNS saat ini (dalam tahun, 2 desimal).
     */
    public function hitungMasaJabatanTahun(AsnMaster $asn): float
    {
        $tglJabatan = $asn->tgl_jabatan ?: $asn->tmt_kerja_unit;
        if (!$tglJabatan) {
            return 0.0;
        }

        $timestamp = is_object($tglJabatan) ? $tglJabatan->getTimestamp() : strtotime((string)$tglJabatan);
        if (!$timestamp) {
            return 0.0;
        }

        return max(0.0, round((time() - $timestamp) / (365.25 * 86400), 2));
    }

    /**
     * Menghitung Masa Kerja PNS sejak TMT CPNS (dalam tahun, 2 desimal).
     */
    public function hitungMasaKerjaTahun(AsnMaster $asn): float
    {
        if ($asn->tgl_cpns) {
            $timestamp = is_object($asn->tgl_cpns) ? $asn->tgl_cpns->getTimestamp() : strtotime((string)$asn->tgl_cpns);
            if ($timestamp) {
                return max(0.0, round((time() - $timestamp) / (365.25 * 86400), 2));
            }
        }

        return (float)($asn->masa_kerja_tahun ?: $asn->masa_pengabdian_tahun);
    }

    /**
     * Mengevaluasi kondisi Kebutuhan Pegawai Unit Kerja Saat Ini (Unit Asal) — Kriteria C5.
     *
     * Formula:
     *   persentase = (bezetting / abk) * 100
     *   lowong     = bezetting - abk  (negatif = kekurangan pegawai, contoh: 10 - 12 = -2)
     *
     * Klasifikasi:
     *   - >= 100% : Lengkap (skor 3.0)
     *   - >= 50%  : Cukup   (skor 2.0)
     *   - < 50%   : Kurang  (skor 1.0)
     *
     * @param UnitKerja|null $unit
     * @param array<int, array{bezetting:int, abk:int}>|null $unitStatsCache Cache agregasi formasi per unit_id
     * @return array{bezetting:int, abk:int, lowong:int, persentase:float, status:string, skor:float, badge_class:string}
     */
    public function evaluasiUnitAsal(?UnitKerja $unit, ?array $unitStatsCache = null): array
    {
        if (!$unit) {
            return [
                'bezetting' => 0,
                'abk' => 0,
                'lowong' => 0,
                'persentase' => 100.0,
                'status' => 'Lengkap',
                'skor' => 3.0,
                'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
        }

        if ($unitStatsCache !== null && isset($unitStatsCache[$unit->id])) {
            $bezetting = (int)$unitStatsCache[$unit->id]['bezetting'];
            $abk = (int)$unitStatsCache[$unit->id]['abk'];
        } else {
            $agg = FormasiJabatan::where('id_unit_kerja', $unit->id)
                ->selectRaw('COALESCE(SUM(bezetting_riil), 0) as total_bezetting, COALESCE(SUM(kebutuhan_abk), 0) as total_abk')
                ->first();
            $bezetting = (int)($agg->total_bezetting ?? 0);
            $abk = (int)($agg->total_abk ?? 0);

            if ($abk === 0 && ($unit->kebutuhan_abk > 0 || $unit->bezetting_saat_ini > 0)) {
                $bezetting = (int)$unit->bezetting_saat_ini;
                $abk = (int)$unit->kebutuhan_abk;
            }
        }

        // Lowong negatif menandakan kekurangan pegawai (contoh: Bezetting 10, ABK 12 -> Lowong -2)
        $lowong = $bezetting - $abk;
        $persentase = $abk > 0 ? round(($bezetting / $abk) * 100, 1) : 100.0;

        if ($persentase >= 100.0) {
            $status = 'Lengkap';
            $skor = 3.0;
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        } elseif ($persentase >= 50.0) {
            $status = 'Cukup';
            $skor = 2.0;
            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
        } else {
            $status = 'Kurang';
            $skor = 1.0;
            $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
        }

        return [
            'bezetting' => $bezetting,
            'abk' => $abk,
            'lowong' => $lowong,
            'persentase' => $persentase,
            'status' => $status,
            'skor' => $skor,
            'badge_class' => $badgeClass,
        ];
    }

    /**
     * Mengambil agregasi Bezetting & ABK seluruh unit kerja dalam 1 query agar cepat.
     *
     * @return array<int, array{bezetting:int, abk:int}>
     */
    public function getAllUnitStats(): array
    {
        $rows = FormasiJabatan::selectRaw('id_unit_kerja, COALESCE(SUM(bezetting_riil), 0) as total_bezetting, COALESCE(SUM(kebutuhan_abk), 0) as total_abk')
            ->groupBy('id_unit_kerja')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r->id_unit_kerja] = [
                'bezetting' => (int)$r->total_bezetting,
                'abk' => (int)$r->total_abk,
            ];
        }

        return $map;
    }

    /**
     * Mencari formasi terbuka (kuota_lowong > 0) yang COCOK untuk seorang PNS.
     *
     * Aturan Matching Mutlak (Poin 1 & 2):
     * - Jabatan Fungsional : Nama/Master Jabatan wajib sama persis + kuota_lowong > 0.
     * - Jabatan Pelaksana  : Kelas Jabatan wajib sama (contoh: Kelas 6 -> Kelas 6) + kuota_lowong > 0.
     *
     * @param AsnMaster $asn
     * @param Collection<int, FormasiJabatan> $openFormasis Formasi terbuka (harus sudah eager-load masterJabatan)
     * @return Collection<int, FormasiJabatan>
     */
    public function cariFormasiCocok(AsnMaster $asn, Collection $openFormasis): Collection
    {
        $jenisAsn = strtolower(trim((string)$asn->jenis_jabatan));
        $kelasAsn = $asn->masterJabatan?->kelas_jabatan;
        $namaJabatanAsn = strtolower(trim((string)$asn->jabatan));

        return $openFormasis->filter(function (FormasiJabatan $f) use ($asn, $jenisAsn, $kelasAsn, $namaJabatanAsn) {
            if ((int)$f->kuota_lowong <= 0) {
                return false;
            }

            $jenisFormasi = strtolower(trim((string)($f->jenis_jabatan ?: ($f->masterJabatan?->jenis_jabatan ?? ''))));

            // Khusus Jabatan Pelaksana: cukup cocok pada Kelas Jabatan yang sama
            if ($jenisAsn === 'pelaksana') {
                if ($jenisFormasi !== 'pelaksana') {
                    return false;
                }
                $kelasFormasi = $f->masterJabatan?->kelas_jabatan;
                if ($kelasAsn !== null && $kelasFormasi !== null) {
                    return (int)$kelasFormasi === (int)$kelasAsn;
                }
                // Fallback jika kelas belum terisi
                return ($asn->id_master_jabatan && (int)$f->id_master_jabatan === (int)$asn->id_master_jabatan)
                    || strtolower(trim((string)$f->nama_jabatan)) === $namaJabatanAsn;
            }

            // Jabatan Fungsional: wajib exact match Master Jabatan atau Nama Jabatan
            if ($asn->id_master_jabatan && $f->id_master_jabatan) {
                return (int)$f->id_master_jabatan === (int)$asn->id_master_jabatan;
            }

            return strtolower(trim((string)$f->nama_jabatan)) === $namaJabatanAsn;
        })->values();
    }

    /**
     * Mengecek syarat mutlak masa jabatan (>= min_masa_jabatan_tahun) & masa kerja keluar instansi.
     */
    public function cekKelayakanAsn(AsnMaster $asn): array
    {
        $rules = self::getRules();
        $masaJabatan = $this->hitungMasaJabatanTahun($asn);
        $masaKerja = $this->hitungMasaKerjaTahun($asn);

        $masaJabatanOk = $masaJabatan >= $rules['min_masa_jabatan_tahun'];
        $masaKerjaKeluarOk = $masaKerja >= $rules['min_masa_kerja_keluar_tahun'];

        return [
            'masa_jabatan_tahun' => $masaJabatan,
            'masa_kerja_tahun' => $masaKerja,
            'min_masa_jabatan_tahun' => $rules['min_masa_jabatan_tahun'],
            'min_masa_kerja_keluar_tahun' => $rules['min_masa_kerja_keluar_tahun'],
            'masa_jabatan_ok' => $masaJabatanOk,
            'masa_kerja_keluar_ok' => $masaKerjaKeluarOk,
            'alasan_tidak_layak' => !$masaJabatanOk
                ? "Masa jabatan saat ini ({$masaJabatan} tahun) belum memenuhi syarat mutlak minimal {$rules['min_masa_jabatan_tahun']} tahun."
                : null,
        ];
    }
}
