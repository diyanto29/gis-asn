<?php

namespace App\Services;

use App\Models\AsnMaster;

class SwapMatchService
{
    public function __construct(
        protected HaversineService $haversine
    ) {}

    /**
     * Mencari pasangan pegawai (Bipartite Mutual Matching) yang saling menguntungkan
     * jika bertukar tempat kerja (Zero Quota / Tanpa mengubah jumlah formasi ABK).
     *
     * @return array Daftar pasangan swap terurut dari efisiensi tertinggi
     */
    public function findMutualSwaps(): array
    {
        $asns = AsnMaster::with('unitSaatIni')
            ->whereNotNull('domisili_lat')
            ->whereNotNull('domisili_lng')
            ->get();

        $pairs = [];
        $visited = [];
        $n = count($asns);

        for ($i = 0; $i < $n; $i++) {
            $asnA = $asns[$i];
            $unitA = $asnA->unitSaatIni;
            if (!$unitA) continue;

            for ($j = $i + 1; $j < $n; $j++) {
                $asnB = $asns[$j];
                $unitB = $asnB->unitSaatIni;
                if (!$unitB) continue;

                // Jangan swap jika sudah di unit kerja yang sama
                if ($unitA->id === $unitB->id) continue;

                // Jarak saat ini
                $distA_cur = (float)$asnA->jarak_ke_kantor_km;
                $distB_cur = (float)$asnB->jarak_ke_kantor_km;

                // Jarak jika bertukar posisi (A ke Unit B, B ke Unit A)
                $distA_swap = $this->haversine->calculateDistance(
                    $asnA->domisili_lat, $asnA->domisili_lng,
                    $unitB->kantor_lat, $unitB->kantor_lng
                );

                $distB_swap = $this->haversine->calculateDistance(
                    $asnB->domisili_lat, $asnB->domisili_lng,
                    $unitA->kantor_lat, $unitA->kantor_lng
                );

                // Selisih penghematan jarak
                $saveA = $distA_cur - $distA_swap;
                $saveB = $distB_cur - $distB_swap;

                // Kriteria Simetris Ideal (Pareto Optimal):
                // Kedua pegawai harus sama-sama hemat (atau total efisiensi bersama positif signifikan > 10 km)
                if ($saveA > 0 && $saveB > 0) {
                    $combinedSaveDaily = $saveA + $saveB;
                    $combinedSaveYearlyKm = $combinedSaveDaily * 2 * 250; // PP x 250 hari kerja

                    // Estimasi Hemat BBM (Rata-rata 1 Liter : 35 Km, harga Rp 11.500/liter)
                    $estHematBbmRupiah = ($combinedSaveYearlyKm / 35) * 11500;

                    // Estimasi Reduksi Emisi Karbon (0.12 kg CO2 per km)
                    $estReduksiCo2Kg = $combinedSaveYearlyKm * 0.12;

                    $pairKey = min($asnA->id, $asnB->id) . '_' . max($asnA->id, $asnB->id);
                    if (!isset($visited[$pairKey])) {
                        $visited[$pairKey] = true;
                        $pairs[] = [
                            'asnA' => $asnA,
                            'asnB' => $asnB,
                            'unitA' => $unitA,
                            'unitB' => $unitB,
                            'distA_current' => $distA_cur,
                            'distA_swap' => $distA_swap,
                            'saveA' => round($saveA, 2),
                            'distB_current' => $distB_cur,
                            'distB_swap' => $distB_swap,
                            'saveB' => round($saveB, 2),
                            'combined_save_daily_km' => round($combinedSaveDaily, 2),
                            'combined_save_yearly_km' => round($combinedSaveYearlyKm, 2),
                            'est_hemat_bbm_rp' => round($estHematBbmRupiah),
                            'est_reduksi_co2_kg' => round($estReduksiCo2Kg, 1),
                            'compatibility_score' => round((($saveA + $saveB) / ($distA_cur + $distB_cur)) * 100, 1),
                        ];
                    }
                }
            }
        }

        // Urutkan dari pasangan paling efisien
        usort($pairs, function ($a, $b) {
            return $b['combined_save_daily_km'] <=> $a['combined_save_daily_km'];
        });

        return $pairs;
    }
}
