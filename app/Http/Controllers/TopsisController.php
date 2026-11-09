<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
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

    public function index(): View
    {
        // Hanya unit kerja yang punya kuota formasi lowong (> 0) yang bisa jadi tujuan mutasi
        $targetUnits = UnitKerja::where('kuota_formasi_kosong', '>', 0)
            ->orderBy('nama_unit')
            ->get();

        $kriteria = TopsisKriteria::orderBy('kode_kriteria')->get();
        $totalBobot = $kriteria->sum('bobot');

        return view('topsis.index', compact('targetUnits', 'kriteria', 'totalBobot'));
    }

    public function updateBobot(Request $request): RedirectResponse
    {
        $bobotInput = $request->input('bobot', []);
        
        if (empty($bobotInput)) {
            return back()->with('error', 'Tidak ada data bobot kriteria yang dikirimkan.');
        }

        // Konversi jika user menginput dalam format persentase bulat (misal 35, 25, 25, 15 => total 100)
        $rawSum = array_sum($bobotInput);
        $isPercentageMode = ($rawSum > 2.0);

        $processed = [];
        foreach ($bobotInput as $id => $val) {
            $num = (float)$val;
            $processed[$id] = $isPercentageMode ? ($num / 100.0) : $num;
        }

        $total = array_sum($processed);

        // Validasi ketat total bobot harus tepat 1.00 (toleransi desimal 0.99 s/d 1.01)
        if ($total < 0.99 || $total > 1.01) {
            $persenDisplay = round($total * 100, 1);
            return back()->with('error', "Validasi Gagal: Akumulasi total bobot seluruh kriteria wajib tepat 100% (1.00). Total yang Anda masukkan saat ini: {$persenDisplay}% ({$total}). Silakan sesuaikan atau gunakan tombol 'Auto-Normalize'.");
        }

        // Simpan bobot baru
        foreach ($processed as $id => $val) {
            TopsisKriteria::where('id', $id)->update(['bobot' => round($val, 4)]);
        }

        return back()->with('success', 'Bobot kriteria TOPSIS berhasil diperbarui (Total: 100%). Perhitungan peringkat selanjutnya akan menggunakan proporsi bobot baru ini.');
    }

    public function proses(Request $request): View
    {
        $request->validate([
            'unit_tujuan_id' => 'required|exists:unit_kerja,id',
        ]);

        $unitTujuan = UnitKerja::findOrFail($request->unit_tujuan_id);
        $kriteriaModels = TopsisKriteria::all();

        // 1. Ambil kandidat ASN yang bukan dari unit tujuan dan punya titik koordinat domisili
        $candidates = AsnMaster::with('unitSaatIni')
            ->where('id_unit_saat_ini', '!=', $unitTujuan->id)
            ->whereNotNull('domisili_lat')
            ->whereNotNull('domisili_lng')
            ->get();

        if ($candidates->isEmpty()) {
            return view('topsis.hasil', [
                'unitTujuan' => $unitTujuan,
                'ranked' => [],
                'details' => [],
                'message' => 'Tidak ditemukan kandidat ASN yang memenuhi syarat koordinat.',
            ]);
        }

        // 2. Siapkan alternatif matriks keputusan
        $alternatives = [];
        foreach ($candidates as $asn) {
            $jarakBaru = $this->haversine->calculateDistance(
                $asn->domisili_lat,
                $asn->domisili_lng,
                $unitTujuan->kantor_lat,
                $unitTujuan->kantor_lng
            );

            $hematJarak = max(0.0, (float)$asn->jarak_ke_kantor_km - $jarakBaru);

            // Skoring pangkat golongan (III/a = 1 ... IV/a = 4)
            $golScore = match ($asn->pangkat_golongan) {
                'II/c' => 1, 'II/d' => 2,
                'III/a' => 3, 'III/b' => 4, 'III/c' => 5, 'III/d' => 6,
                'IV/a' => 7, 'IV/b' => 8, 'IV/c' => 9,
                default => 3,
            };

            $alternatives[] = [
                'id' => $asn->id,
                'asn_model' => $asn,
                'nip' => $asn->nip,
                'nama' => $asn->nama_lengkap,
                'unit_asal' => $asn->unitSaatIni->nama_unit,
                'jarak_lama' => (float)$asn->jarak_ke_kantor_km,
                'C1' => $jarakBaru,
                'C2' => (float)$asn->masa_kerja_tahun,
                'C3' => (float)$golScore,
                'C4' => $hematJarak,
            ];
        }

        // 3. Siapkan kriteria
        $criteria = [];
        foreach ($kriteriaModels as $k) {
            $criteria[$k->kode_kriteria] = [
                'bobot' => (float)$k->bobot,
                'atribut' => $k->atribut,
                'nama' => $k->nama_kriteria,
            ];
        }

        // 4. Hitung menggunakan TopsisService
        $topsisResult = $this->topsis->calculate($alternatives, $criteria);

        return view('topsis.hasil', [
            'unitTujuan' => $unitTujuan,
            'kriteria' => $criteria,
            'ranked' => $topsisResult['ranked'],
            'details' => $topsisResult['details'],
        ]);
    }

    public function simpanRekomendasi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'unit_tujuan_id' => 'required|exists:unit_kerja,id',
            'ranks' => 'required|array',
        ]);

        $unitTujuan = UnitKerja::findOrFail($validated['unit_tujuan_id']);
        $now = now();

        foreach ($validated['ranks'] as $item) {
            RekomendasiMutasi::updateOrCreate(
                [
                    'id_asn' => $item['id_asn'],
                    'id_unit_tujuan' => $unitTujuan->id,
                ],
                [
                    'id_unit_asal' => $item['id_unit_asal'],
                    'jarak_asal_km' => $item['jarak_asal_km'],
                    'jarak_baru_km' => $item['jarak_baru_km'],
                    'selisih_hemat_jarak_km' => $item['selisih_hemat_jarak_km'],
                    'nilai_preferensi_v' => $item['nilai_preferensi_v'],
                    'ranking' => $item['ranking'],
                    'status_usulan' => 'draft',
                    'tanggal_kalkulasi' => $now,
                ]
            );
        }

        return redirect()->route('topsis.riwayat')
            ->with('success', "Rekomendasi mutasi menuju {$unitTujuan->nama_unit} berhasil disimpan ke basis data Baperjakat.");
    }

    public function riwayat(): View
    {
        $riwayat = RekomendasiMutasi::with(['asn', 'unitAsal', 'unitTujuan'])
            ->orderBy('id_unit_tujuan')
            ->orderBy('ranking')
            ->paginate(20);

        return view('topsis.riwayat', compact('riwayat'));
    }
}
