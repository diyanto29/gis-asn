<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\RekomendasiMutasi;
use App\Models\TopsisKriteria;
use App\Models\UnitKerja;
use App\Services\HaversineService;
use App\Services\MutasiRuleService;
use App\Services\TopsisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TopsisController extends Controller
{
    public function __construct(
        protected HaversineService $haversine,
        protected TopsisService $topsis,
        protected MutasiRuleService $mutasiRule
    ) {}

    /**
     * Halaman Utama Menu TOPSIS: Redirect ke Mode 1 (PNS to Dinas).
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('topsis.pns-to-dinas');
    }

    /**
     * =========================================================================
     * MODE 1: REKOMENDASI SPK "PNS TO DINAS"
     * Skenario: Pegawai ASN mencari penempatan dinas terbaik yang ideal baginya.
     *
     * ATURAN MUTLAK (BY SYSTEM — POIN 1 s/d 3):
     * 1. Matching Jabatan: Fungsional = exact match nama/master jabatan; Pelaksana = sama kelas jabatan.
     * 2. Formasi Tersedia: Kuota lowong formasi cocok wajib > 0 (ABK - Bezetting > 0).
     * 3. Masa Jabatan    : Masa jabatan PNS saat ini wajib >= 2 tahun (dinamis).
     *
     * KRITERIA TOPSIS (5 KRITERIA):
     * - C1: Jarak Domisili ke Unit Tujuan (Cost)
     * - C2: Masa Jabatan PNS (Benefit)
     * - C3: Masa Kerja PNS (Benefit)
     * - C4: Formasi Jabatan Cocok / Kuota Lowong (Benefit)
     * - C5: Kebutuhan Pegawai Unit Kerja Saat Ini / Unit Asal (Benefit: 3=Lengkap, 2=Cukup, 1=Kurang)
     * =========================================================================
     */
    public function pnsToDinas(Request $request): View
    {
        $asnList = AsnMaster::with(['unitSaatIni', 'masterJabatan'])->orderBy('nama')->get();
        $selectedAsnId = $request->get('asn_id');

        $kriteriaModels = TopsisKriteria::where('kategori_mode', 'pns_to_dinas')
            ->where('is_active', true)
            ->orderBy('kode_kriteria')
            ->get();

        $selectedAsn = null;
        $topsisResult = null;
        $ranked = [];
        $kelayakan = null;
        $unitAsalEval = null;
        $filterSummary = null;

        if ($selectedAsnId) {
            $selectedAsn = AsnMaster::with(['unitSaatIni', 'masterJabatan', 'formasiJabatan'])->findOrFail($selectedAsnId);

            $unitStats = $this->mutasiRule->getAllUnitStats();
            $kelayakan = $this->mutasiRule->cekKelayakanAsn($selectedAsn);
            $unitAsalEval = $this->mutasiRule->evaluasiUnitAsal($selectedAsn->unitSaatIni, $unitStats);

            // Ambil seluruh formasi terbuka (kuota_lowong > 0) pada unit tujuan selain unit asal
            $openFormasiLain = FormasiJabatan::with(['masterJabatan', 'unitKerja'])
                ->where('kuota_lowong', '>', 0)
                ->where('id_unit_kerja', '!=', $selectedAsn->id_unit_saat_ini)
                ->get();

            // POIN 1 & 2 MUTLAK: Saring hanya formasi yang cocok dengan jabatan / kelas jabatan PNS
            $matchingFormasis = $this->mutasiRule->cariFormasiCocok($selectedAsn, $openFormasiLain);
            $matchingByUnit = $matchingFormasis->groupBy('id_unit_kerja');

            $filterSummary = [
                'total_unit_lain' => UnitKerja::where('id', '!=', $selectedAsn->id_unit_saat_ini)->count(),
                'unit_cocok_formasi' => $matchingByUnit->count(),
                'aturan_match' => $this->mutasiRule->deskripsiAturanMatch($selectedAsn),
            ];

            // POIN 3 MUTLAK: Hanya hitung peringkat jika masa jabatan >= batas minimal (default 2 tahun)
            if ($kelayakan['masa_jabatan_ok'] && $matchingByUnit->isNotEmpty()) {
                $alternatives = [];
                $unitDetails = [];

                foreach ($matchingByUnit as $unitId => $formasiList) {
                    $unit = $formasiList->first()->unitKerja;
                    if (!$unit || !$unit->kantor_lat || !$unit->kantor_lng) {
                        continue;
                    }

                    $jarakBaru = $this->haversine->calculateDistance(
                        (float)$selectedAsn->domisili_lat,
                        (float)$selectedAsn->domisili_lng,
                        (float)$unit->kantor_lat,
                        (float)$unit->kantor_lng
                    );

                    $jarakLama = (float)($selectedAsn->jarak_ke_kantor_km ?: 15.0);
                    $hematJarak = round(max(0, $jarakLama - $jarakBaru), 2);
                    $kuotaCocok = (int)$formasiList->sum('kuota_lowong');
                    $formasiLabels = $formasiList->map(function ($f) {
                        $kls = $f->masterJabatan?->kelas_jabatan ? " [Kls {$f->masterJabatan->kelas_jabatan}]" : '';
                        return "{$f->nama_jabatan}{$kls} ({$f->kuota_lowong} lowong)";
                    })->values()->toArray();

                    $unitKey = 'unit_' . $unit->id;
                    $alternatives[$unitKey] = [
                        'id' => $unit->id,
                        'nama_unit' => $unit->nama_unit,
                        'jarak_baru_km' => $jarakBaru,
                        'hemat_jarak_km' => $hematJarak,
                        'kuota_cocok' => $kuotaCocok,
                        'formasi_cocok' => $formasiLabels,
                        'unit_asal_eval' => $unitAsalEval,

                        // Matriks 5 Kriteria TOPSIS
                        'C1' => (float)$jarakBaru,                     // Cost: Jarak
                        'C2' => (float)$kelayakan['masa_jabatan_tahun'], // Benefit: Masa Jabatan
                        'C3' => (float)$kelayakan['masa_kerja_tahun'],   // Benefit: Masa Kerja
                        'C4' => (float)$kuotaCocok,                      // Benefit: Formasi Jabatan (Kuota Cocok)
                        'C5' => (float)$unitAsalEval['skor'],            // Benefit: Kebutuhan Pegawai Unit Saat Ini
                    ];

                    $unitDetails[$unit->id] = $unit;
                }

                // Bobot kriteria dinamis dari database
                $criteriaWeights = [];
                foreach ($kriteriaModels as $k) {
                    $criteriaWeights[$k->kode_kriteria] = [
                        'bobot' => $k->bobot,
                        'atribut' => $k->atribut,
                    ];
                }

                if (!empty($alternatives) && !empty($criteriaWeights)) {
                    $topsisResult = $this->topsis->calculate(array_values($alternatives), $criteriaWeights);
                    $ranked = $topsisResult['ranked'];

                    foreach ($ranked as &$item) {
                        $altId = $item['alternative']['id'] ?? null;
                        $item['id'] = $altId;
                        $item['vi'] = $item['v_value'];
                        $item['unit'] = $unitDetails[$altId] ?? null;
                        $altInfo = $alternatives['unit_' . $altId] ?? null;
                        $item['jarak_baru_km'] = $altInfo['jarak_baru_km'] ?? 0;
                        $item['hemat_jarak_km'] = $altInfo['hemat_jarak_km'] ?? 0;
                        $item['kebutuhan_staf'] = $altInfo['kuota_cocok'] ?? 0;
                        $item['has_formasi'] = ($altInfo['kuota_cocok'] ?? 0) > 0 ? 1 : 0;
                        $item['formasi_cocok'] = $altInfo['formasi_cocok'] ?? [];
                        $item['unit_asal_eval'] = $altInfo['unit_asal_eval'] ?? $unitAsalEval;
                    }
                    unset($item);
                }
            }
        }

        return view('topsis.pns-to-dinas', compact(
            'asnList',
            'selectedAsn',
            'kriteriaModels',
            'ranked',
            'topsisResult',
            'kelayakan',
            'unitAsalEval',
            'filterSummary'
        ));
    }

    /**
     * =========================================================================
     * MODE 2: REKOMENDASI SPK "DINAS TO PNS"
     * Skenario: Suatu Dinas/Unit Kerja mencari kandidat ASN terbaik se-kabupaten
     * yang memenuhi syarat mutlak (Poin 1-3) dan dirangking dengan 5 kriteria TOPSIS.
     * =========================================================================
     */
    public function dinasToPns(Request $request): View
    {
        $unitList = UnitKerja::orderBy('nama_unit')->get();
        $selectedUnitId = $request->get('unit_id');

        $kriteriaModels = TopsisKriteria::where('kategori_mode', 'dinas_to_pns')
            ->where('is_active', true)
            ->orderBy('kode_kriteria')
            ->get();

        $selectedUnit = null;
        $topsisResult = null;
        $ranked = [];
        $unitTujuanEval = null;
        $openFormasiUnit = collect();
        $filterSummary = null;

        if ($selectedUnitId) {
            $selectedUnit = UnitKerja::findOrFail($selectedUnitId);
            $unitStats = $this->mutasiRule->getAllUnitStats();
            $unitTujuanEval = $this->mutasiRule->evaluasiUnitAsal($selectedUnit, $unitStats);

            // Formasi terbuka di Dinas yang dipilih
            $openFormasiUnit = FormasiJabatan::with('masterJabatan')
                ->where('id_unit_kerja', $selectedUnit->id)
                ->where('kuota_lowong', '>', 0)
                ->get();

            // Kandidat awal: seluruh ASN dari unit lain yang punya koordinat domisili
            $candidateAsns = AsnMaster::with(['unitSaatIni', 'masterJabatan'])
                ->where('id_unit_saat_ini', '!=', $selectedUnit->id)
                ->whereNotNull('domisili_lat')
                ->whereNotNull('domisili_lng')
                ->get();

            $alternatives = [];
            $asnDetails = [];
            $skippedMasaJabatan = 0;
            $skippedFormasi = 0;

            foreach ($candidateAsns as $asn) {
                // POIN 3 MUTLAK: Masa jabatan >= 2 tahun
                $kelayakan = $this->mutasiRule->cekKelayakanAsn($asn);
                if (!$kelayakan['masa_jabatan_ok']) {
                    $skippedMasaJabatan++;
                    continue;
                }

                // POIN 1 & 2 MUTLAK: Kecocokan jabatan (Fungsional exact / Pelaksana sama kelas) & kuota > 0
                $matchingFormasi = $this->mutasiRule->cariFormasiCocok($asn, $openFormasiUnit);
                if ($matchingFormasi->isEmpty()) {
                    $skippedFormasi++;
                    continue;
                }

                $jarakKeDinas = $this->haversine->calculateDistance(
                    (float)$asn->domisili_lat,
                    (float)$asn->domisili_lng,
                    (float)$selectedUnit->kantor_lat,
                    (float)$selectedUnit->kantor_lng
                );

                $jarakLama = (float)($asn->jarak_ke_kantor_km ?: 15.0);
                $hematJarak = round(max(0, $jarakLama - $jarakKeDinas), 2);
                $kuotaCocok = (int)$matchingFormasi->sum('kuota_lowong');
                $formasiLabels = $matchingFormasi->map(function ($f) {
                    $kls = $f->masterJabatan?->kelas_jabatan ? " [Kls {$f->masterJabatan->kelas_jabatan}]" : '';
                    return "{$f->nama_jabatan}{$kls} ({$f->kuota_lowong} lowong)";
                })->values()->toArray();

                // POIN 4 (Hak Prerogatif Pimpinan — C5): Kebutuhan Pegawai Unit Kerja Asal (%)
                $unitAsalEval = $this->mutasiRule->evaluasiUnitAsal($asn->unitSaatIni, $unitStats);

                $asnKey = 'asn_' . $asn->id;
                $alternatives[$asnKey] = [
                    'id' => $asn->id,
                    'nama' => $asn->nama,
                    'nip' => $asn->nip,
                    'jabatan' => $asn->jabatan,
                    'jarak_ke_dinas_km' => $jarakKeDinas,
                    'hemat_jarak_km' => $hematJarak,
                    'masa_jabatan_tahun' => $kelayakan['masa_jabatan_tahun'],
                    'masa_kerja_tahun' => $kelayakan['masa_kerja_tahun'],
                    'masa_kerja_keluar_ok' => $kelayakan['masa_kerja_keluar_ok'],
                    'min_masa_kerja_keluar_tahun' => $kelayakan['min_masa_kerja_keluar_tahun'],
                    'kuota_cocok' => $kuotaCocok,
                    'formasi_cocok' => $formasiLabels,
                    'unit_asal_eval' => $unitAsalEval,
                    'kondisi_unit_asal_skor' => $unitAsalEval['skor'],

                    // Matriks 4 Kriteria TOPSIS Mode 2 (Dinas to PNS):
                    // Syarat mutlak Poin 1-3 (matching jabatan/pendidikan, formasi tersedia, masa jabatan >= min)
                    // sudah disaring ketat di atas sebelum masuk matriks TOPSIS.
                    'C1' => (float)$jarakKeDinas,                    // Cost: Jarak
                    'C2' => (float)$kelayakan['masa_jabatan_tahun'], // Benefit: Masa Jabatan (TMT Jabatan)
                    'C3' => (float)$kelayakan['masa_kerja_tahun'],   // Benefit: Masa Kerja (TMT CPNS)
                    'C4' => (float)$unitAsalEval['skor'],            // Benefit: Kebutuhan Pegawai Unit Saat Ini (Unit Asal)
                ];

                $asnDetails[$asn->id] = $asn;
            }

            $filterSummary = [
                'total_pns_lain' => $candidateAsns->count(),
                'lolos_syarat_mutlak' => count($alternatives),
                'gugur_masa_jabatan' => $skippedMasaJabatan,
                'gugur_formasi' => $skippedFormasi,
                'formasi_terbuka_dinas' => $openFormasiUnit->count(),
            ];

            // Bobot kriteria dinamis dari database
            $criteriaWeights = [];
            foreach ($kriteriaModels as $k) {
                $criteriaWeights[$k->kode_kriteria] = [
                    'bobot' => $k->bobot,
                    'atribut' => $k->atribut,
                ];
            }

            if (!empty($alternatives) && !empty($criteriaWeights)) {
                $topsisResult = $this->topsis->calculate(array_values($alternatives), $criteriaWeights);
                $ranked = $topsisResult['ranked'];

                foreach ($ranked as &$item) {
                    $altId = $item['alternative']['id'] ?? null;
                    $item['id'] = $altId;
                    $item['vi'] = $item['v_value'];
                    $item['asn'] = $asnDetails[$altId] ?? null;
                    $altInfo = $alternatives['asn_' . $altId] ?? null;
                    $item['jarak_ke_dinas_km'] = $altInfo['jarak_ke_dinas_km'] ?? 0;
                    $item['hemat_jarak_km'] = $altInfo['hemat_jarak_km'] ?? 0;
                    $item['masa_jabatan_tahun'] = $altInfo['masa_jabatan_tahun'] ?? 0;
                    $item['masa_kerja_tahun'] = $altInfo['masa_kerja_tahun'] ?? 0;
                    $item['kuota_cocok'] = $altInfo['kuota_cocok'] ?? 0;
                    $item['formasi_cocok'] = $altInfo['formasi_cocok'] ?? [];
                    $item['unit_asal_eval'] = $altInfo['unit_asal_eval'] ?? null;
                    $item['kondisi_unit_asal_skor'] = $altInfo['kondisi_unit_asal_skor'] ?? 2;
                }
                unset($item);
            }
        }

        return view('topsis.dinas-to-pns', compact(
            'unitList',
            'selectedUnit',
            'kriteriaModels',
            'ranked',
            'topsisResult',
            'unitTujuanEval',
            'openFormasiUnit',
            'filterSummary'
        ));
    }

    /**
     * Menyimpan hasil kalkulasi rekomendasi ke Database Baperjakat.
     */
    public function simpanRekomendasi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_asn' => 'required|exists:asn_master,id',
            'id_unit_tujuan' => 'required|exists:unit_kerja,id',
            'jarak_baru_km' => 'required|numeric',
            'selisih_hemat_jarak_km' => 'required|numeric',
            'nilai_preferensi_v' => 'required|numeric',
            'ranking' => 'required|integer',
            'catatan' => 'nullable|string',
        ]);

        $asn = AsnMaster::findOrFail($validated['id_asn']);

        RekomendasiMutasi::updateOrCreate(
            [
                'id_asn' => $asn->id,
                'id_unit_tujuan' => $validated['id_unit_tujuan'],
            ],
            [
                'id_unit_asal' => $asn->id_unit_saat_ini,
                'jarak_asal_km' => $asn->jarak_ke_kantor_km ?: 15.0,
                'jarak_baru_km' => $validated['jarak_baru_km'],
                'selisih_hemat_jarak_km' => $validated['selisih_hemat_jarak_km'],
                'nilai_preferensi_v' => $validated['nilai_preferensi_v'],
                'ranking' => $validated['ranking'],
                'status_usulan' => 'diajukan_baperjakat',
                'catatan_baperjakat' => $validated['catatan'] ?? 'Diajukan melalui SPK TOPSIS multi-kriteria.',
                'tanggal_kalkulasi' => now(),
            ]
        );

        return back()->with('success', "Rekomendasi mutasi untuk {$asn->nama} berhasil diajukan ke Sidang Baperjakat BKPSDM!");
    }

    /**
     * Menampilkan riwayat rekomendasi mutasi & sidang Baperjakat.
     */
    public function riwayat(): View
    {
        $riwayat = RekomendasiMutasi::with(['asn', 'unitAsal', 'unitTujuan'])
            ->orderByDesc('tanggal_kalkulasi')
            ->get();

        return view('topsis.riwayat', compact('riwayat'));
    }

    /**
     * Menyetujui usulan rekomendasi mutasi dan menerbitkan SK Mutasi.
     */
    public function approveUsulan(Request $request, int $id): RedirectResponse
    {
        $usulan = RekomendasiMutasi::with(['asn', 'unitAsal', 'unitTujuan'])->findOrFail($id);

        if ($usulan->status_usulan === 'disetujui') {
            return back()->with('error', 'Usulan mutasi ini sudah berstatus disetujui sebelumnya.');
        }

        $asn = $usulan->asn;
        $unitAsal = $usulan->unitAsal;
        $unitTujuan = $usulan->unitTujuan;

        // 1. Update status usulan Baperjakat
        $catatan = $request->input('catatan', 'Disetujui melalui Sidang Penilai Kinerja / Baperjakat BKPSDM.');
        $usulan->update([
            'status_usulan' => 'disetujui',
            'catatan_baperjakat' => $catatan,
        ]);

        // 2. Eksekusi perpindahan unit kerja pada data master ASN
        $asn->update([
            'id_unit_saat_ini' => $unitTujuan->id,
            'jarak_ke_kantor_km' => $usulan->jarak_baru_km,
            'status_permohonan_mutasi' => false,
            'alasan_mutasi' => null,
        ]);

        // 3. Update kapasitas bezetting & kuota makro unit kerja jika berbeda
        if ($unitAsal && $unitAsal->id !== $unitTujuan->id) {
            $unitAsal->decrement('bezetting_saat_ini');
            $unitAsal->increment('kuota_formasi_kosong');

            $unitTujuan->increment('bezetting_saat_ini');
            if ($unitTujuan->kuota_formasi_kosong > 0) {
                $unitTujuan->decrement('kuota_formasi_kosong');
            }
        }

        return redirect()->route('topsis.riwayat')
            ->with('success', "SK Mutasi Diterbitkan! ASN {$asn->nama} resmi dipindahkan dari {$unitAsal->nama_unit} ke {$unitTujuan->nama_unit}.");
    }

    /**
     * Menolak usulan rekomendasi mutasi dengan catatan sidang.
     */
    public function rejectUsulan(Request $request, int $id): RedirectResponse
    {
        $usulan = RekomendasiMutasi::with('asn')->findOrFail($id);
        $alasan = $request->input('catatan_penolakan', 'Ditolak berdasarkan pertimbangan sidang Baperjakat.');

        $usulan->update([
            'status_usulan' => 'ditolak',
            'catatan_baperjakat' => $alasan,
        ]);

        if ($usulan->asn) {
            $usulan->asn->update([
                'status_permohonan_mutasi' => false,
            ]);
        }

        return redirect()->route('topsis.riwayat')
            ->with('success', "Usulan mutasi untuk ASN {$usulan->asn->nama} telah ditolak.");
    }
}
