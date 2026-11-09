<?php

namespace App\Http\Controllers;

use App\Services\SwapMatchService;
use Illuminate\View\View;

class SwapMatchController extends Controller
{
    public function __construct(
        protected SwapMatchService $swapService
    ) {}

    public function index(): View
    {
        $pairs = $this->swapService->findMutualSwaps();

        $totalPasangan = count($pairs);
        $totalHematTahunKm = array_sum(array_column($pairs, 'combined_save_yearly_km'));
        $totalHematBbmRp = array_sum(array_column($pairs, 'est_hemat_bbm_rp'));
        $totalReduksiCo2 = array_sum(array_column($pairs, 'est_reduksi_co2_kg'));

        return view('swap-match.index', compact(
            'pairs', 'totalPasangan',
            'totalHematTahunKm', 'totalHematBbmRp', 'totalReduksiCo2'
        ));
    }
}

