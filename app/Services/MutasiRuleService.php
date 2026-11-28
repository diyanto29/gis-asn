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
     * Mengambil aturan mutasi & subkriteria dinamis TOPSIS (C1 Jarak, C2 Masa Jabatan,
     * C3 Masa Kerja Keluar Daerah, C5/C4 Kebutuhan Pegawai Unit Saat Ini).
     */
    public static function getRules(): array
    {
        $defaults = [
            'min_masa_jabatan_tahun' => 2.0,
            'min_masa_kerja_keluar_tahun' => 5,
            'radius_terdekat_km' => 10.0,
            'jarak_dekat_max_km' => 5.0,
            'jarak_jauh_min_km' => 20.0,
            'unit_lengkap_min_pct' => 100.0,
            'unit_cukup_min_pct' => 50.0,
            'unit_lengkap_skor' => 3.0,
            'unit_cukup_skor' => 2.0,
            'unit_kurang_skor' => 1.0,
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
            'radius_terdekat_km' => max(1.0, (float)($decoded['radius_terdekat_km'] ?? $defaults['radius_terdekat_km'])),
            'jarak_dekat_max_km' => max(0.5, (float)($decoded['jarak_dekat_max_km'] ?? $defaults['jarak_dekat_max_km'])),
            'jarak_jauh_min_km' => max(1.0, (float)($decoded['jarak_jauh_min_km'] ?? $defaults['jarak_jauh_min_km'])),
            'unit_lengkap_min_pct' => max(1.0, (float)($decoded['unit_lengkap_min_pct'] ?? $defaults['unit_lengkap_min_pct'])),
            'unit_cukup_min_pct' => max(1.0, (float)($decoded['unit_cukup_min_pct'] ?? $defaults['unit_cukup_min_pct'])),
            'unit_lengkap_skor' => max(0.1, (float)($decoded['unit_lengkap_skor'] ?? $defaults['unit_lengkap_skor'])),
            'unit_cukup_skor' => max(0.1, (float)($decoded['unit_cukup_skor'] ?? $defaults['unit_cukup_skor'])),
            'unit_kurang_skor' => max(0.1, (float)($decoded['unit_kurang_skor'] ?? $defaults['unit_kurang_skor'])),
        ];
    }

    /**
     * Menyimpan perubahan aturan & subkriteria dinamis dari Admin BKPSDM (mendukung partial update).
     */
    public static function saveRules(float $minMasaJabatan, int $minMasaKerjaKeluar, array $extra = []): array
    {
        $current = self::getRules();
        $data = array_merge($current, $extra, [
            'min_masa_jabatan_tahun' => round(max(0.0, $minMasaJabatan), 1),
            'min_masa_kerja_keluar_tahun' => max(1, $minMasaKerjaKeluar),
        ]);

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
        $rules = self::getRules();
        $lowong = $bezetting - $abk;
        $persentase = $abk > 0 ? round(($bezetting / $abk) * 100, 1) : 100.0;

        if ($persentase >= $rules['unit_lengkap_min_pct']) {
            $status = 'Lengkap';
            $skor = (float)$rules['unit_lengkap_skor'];
            $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
        } elseif ($persentase >= $rules['unit_cukup_min_pct']) {
            $status = 'Cukup';
            $skor = (float)$rules['unit_cukup_skor'];
            $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
        } else {
            $status = 'Kurang';
            $skor = (float)$rules['unit_kurang_skor'];
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
     * Memetakan string tingkat/kualifikasi pendidikan ke level numerik standar (1 s/d 9).
     *
     * Level Pendidikan:
     *   1 = SD, 2 = SMP/SLTP, 3 = SMA/SLTA/SMK/STM/SMEA,
     *   4 = D-I, 5 = D-II, 6 = D-III, 7 = D-IV/S-1/Profesi, 8 = S-2, 9 = S-3.
     *
     * Jika string kualifikasi memuat beberapa pilihan (misal "D-III / S-1 ..."),
     * mengembalikan level MINIMUM yang dipersyaratkan jabatan ($mode = 'min'),
     * sedangkan untuk profil ASN mengembalikan level TERTINGGI ($mode = 'max').
     */
    public function deteksiLevelPendidikan(?string $teks, string $mode = 'max'): int
    {
        $str = strtoupper(trim((string)$teks));
        if ($str === '') {
            return 0;
        }

        $patterns = [
            9 => '/\b(S-?3|DOKTOR|STRATA\s*3)\b/',
            8 => '/\b(S-?2|MAGISTER|PASCASARJANA|STRATA\s*2)\b/',
            7 => '/\b(S-?1|D-?IV|D4|DIPLOMA\s*IV|SARJANA(?!\s*MUDA)|PROFESI|NERS|DOKTER|APOTEKER)\b/',
            6 => '/\b(D-?III|D3|DIPLOMA\s*III|SARJANA\s*MUDA)\b/',
            5 => '/\b(D-?II|A-?II|D2|DIPLOMA\s*II)\b/',
            4 => '/\b(D-?I|D1|DIPLOMA\s*I)\b/',
            3 => '/\b(SLTA|SMA|SMU|SMK|STM|SMEA|PAKET\s*C|SEKOLAH\s*MENENGAH\s*ATAS)\b/',
            2 => '/\b(SLTP|SMP|MTS)\b/',
            1 => '/\b(SD|SEKOLAH\s*DASAR)\b/',
        ];

        $matched = [];
        foreach ($patterns as $level => $regex) {
            if (preg_match($regex, $str)) {
                $matched[] = $level;
            }
        }

        if (empty($matched)) {
            return 0;
        }

        return $mode === 'min' ? min($matched) : max($matched);
    }

    /**
     * Menghasilkan deskripsi ringkas aturan matching jabatan yang aktif untuk seorang ASN.
     */
    public function deskripsiAturanMatch(AsnMaster $asn): string
    {
        $jenis = strtolower(trim((string)$asn->jenis_jabatan));
        if ($jenis === 'pelaksana') {
            $kls = $asn->masterJabatan?->kelas_jabatan ?? '-';
            $pend = $asn->tingkat_pendidikan ?: ($asn->pendidikan_terakhir ?: '-');
            return "Jabatan Pelaksana (Kelas {$kls}, Pend. {$pend}) — Fleksibel (Sama/Turun/Naik dengan Tingkat Pendidikan Sesuai)";
        }

        $jenjang = $asn->masterJabatan?->jenjang_jabatan;
        $labelJenjang = $jenjang ? " — Jenjang {$jenjang}" : '';
        return "Jabatan Fungsional — Wajib Sama Nama Jabatan ({$asn->jabatan}){$labelJenjang}";
    }

    /**
     * Mencari formasi terbuka (kuota_lowong > 0) yang COCOK untuk seorang PNS.
     *
     * Aturan Matching Mutlak (Poin 1 & 2):
     * - Jabatan Fungsional : Wajib sama NAMA JABATAN dan JENJANG JABATAN + kuota_lowong > 0.
     * - Jabatan Pelaksana  : Fleksibel pada jabatan pelaksana (kelas jabatan bisa sama, turun, atau naik),
     *                        NAMUN jika NAIK kelas jabatan maka tingkat pendidikan PNS wajib sesuai
     *                        dengan kualifikasi pendidikan jabatan tujuan + kuota_lowong > 0.
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
        $jenjangAsn = strtolower(trim((string)($asn->masterJabatan?->jenjang_jabatan ?? '')));

        $eduAsn = max(
            $this->deteksiLevelPendidikan($asn->tingkat_pendidikan, 'max'),
            $this->deteksiLevelPendidikan($asn->pendidikan_terakhir, 'max')
        );

        return $openFormasis->filter(function (FormasiJabatan $f) use ($asn, $jenisAsn, $kelasAsn, $namaJabatanAsn, $jenjangAsn, $eduAsn) {
            if ((int)$f->kuota_lowong <= 0) {
                return false;
            }

            $jenisFormasi = strtolower(trim((string)($f->jenis_jabatan ?: ($f->masterJabatan?->jenis_jabatan ?? ''))));

            // -----------------------------------------------------------------
            // JABATAN PELAKSANA: Fleksibel (Sama / Turun / Naik + Pendidikan Sesuai)
            // -----------------------------------------------------------------
            if ($jenisAsn === 'pelaksana') {
                if ($jenisFormasi !== 'pelaksana') {
                    return false;
                }

                $kelasFormasi = $f->masterJabatan?->kelas_jabatan;

                // Level pendidikan minimum yang dipersyaratkan formasi tujuan
                $kualifFormasi = trim((string)($f->kualifikasi_pendidikan ?: ($f->masterJabatan?->kualifikasi_pendidikan ?? '')));
                $eduFormasiMin = $this->deteksiLevelPendidikan($kualifFormasi, 'min');

                if ($kelasAsn !== null && $kelasFormasi !== null) {
                    // Sama atau Turun kelas jabatan: diperbolehkan langsung (fleksibel)
                    if ((int)$kelasFormasi <= (int)$kelasAsn) {
                        return true;
                    }

                    // Naik kelas jabatan: tingkat pendidikan PNS wajib memenuhi kualifikasi jabatan tujuan
                    if ($eduFormasiMin > 0 && $eduAsn > 0) {
                        return $eduAsn >= $eduFormasiMin;
                    }

                    // Jika data kualifikasi belum terisi, tidak boleh naik tanpa verifikasi pendidikan
                    return false;
                }

                // Fallback jika kelas jabatan tidak tersedia: pastikan pendidikan sesuai
                if ($eduFormasiMin > 0 && $eduAsn > 0) {
                    return $eduAsn >= $eduFormasiMin;
                }

                return ($asn->id_master_jabatan && (int)$f->id_master_jabatan === (int)$asn->id_master_jabatan)
                    || strtolower(trim((string)$f->nama_jabatan)) === $namaJabatanAsn;
            }

            // -----------------------------------------------------------------
            // JABATAN FUNGSIONAL: Wajib SAMA Nama Jabatan DAN Jenjang Jabatan
            // -----------------------------------------------------------------
            if ($jenisFormasi !== '' && $jenisFormasi !== 'fungsional') {
                return false;
            }

            $namaFormasi = strtolower(trim((string)($f->nama_jabatan ?: ($f->masterJabatan?->nama_jabatan ?? ''))));
            $jenjangFormasi = strtolower(trim((string)($f->jenjang_jabatan ?: ($f->masterJabatan?->jenjang_jabatan ?? ''))));

            if ($asn->id_master_jabatan && $f->id_master_jabatan && (int)$f->id_master_jabatan === (int)$asn->id_master_jabatan) {
                return true;
            }

            $namaSama = ($namaFormasi !== '' && $namaFormasi === $namaJabatanAsn);
            $jenjangSama = ($jenjangAsn === '' || $jenjangFormasi === '' || $jenjangFormasi === $jenjangAsn);

            return $namaSama && $jenjangSama;
        })->values();
    }

    /**
     * Mengevaluasi status & skor Formasi Jabatan (Kriteria C4) pada suatu unit kerja tujuan
     * TANPA menyembunyikan unit yang formasinya penuh/tidak tersedia.
     *
     * Skor C4:
     * - 2.0 : Formasi cocok ADA & TERSEDIA (kuota_lowong > 0)
     * - 1.0 : Formasi cocok ADA namun PENUH (kuota_lowong = 0)
     * - 0.0 : Tidak ada formasi yang cocok pada unit tersebut
     *
     * @return array{status:string, skor:float, kuota_cocok:int, labels:array<int,string>, badge_class:string}
     */
    public function evaluasiFormasiUnitTujuan(AsnMaster $asn, Collection $unitFormasis): array
    {
        // 1. Cek formasi yang cocok & tersedia (kuota_lowong > 0)
        $openMatches = $this->cariFormasiCocok($asn, $unitFormasis);
        if ($openMatches->isNotEmpty()) {
            $kuota = (int)$openMatches->sum('kuota_lowong');
            $labels = $openMatches->map(function ($f) {
                $kls = $f->masterJabatan?->kelas_jabatan ? " [Kls {$f->masterJabatan->kelas_jabatan}]" : '';
                return "{$f->nama_jabatan}{$kls} ({$f->kuota_lowong} lowong)";
            })->values()->toArray();

            return [
                'status' => 'Tersedia',
                'skor' => 2.0,
                'kuota_cocok' => $kuota,
                'labels' => $labels,
                'badge_class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ];
        }

        // 2. Cek apakah jabatan cocok ada pada unit ini tetapi kuota sudah penuh (kuota_lowong == 0)
        $simulatedOpen = $unitFormasis->map(function (FormasiJabatan $f) {
            $clone = clone $f;
            $clone->kuota_lowong = 1;
            return $clone;
        });
        $fullMatches = $this->cariFormasiCocok($asn, $simulatedOpen);
        if ($fullMatches->isNotEmpty()) {
            $labels = $fullMatches->map(function ($f) {
                $kls = $f->masterJabatan?->kelas_jabatan ? " [Kls {$f->masterJabatan->kelas_jabatan}]" : '';
                return "{$f->nama_jabatan}{$kls} (Penuh)";
            })->values()->toArray();

            return [
                'status' => 'Penuh',
                'skor' => 1.0,
                'kuota_cocok' => 0,
                'labels' => $labels,
                'badge_class' => 'bg-amber-50 text-amber-700 border-amber-200',
            ];
        }

        // 3. Tidak ada formasi yang cocok pada unit ini
        return [
            'status' => 'Tidak Tersedia',
            'skor' => 0.0,
            'kuota_cocok' => 0,
            'labels' => [],
            'badge_class' => 'bg-slate-100 text-slate-600 border-slate-200',
        ];
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
