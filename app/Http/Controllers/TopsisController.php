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
     * Halaman Utama 1-Pintu Menu SPK TOPSIS (Mode 1: PNS -> Dinas & Mode 2: Dinas -> PNS).
     */
    public function index(Request $request): View
    {
        $activeMode = $request->get('mode', 'pns_to_dinas');
        if (!in_array($activeMode, ['pns_to_dinas', 'dinas_to_pns'], true)) {
            $activeMode = 'pns_to_dinas';
        }

        $data = $activeMode === 'dinas_to_pns'
            ? $this->buildDinasToPnsData($request)
            : $this->buildPnsToDinasData($request);

        return view('topsis.index', array_merge(['activeMode' => $activeMode], $data));
    }

    /**
     * Backward-compatible route untuk Mode 1 (PNS to Dinas) -> render di halaman 1-pintu SPK.
     */
    public function pnsToDinas(Request $request): View
    {
        $request->merge(['mode' => 'pns_to_dinas']);
        return $this->index($request);
    }

    /**
     * Backward-compatible route untuk Mode 2 (Dinas to PNS) -> render di halaman 1-pintu SPK.
     */
    public function dinasToPns(Request $request): View
    {
        $request->merge(['mode' => 'dinas_to_pns']);
        return $this->index($request);
    }

    /**
     * =========================================================================
     * BUILDER MODE 1: REKOMENDASI SPK "PNS TO DINAS" (5 KRITERIA)
     * =========================================================================
     */
    protected function buildPnsToDinasData(Request $request): array
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
        $alternativesList = [];
        $rules = MutasiRuleService::getRules();
        $defaultRadiusKm = (float)($rules['radius_terdekat_km'] ?? 10.0);
        $radiusParam = $request->get('radius', (string)$defaultRadiusKm);

        if ($selectedAsnId) {
            $selectedAsn = AsnMaster::with(['unitSaatIni', 'masterJabatan', 'formasiJabatan'])->findOrFail($selectedAsnId);

            $unitStats = $this->mutasiRule->getAllUnitStats();
            $kelayakan = $this->mutasiRule->cekKelayakanAsn($selectedAsn);
            $unitAsalEval = $this->mutasiRule->evaluasiUnitAsal($selectedAsn->unitSaatIni, $unitStats);

            // Ambil SEMUA Unit Kerja tujuan selain unit saat ini beserta seluruh formasinya
            $allCandidateUnits = UnitKerja::with(['formasiJabatan.masterJabatan'])
                ->where('id', '!=', $selectedAsn->id_unit_saat_ini)
                ->whereNotNull('kantor_lat')
                ->whereNotNull('kantor_lng')
                ->get();

            // Hitung jarak seluruh unit dari domisili PNS
            $unitsWithDistance = $allCandidateUnits->map(function ($unit) use ($selectedAsn) {
                $jarak = $this->haversine->calculateDistance(
                    (float)$selectedAsn->domisili_lat,
                    (float)$selectedAsn->domisili_lng,
                    (float)$unit->kantor_lat,
                    (float)$unit->kantor_lng
                );
                return ['unit' => $unit, 'jarak' => $jarak];
            })->sortBy('jarak')->values();

            // Saring berdasarkan subkriteria Radius Terdekat (C1, default 10 km) — kecuali user memilih 'all'
            $isAllRadius = ($radiusParam === 'all');
            $activeRadiusKm = $isAllRadius ? null : max(1.0, (float)$radiusParam);
            $fallbackUsed = false;

            if ($isAllRadius) {
                $filteredUnits = $unitsWithDistance;
            } else {
                $filteredUnits = $unitsWithDistance->filter(fn($row) => $row['jarak'] <= $activeRadiusKm)->values();
                // Jika tidak ada satupun unit dalam radius tsb (misal PNS luar kota), fallback tampilkan semua unit terdekat
                if ($filteredUnits->isEmpty() && $unitsWithDistance->isNotEmpty()) {
                    $filteredUnits = $unitsWithDistance;
                    $fallbackUsed = true;
                }
            }

            $alternatives = [];
            $unitDetails = [];
            $unitTersediaCount = 0;

            foreach ($filteredUnits as $row) {
                $unit = $row['unit'];
                $jarakBaru = (float)$row['jarak'];

                $jarakLama = (float)($selectedAsn->jarak_ke_kantor_km ?: 15.0);
                $hematJarak = round(max(0, $jarakLama - $jarakBaru), 2);

                // Evaluasi Formasi Jabatan (C4) TANPA menyembunyikan unit yang penuh / tidak tersedia
                $formasiEval = $this->mutasiRule->evaluasiFormasiUnitTujuan($selectedAsn, $unit->formasiJabatan);
                if ($formasiEval['status'] === 'Tersedia') {
                    $unitTersediaCount++;
                }

                $unitKey = 'unit_' . $unit->id;
                $alternatives[$unitKey] = [
                    'id' => $unit->id,
                    'label_alt' => $unit->nama_unit,
                    'nama_unit' => $unit->nama_unit,
                    'jarak_baru_km' => $jarakBaru,
                    'hemat_jarak_km' => $hematJarak,
                    'kuota_cocok' => $formasiEval['kuota_cocok'],
                    'formasi_cocok' => $formasiEval['labels'],
                    'formasi_eval' => $formasiEval,
                    'unit_asal_eval' => $unitAsalEval,

                    // Matriks 5 Kriteria TOPSIS (semua unit dalam radius tetap dihitung!)
                    'C1' => (float)$jarakBaru,                       // Cost: Jarak
                    'C2' => (float)$kelayakan['masa_jabatan_tahun'], // Benefit: Masa Jabatan
                    'C3' => (float)$kelayakan['masa_kerja_tahun'],   // Benefit: Masa Kerja
                    'C4' => (float)$formasiEval['skor'],             // Benefit: Formasi (2=Tersedia, 1=Penuh, 0=Tidak Tersedia)
                    'C5' => (float)$unitAsalEval['skor'],            // Benefit: Kebutuhan Pegawai Unit Saat Ini
                ];

                $unitDetails[$unit->id] = $unit;
            }

            $filterSummary = [
                'total_unit_lain' => $allCandidateUnits->count(),
                'unit_dalam_radius' => count($alternatives),
                'unit_cocok_formasi' => $unitTersediaCount,
                'radius_label' => $isAllRadius ? 'Semua Unit Kerja' : "Radius ≤ {$activeRadiusKm} km",
                'fallback_used' => $fallbackUsed,
                'nearest_km' => $unitsWithDistance->first()['jarak'] ?? null,
                'aturan_match' => $this->mutasiRule->deskripsiAturanMatch($selectedAsn),
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
                $alternativesList = array_values($alternatives);
                $topsisResult = $this->topsis->calculate($alternativesList, $criteriaWeights);
                $ranked = $topsisResult['ranked'];

                foreach ($ranked as &$item) {
                    $altId = $item['alternative']['id'] ?? null;
                    $item['id'] = $altId;
                    $item['vi'] = $item['v_value'];
                    $item['unit'] = $unitDetails[$altId] ?? null;
                    $altInfo = $alternatives['unit_' . $altId] ?? null;
                    $item['jarak_baru_km'] = $altInfo['jarak_baru_km'] ?? 0;
                    $item['hemat_jarak_km'] = $altInfo['hemat_jarak_km'] ?? 0;
                    $item['masa_jabatan_tahun'] = $kelayakan['masa_jabatan_tahun'];
                    $item['masa_kerja_tahun'] = $kelayakan['masa_kerja_tahun'];
                    $item['masa_kerja_keluar_ok'] = $kelayakan['masa_kerja_keluar_ok'];
                    $item['min_masa_jabatan_tahun'] = $kelayakan['min_masa_jabatan_tahun'];
                    $item['min_masa_kerja_keluar_tahun'] = $kelayakan['min_masa_kerja_keluar_tahun'];
                    $item['kebutuhan_staf'] = $altInfo['kuota_cocok'] ?? 0;
                    $item['has_formasi'] = ($altInfo['kuota_cocok'] ?? 0) > 0 ? 1 : 0;
                    $item['formasi_cocok'] = $altInfo['formasi_cocok'] ?? [];
                    $item['formasi_eval'] = $altInfo['formasi_eval'] ?? null;
                    $item['unit_asal_eval'] = $altInfo['unit_asal_eval'] ?? $unitAsalEval;
                }
                unset($item);
            }
        }

        return compact(
            'asnList',
            'selectedAsn',
            'kriteriaModels',
            'ranked',
            'topsisResult',
            'alternativesList',
            'kelayakan',
            'unitAsalEval',
            'filterSummary',
            'defaultRadiusKm',
            'radiusParam'
        );
    }

    /**
     * =========================================================================
     * BUILDER MODE 2: REKOMENDASI SPK "DINAS TO PNS" (4 KRITERIA)
     * =========================================================================
     */
    protected function buildDinasToPnsData(Request $request): array
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
        $alternativesList = [];
        $rules = MutasiRuleService::getRules();

        if ($selectedUnitId) {
            $selectedUnit = UnitKerja::findOrFail($selectedUnitId);
            $unitStats = $this->mutasiRule->getAllUnitStats();
            $unitTujuanEval = $this->mutasiRule->evaluasiUnitAsal($selectedUnit, $unitStats);

            $openFormasiUnit = FormasiJabatan::with('masterJabatan')
                ->where('id_unit_kerja', $selectedUnit->id)
                ->where('kuota_lowong', '>', 0)
                ->get();

            // Ambil PNS yang saat ini bertugas di Dinas terpilih (Dinas A -> PNS A)
            $candidateAsns = AsnMaster::with(['unitSaatIni', 'masterJabatan'])
                ->where('id_unit_saat_ini', $selectedUnit->id)
                ->whereNotNull('domisili_lat')
                ->whereNotNull('domisili_lng')
                ->orderBy('nama')
                ->get();

            $alternatives = [];
            $asnDetails = [];
            $skippedMasaJabatan = 0;

            foreach ($candidateAsns as $asn) {
                $kelayakan = $this->mutasiRule->cekKelayakanAsn($asn);
                if (!$kelayakan['masa_jabatan_ok']) {
                    $skippedMasaJabatan++;
                    continue;
                }

                $jarakKeDinas = ($selectedUnit->kantor_lat && $selectedUnit->kantor_lng)
                    ? $this->haversine->calculateDistance(
                        (float)$asn->domisili_lat,
                        (float)$asn->domisili_lng,
                        (float)$selectedUnit->kantor_lat,
                        (float)$selectedUnit->kantor_lng
                    )
                    : (float)($asn->jarak_ke_kantor_km ?: 0.0);

                $asnKey = 'asn_' . $asn->id;
                $alternatives[$asnKey] = [
                    'id' => $asn->id,
                    'label_alt' => $asn->nama . ' (' . $asn->nip . ')',
                    'nama' => $asn->nama,
                    'nip' => $asn->nip,
                    'jabatan' => $asn->jabatan,
                    'jarak_ke_dinas_km' => $jarakKeDinas,
                    'hemat_jarak_km' => 0.0,
                    'masa_jabatan_tahun' => $kelayakan['masa_jabatan_tahun'],
                    'masa_kerja_tahun' => $kelayakan['masa_kerja_tahun'],
                    'masa_kerja_keluar_ok' => $kelayakan['masa_kerja_keluar_ok'],
                    'min_masa_jabatan_tahun' => $kelayakan['min_masa_jabatan_tahun'],
                    'min_masa_kerja_keluar_tahun' => $kelayakan['min_masa_kerja_keluar_tahun'],
                    'unit_asal_eval' => $unitTujuanEval,
                    'kondisi_unit_asal_skor' => $unitTujuanEval['skor'],

                    // Matriks 4 Kriteria TOPSIS Mode 2 (Dinas -> PNS):
                    'C1' => (float)$jarakKeDinas,
                    'C2' => (float)$kelayakan['masa_jabatan_tahun'],
                    'C3' => (float)$kelayakan['masa_kerja_tahun'],
                    'C4' => (float)$unitTujuanEval['skor'],
                ];

                $asnDetails[$asn->id] = $asn;
            }

            $filterSummary = [
                'total_pns_unit' => $candidateAsns->count(),
                'lolos_syarat_mutlak' => count($alternatives),
                'gugur_masa_jabatan' => $skippedMasaJabatan,
                'min_masa_jabatan_tahun' => $rules['min_masa_jabatan_tahun'],
            ];

            $criteriaWeights = [];
            foreach ($kriteriaModels as $k) {
                $criteriaWeights[$k->kode_kriteria] = [
                    'bobot' => $k->bobot,
                    'atribut' => $k->atribut,
                ];
            }

            if (!empty($alternatives) && !empty($criteriaWeights)) {
                $alternativesList = array_values($alternatives);
                $topsisResult = $this->topsis->calculate($alternativesList, $criteriaWeights);
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
                    $item['masa_kerja_keluar_ok'] = $altInfo['masa_kerja_keluar_ok'] ?? false;
                    $item['min_masa_jabatan_tahun'] = $altInfo['min_masa_jabatan_tahun'] ?? 2;
                    $item['min_masa_kerja_keluar_tahun'] = $altInfo['min_masa_kerja_keluar_tahun'] ?? 5;
                    $item['unit_asal_eval'] = $altInfo['unit_asal_eval'] ?? $unitTujuanEval;
                    $item['kondisi_unit_asal_skor'] = $altInfo['kondisi_unit_asal_skor'] ?? 2;
                }
                unset($item);
            }
        }

        return compact(
            'unitList',
            'selectedUnit',
            'kriteriaModels',
            'ranked',
            'topsisResult',
            'alternativesList',
            'unitTujuanEval',
            'openFormasiUnit',
            'filterSummary'
        );
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
