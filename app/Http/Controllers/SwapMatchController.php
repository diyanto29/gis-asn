<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\RekomendasiMutasi;
use App\Models\UnitKerja;
use App\Services\SwapMatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    /**
     * Mendaftarkan paket usulan tukar guling simetris (Zero-ABK) ke sidang Baperjakat.
     */
    public function ajukanSwap(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asn_a_id' => 'required|exists:asn_master,id',
            'asn_b_id' => 'required|exists:asn_master,id',
            'unit_a_id' => 'required|exists:unit_kerja,id',
            'unit_b_id' => 'required|exists:unit_kerja,id',
            'dist_a_swap' => 'required|numeric',
            'dist_b_swap' => 'required|numeric',
            'save_a' => 'required|numeric',
            'save_b' => 'required|numeric',
            'combined_save_daily' => 'required|numeric',
        ]);

        $asnA = AsnMaster::findOrFail($validated['asn_a_id']);
        $asnB = AsnMaster::findOrFail($validated['asn_b_id']);
        $unitA = UnitKerja::findOrFail($validated['unit_a_id']);
        $unitB = UnitKerja::findOrFail($validated['unit_b_id']);

        $now = now();
        $catatanA = "Paket Tukar Guling Simetris (Zero-ABK) berpasangan dengan {$asnB->nama_lengkap} (NIP {$asnB->nip}). Efisiensi bersama: +{$validated['combined_save_daily']} km/hari.";
        $catatanB = "Paket Tukar Guling Simetris (Zero-ABK) berpasangan dengan {$asnA->nama_lengkap} (NIP {$asnA->nip}). Efisiensi bersama: +{$validated['combined_save_daily']} km/hari.";

        // Rekomendasi Pegawai A ke Unit B
        RekomendasiMutasi::updateOrCreate(
            ['id_asn' => $asnA->id, 'id_unit_tujuan' => $unitB->id],
            [
                'id_unit_asal' => $unitA->id,
                'jarak_asal_km' => $asnA->jarak_ke_kantor_km,
                'jarak_baru_km' => $validated['dist_a_swap'],
                'selisih_hemat_jarak_km' => $validated['save_a'],
                'nilai_preferensi_v' => 0.990000,
                'ranking' => 1,
                'status_usulan' => 'diajukan_baperjakat',
                'catatan_baperjakat' => $catatanA,
                'tanggal_kalkulasi' => $now,
            ]
        );

        // Rekomendasi Pegawai B ke Unit A
        RekomendasiMutasi::updateOrCreate(
            ['id_asn' => $asnB->id, 'id_unit_tujuan' => $unitA->id],
            [
                'id_unit_asal' => $unitB->id,
                'jarak_asal_km' => $asnB->jarak_ke_kantor_km,
                'jarak_baru_km' => $validated['dist_b_swap'],
                'selisih_hemat_jarak_km' => $validated['save_b'],
                'nilai_preferensi_v' => 0.990000,
                'ranking' => 1,
                'status_usulan' => 'diajukan_baperjakat',
                'catatan_baperjakat' => $catatanB,
                'tanggal_kalkulasi' => $now,
            ]
        );

        $asnA->update(['status_permohonan_mutasi' => true]);
        $asnB->update(['status_permohonan_mutasi' => true]);

        return redirect()->route('topsis.riwayat')
            ->with('success', "Paket Tukar Guling Simetris antara {$asnA->nama_lengkap} dan {$asnB->nama_lengkap} berhasil diajukan ke agenda sidang Baperjakat!");
    }
}

