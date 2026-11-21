<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\RekomendasiMutasi;
use App\Models\TopsisKriteria;
use App\Models\UnitKerja;
use App\Services\HaversineService;
use App\Services\TopsisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TopsisController extends Controller
{
    public function __construct(
        protected HaversineService $haversine,
        protected TopsisService $topsis
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
     * =========================================================================
     */
    public function pnsToDinas(Request $request): View
    {
        $asnList = AsnMaster::with('unitSaatIni')->orderBy('nama')->get();
        $selectedAsnId = $request->get('asn_id');

        $kriteriaModels = TopsisKriteria::where('kategori_mode', 'pns_to_dinas')
            ->where('is_active', true)
            ->orderBy('kode_kriteria')
            ->get();

        $selectedAsn = null;
        $topsisResult = null;
        $ranked = [];

        if ($selectedAsnId) {
            $selectedAsn = AsnMaster::with(['unitSaatIni', 'formasiJabatan'])->findOrFail($selectedAsnId);

            // Cari alternatif unit kerja tujuan yang bukan unit saat ini
            $candidateUnits = UnitKerja::where('id', '!=', $selectedAsn->id_unit_saat_ini)
                ->whereNotNull('kantor_lat')
                ->whereNotNull('kantor_lng')
                ->get();

            // Kumpulkan data kualifikasi formasi linear untuk C5
            $openFormasiUnitIds = FormasiJabatan::where('status', 'terbuka')
                ->where('kuota_lowong', '>', 0)
                ->pluck('id_unit_kerja')
                ->toArray();

            // Hitung masa jabatan dan masa kerja ASN
            $tglJabatan = $selectedAsn->tgl_jabatan ?: $selectedAsn->tmt_kerja_unit;
            $masaJabatanTahun = $tglJabatan ? max(0.5, round((time() - strtotime($tglJabatan)) / (365.25 * 86400), 2)) : 2.0;
            
            $tglCpns = $selectedAsn->tgl_cpns;
            $masaKerjaTahun = $tglCpns ? max(1, round((time() - strtotime($tglCpns)) / (365.25 * 86400), 2)) : ($selectedAsn->masa_kerja_tahun ?: 5);

            $alternatives = [];
            $unitDetails = [];

            foreach ($candidateUnits as $unit) {
                $jarakBaru = $this->haversine->calculateDistance(
                    $selectedAsn->domisili_lat,
                    $selectedAsn->domisili_lng,
                    $unit->kantor_lat,
                    $unit->kantor_lng
                );

                $jarakLama = $selectedAsn->jarak_ke_kantor_km ?: 15.0;
                $hematJarak = round(max(0, $jarakLama - $jarakBaru), 2);

                // Kebutuhan staf dinas tujuan (ABK - Bezetting)
                $kebutuhanStaf = max(0, $unit->kebutuhan_abk - $unit->bezetting_saat_ini);
                if ($kebutuhanStaf === 0 && $unit->kuota_formasi_kosong > 0) {
                    $kebutuhanStaf = $unit->kuota_formasi_kosong;
                }

                // C5 Formasi Jabatan Linear (1 = Ada Kuota Formasi, 0 = Tidak Ada)
                $hasLinearFormasi = in_array($unit->id, $openFormasiUnitIds) || ($unit->kuota_formasi_kosong > 0) ? 1.0 : 0.0;

                $unitKey = 'unit_' . $unit->id;
                $alternatives[$unitKey] = [
                    'id' => $unit->id,
                    'nama_unit' => $unit->nama_unit,
                    'jarak_baru_km' => $jarakBaru,
                    'hemat_jarak_km' => $hematJarak,
                    'kebutuhan_staf' => $kebutuhanStaf,
                    'has_formasi' => $hasLinearFormasi,
                    
                    // Matriks kriteria untuk TOPSIS
                    'C1' => $jarakBaru,               // Cost
                    'C2' => $masaJabatanTahun,        // Benefit
                    'C3' => $masaKerjaTahun,          // Benefit
                    'C4' => (float)$kebutuhanStaf,    // Benefit
                    'C5' => $hasLinearFormasi,        // Benefit
                ];

                $unitDetails[$unit->id] = $unit;
            }

            // Bobot kriteria
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

                // Attach detail unit kerja ke hasil ranking
                foreach ($ranked as &$item) {
                    $altId = $item['alternative']['id'] ?? null;
                    $item['id'] = $altId;
                    $item['vi'] = $item['v_value'];
                    $item['unit'] = $unitDetails[$altId] ?? null;
                    $altInfo = $alternatives['unit_' . $altId] ?? null;
                    $item['jarak_baru_km'] = $altInfo['jarak_baru_km'] ?? 0;
                    $item['hemat_jarak_km'] = $altInfo['hemat_jarak_km'] ?? 0;
                    $item['kebutuhan_staf'] = $altInfo['kebutuhan_staf'] ?? 0;
                    $item['has_formasi'] = $altInfo['has_formasi'] ?? 0;
                }
                unset($item);
            }
        }

        return view('topsis.pns-to-dinas', compact(
            'asnList',
            'selectedAsn',
            'kriteriaModels',
            'ranked',
            'topsisResult'
        ));
    }

    /**
     * =========================================================================
     * MODE 2: REKOMENDASI SPK "DINAS TO PNS"
     * Skenario: Suatu Dinas/Unit Kerja mencari kandidat ASN terbaik se-kabupaten.
     * =========================================================================
     */
    public function dinasToPns(Request $request): View
    {
        // Tampilkan daftar unit kerja
        $unitList = UnitKerja::orderBy('nama_unit')->get();
        $selectedUnitId = $request->get('unit_id');

        $kriteriaModels = TopsisKriteria::where('kategori_mode', 'dinas_to_pns')
            ->where('is_active', true)
            ->orderBy('kode_kriteria')
            ->get();

        $selectedUnit = null;
        $topsisResult = null;
        $ranked = [];

        if ($selectedUnitId) {
            $selectedUnit = UnitKerja::findOrFail($selectedUnitId);

            // Kandidat adalah seluruh ASN yang bukan dari unit ini dan memiliki titik domisili
            $candidateAsns = AsnMaster::with('unitSaatIni')
                ->where('id_unit_saat_ini', '!=', $selectedUnit->id)
                ->whereNotNull('domisili_lat')
                ->whereNotNull('domisili_lng')
                ->get();

            $alternatives = [];
            $asnDetails = [];

            foreach ($candidateAsns as $asn) {
                // Jarak domisili ASN ke dinas pemohon
                $jarakKeDinas = $this->haversine->calculateDistance(
                    $asn->domisili_lat,
                    $asn->domisili_lng,
                    $selectedUnit->kantor_lat,
                    $selectedUnit->kantor_lng
                );

                $jarakLama = $asn->jarak_ke_kantor_km ?: 15.0;
                $hematJarak = round(max(0, $jarakLama - $jarakKeDinas), 2);

                // C2: Masa Jabatan ASN di unit asal (tahun)
                $tglJabatan = $asn->tgl_jabatan ?: $asn->tmt_kerja_unit;
                $masaJabatanTahun = $tglJabatan ? max(0.5, round((time() - strtotime($tglJabatan)) / (365.25 * 86400), 2)) : 2.0;

                // C3: Masa Kerja PNS sejak CPNS (tahun)
                $tglCpns = $asn->tgl_cpns;
                $masaKerjaTahun = $tglCpns ? max(1, round((time() - strtotime($tglCpns)) / (365.25 * 86400), 2)) : ($asn->masa_kerja_tahun ?: 5);

                // C4: Kondisi Pegawai Unit Asal (Beban ABK)
                // Jika unit asalnya surplus (bezetting > abk) => Nilai 3
                // Jika cukup (bezetting == abk) => Nilai 2
                // Jika kurang (bezetting < abk) => Nilai 1
                $kondisiUnitAsal = 2.0;
                if ($asn->unitSaatIni) {
                    $u = $asn->unitSaatIni;
                    if ($u->bezetting_saat_ini > $u->kebutuhan_abk) {
                        $kondisiUnitAsal = 3.0; // Surplus (Sangat aman ditarik)
                    } elseif ($u->bezetting_saat_ini === $u->kebutuhan_abk) {
                        $kondisiUnitAsal = 2.0; // Cukup
                    } else {
                        $kondisiUnitAsal = 1.0; // Kurang (Risiko defisit staf)
                    }
                }

                $asnKey = 'asn_' . $asn->id;
                $alternatives[$asnKey] = [
                    'id' => $asn->id,
                    'nama' => $asn->nama,
                    'nip' => $asn->nip,
                    'jabatan' => $asn->jabatan,
                    'jarak_ke_dinas_km' => $jarakKeDinas,
                    'hemat_jarak_km' => $hematJarak,
                    'masa_jabatan_tahun' => $masaJabatanTahun,
                    'masa_kerja_tahun' => $masaKerjaTahun,
                    'kondisi_unit_asal_skor' => $kondisiUnitAsal,

                    // Matriks kriteria untuk TOPSIS
                    'C1' => $jarakKeDinas,           // Cost
                    'C2' => $masaJabatanTahun,       // Benefit
                    'C3' => $masaKerjaTahun,         // Benefit
                    'C4' => $kondisiUnitAsal,        // Benefit
                ];

                $asnDetails[$asn->id] = $asn;
            }

            // Bobot kriteria
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

                // Attach detail ASN ke hasil ranking
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
            'topsisResult'
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
