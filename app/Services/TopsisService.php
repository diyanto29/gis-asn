<?php

namespace App\Services;

/**
 * Class TopsisService
 *
 * Layanan Komputasi Sistem Pendukung Keputusan (SPK) menggunakan metode
 * TOPSIS (Technique for Order of Preference by Similarity to Ideal Solution)
 * yang dikembangkan oleh Hwang & Yoon (1981).
 *
 * Algoritma ini memilih alternatif terbaik yang memiliki jarak geometris terdekat
 * dengan Solusi Ideal Positif (A+) dan jarak terjauh dari Solusi Ideal Negatif (A-).
 *
 * Digunakan untuk:
 * 1. Tahap 1: Seleksi prioritas mutasi ASN pada unit kerja tertentu.
 * 2. Tahap 2: Simulasi rekomendasi penempatan unit kerja optimal bagi ASN.
 *
 * @package App\Services
 */
class TopsisService
{
    /**
     * Menghitung nilai preferensi relatif (Vi) dan pemeringkatan alternatif TOPSIS.
     *
     * FORMULASI MATEMATIS ILMIAH (BAB 3 METODOLOGI):
     * -------------------------------------------------------------
     * 1. Matriks Keputusan:
     *    X = [x_ij] berukuran m x n (m alternatif, n kriteria).
     *
     * 2. Pembagi Normalisasi Euclidean:
     *    |X_j| = sqrt( sum_{i=1}^{m} (x_ij)^2 )
     *
     * 3. Matriks Ternormalisasi (R):
     *    r_ij = x_ij / |X_j|
     *
     * 4. Matriks Ternormalisasi Terbobot (Y):
     *    y_ij = w_j * r_ij, dimana sum(w_j) = 1.00
     *
     * 5. Solusi Ideal Positif (A+) dan Solusi Ideal Negatif (A-):
     *    - Kriteria Benefit: y_j^+ = max(y_ij),  y_j^- = min(y_ij)
     *    - Kriteria Cost:    y_j^+ = min(y_ij),  y_j^- = max(y_ij)
     *
     * 6. Jarak Separasi Euclidean:
     *    D_i^+ = sqrt( sum_{j=1}^{n} (y_ij - y_j^+)^2 )
     *    D_i^- = sqrt( sum_{j=1}^{n} (y_ij - y_j^-)^2 )
     *
     * 7. Nilai Preferensi Relatif (Vi):
     *    V_i = D_i^- / (D_i^+ + D_i^-), dengan rentang nilai V_i in [0, 1].
     *    Alternatif dengan nilai V_i terbesar menempati peringkat tertinggi (Rank 1).
     *
     * @param array $alternatives Array alternatif keputusan, contoh:
     *   [
     *     ['id' => 1, 'nama' => 'Budi', 'c1_jarak' => 3.2, 'c2_masa_kerja' => 8, 'c3_golongan' => 3],
     *     ...
     *   ]
     * @param array $criteria Array kriteria & bobot, contoh:
     *   [
     *     'c1_jarak'      => ['bobot' => 0.40, 'atribut' => 'cost'],
     *     'c2_masa_kerja' => ['bobot' => 0.35, 'atribut' => 'benefit'],
     *     'c3_golongan'   => ['bobot' => 0.25, 'atribut' => 'benefit'],
     *   ]
     * @return array Hasil lengkap berupa daftar perangkingan, matriks R, matriks Y, A+, A-, dan jarak D+/D-.
     */
    public function calculate(array $alternatives, array $criteria): array
    {
        $m = count($alternatives);
        if ($m === 0) {
            return [
                'ranked' => [],
                'details' => [],
            ];
        }

        // =========================================================================
        // LANGKAH 1: Menghitung Pembagi Normalisasi Euclidean (|X_j|)
        // Formula: divisor_j = sqrt( sum_{i=1}^{m} (x_ij)^2 )
        // Berfungsi memetakan setiap dimensi kriteria ke skala seragam non-satuan.
        // =========================================================================
        $divisors = [];
        foreach ($criteria as $code => $crit) {
            $sumSquares = 0.0;
            foreach ($alternatives as $alt) {
                $val = (float)($alt[$code] ?? 0.0);
                $sumSquares += ($val * $val);
            }
            // Pencegahan pembagian dengan nol (division by zero guard)
            $divisors[$code] = ($sumSquares > 0) ? sqrt($sumSquares) : 1.0;
        }

        // =========================================================================
        // LANGKAH 2: Membentuk Matriks Ternormalisasi (R) & Matriks Terbobot (Y)
        // Formula: r_ij = x_ij / divisor_j
        // Formula: y_ij = r_ij * w_j
        // Nilai dibulatkan hingga 6 desimal untuk menjaga presisi floating point.
        // =========================================================================
        $matrixR = [];
        $matrixY = [];
        foreach ($alternatives as $i => $alt) {
            $rowR = [];
            $rowY = [];
            foreach ($criteria as $code => $crit) {
                $rawVal = (float)($alt[$code] ?? 0.0);
                $rVal = $rawVal / $divisors[$code];
                $yVal = $rVal * (float)$crit['bobot'];

                $rowR[$code] = round($rVal, 6);
                $rowY[$code] = round($yVal, 6);
            }
            $matrixR[$i] = $rowR;
            $matrixY[$i] = $rowY;
        }

        // =========================================================================
        // LANGKAH 3: Menentukan Solusi Ideal Positif (A+) dan Solusi Ideal Negatif (A-)
        // - Kriteria BENEFIT : A+ mengambil nilai MAX(y_ij), A- mengambil nilai MIN(y_ij)
        // - Kriteria COST    : A+ mengambil nilai MIN(y_ij), A- mengambil nilai MAX(y_ij)
        // =========================================================================
        $idealPositive = [];
        $idealNegative = [];

        foreach ($criteria as $code => $crit) {
            $columnY = array_column($matrixY, $code);
            $isCost = (strtolower($crit['atribut']) === 'cost');

            if ($isCost) {
                // Untuk kriteria biaya (jarak): nilai terkecil adalah yang paling ideal
                $idealPositive[$code] = min($columnY);
                $idealNegative[$code] = max($columnY);
            } else {
                // Untuk kriteria keuntungan (masa kerja, formasi): nilai terbesar adalah ideal
                $idealPositive[$code] = max($columnY);
                $idealNegative[$code] = min($columnY);
            }
        }

        // =========================================================================
        // LANGKAH 4: Menghitung Jarak Separasi Euclidean (D+ dan D-)
        // Formula D_i^+ = sqrt( sum_{j=1}^{n} (y_ij - y_j^+)^2 )
        // Formula D_i^- = sqrt( sum_{j=1}^{n} (y_ij - y_j^-)^2 )
        //
        // LANGKAH 5: Menghitung Nilai Preferensi Relatif (Vi)
        // Formula Vi = D_i^- / (D_i^+ + D_i^-)
        // Nilai Vi mendekati 1 menunjukkan kedekatan tinggi dengan solusi ideal positif.
        // =========================================================================
        $results = [];
        foreach ($alternatives as $i => $alt) {
            $distPositiveSq = 0.0;
            $distNegativeSq = 0.0;

            foreach ($criteria as $code => $crit) {
                $yVal = $matrixY[$i][$code];
                $distPositiveSq += pow($yVal - $idealPositive[$code], 2);
                $distNegativeSq += pow($yVal - $idealNegative[$code], 2);
            }

            $dPositive = sqrt($distPositiveSq);
            $dNegative = sqrt($distNegativeSq);

            // Perhitungan Vi dengan perlindungan jika denominator nol
            $denominator = $dPositive + $dNegative;
            $vi = ($denominator > 0) ? ($dNegative / $denominator) : 0.0;

            $results[] = [
                'alternative' => $alt,
                'd_positive' => round($dPositive, 6),
                'd_negative' => round($dNegative, 6),
                'v_value' => round($vi, 6),
            ];
        }

        // =========================================================================
        // LANGKAH 6: Perangkingan (Sorting Descending berdasarkan Nilai Vi)
        // Alternatif dengan skor Vi tertinggi menduduki peringkat Rank 1.
        // =========================================================================
        usort($results, function ($a, $b) {
            return $b['v_value'] <=> $a['v_value'];
        });

        // Pemberian nomor peringkat urut (1 .. m)
        foreach ($results as $index => &$res) {
            $res['rank'] = $index + 1;
        }
        unset($res);

        // Mengembalikan struktur data lengkap untuk keperluan visualisasi audit & skripsi
        return [
            'ranked' => $results,
            'details' => [
                'divisors' => $divisors,
                'matrix_r' => $matrixR,
                'matrix_y' => $matrixY,
                'ideal_positive' => $idealPositive,
                'ideal_negative' => $idealNegative,
            ],
        ];
    }
}

