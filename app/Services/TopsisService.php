<?php

namespace App\Services;

class TopsisService
{
    /**
     * Menghitung nilai preferensi TOPSIS (Technique for Order of Preference by Similarity to Ideal Solution).
     *
     * @param array $alternatives Array alternatif, contoh:
     *   [
     *     ['id' => 1, 'nama' => 'Budi', 'c1_jarak_baru' => 3.2, 'c2_masa_kerja' => 8, 'c3_golongan' => 3, 'c4_hemat_jarak' => 18.5],
     *     ...
     *   ]
     * @param array $criteria Array kriteria & pembobotan dinamis, contoh:
     *   [
     *     'c1_jarak_baru' => ['bobot' => 0.35, 'atribut' => 'cost'],
     *     'c2_masa_kerja' => ['bobot' => 0.25, 'atribut' => 'benefit'],
     *     'c3_golongan'   => ['bobot' => 0.15, 'atribut' => 'benefit'],
     *     'c4_hemat_jarak'=> ['bobot' => 0.25, 'atribut' => 'benefit'],
     *   ]
     * @return array Hasil lengkap: ranking, detail matriks ternormalisasi, matriks terbobot, solusi ideal, dan nilai preferensi Vi.
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

        // 1. Hitung pembagi akar jumlah kuadrat untuk normalisasi tiap kriteria
        $divisors = [];
        foreach ($criteria as $code => $crit) {
            $sumSquares = 0.0;
            foreach ($alternatives as $alt) {
                $val = (float)($alt[$code] ?? 0.0);
                $sumSquares += ($val * $val);
            }
            $divisors[$code] = ($sumSquares > 0) ? sqrt($sumSquares) : 1.0;
        }

        // 2. Matriks Ternormalisasi (R) & Matriks Terbobot (Y)
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

        // 3. Solusi Ideal Positif (A+) dan Solusi Ideal Negatif (A-)
        $idealPositive = [];
        $idealNegative = [];

        foreach ($criteria as $code => $crit) {
            $columnY = array_column($matrixY, $code);
            $isCost = (strtolower($crit['atribut']) === 'cost');

            if ($isCost) {
                // Untuk Cost: A+ adalah nilai minimum, A- adalah nilai maksimum
                $idealPositive[$code] = min($columnY);
                $idealNegative[$code] = max($columnY);
            } else {
                // Untuk Benefit: A+ adalah nilai maksimum, A- adalah nilai minimum
                $idealPositive[$code] = max($columnY);
                $idealNegative[$code] = min($columnY);
            }
        }

        // 4. Jarak Separasi (D+ dan D-) & Nilai Preferensi (Vi)
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

            // Rumus Vi = D- / (D+ + D-)
            $denominator = $dPositive + $dNegative;
            $vi = ($denominator > 0) ? ($dNegative / $denominator) : 0.0;

            $results[] = [
                'alternative' => $alt,
                'd_positive' => round($dPositive, 6),
                'd_negative' => round($dNegative, 6),
                'v_value' => round($vi, 6),
            ];
        }

        // 5. Perangkingan Descending berdasarkan nilai Vi
        usort($results, function ($a, $b) {
            return $b['v_value'] <=> $a['v_value'];
        });

        // Berikan nomor rank (1..m)
        foreach ($results as $index => &$res) {
            $res['rank'] = $index + 1;
        }
        unset($res);

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
