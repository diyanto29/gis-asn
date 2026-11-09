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
        $activeKriteria = $kriteria->where('is_active', true);
        $totalBobot = $activeKriteria->sum('bobot');

        return view('topsis.index', compact('targetUnits', 'kriteria', 'activeKriteria', 'totalBobot'));
    }

    public function storeKriteria(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_kriteria' => 'required|string|max:100',
            'parameter_key' => 'required|string|max:50',
            'atribut' => 'required|in:cost,benefit',
            'bobot' => 'required|numeric|min:0|max:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        // Auto-generate kode_kriteria (C1, C2, C3, ...)
        $existingCodes = TopsisKriteria::pluck('kode_kriteria')->toArray();
        $maxNum = 0;
        foreach ($existingCodes as $code) {
            if (preg_match('/^C(\d+)$/i', $code, $matches)) {
                $maxNum = max($maxNum, (int)$matches[1]);
            }
        }
        $newCode = 'C' . ($maxNum + 1);

        TopsisKriteria::create([
            'kode_kriteria' => $newCode,
            'nama_kriteria' => $validated['nama_kriteria'],
            'parameter_key' => $validated['parameter_key'],
            'atribut' => $validated['atribut'],
            'bobot' => (float)$validated['bobot'],
            'is_active' => true,
            'keterangan' => $validated['keterangan'] ?? 'Kriteria dinamis tambahan.',
        ]);

        return back()->with('success', "Kriteria baru [{$newCode}] {$validated['nama_kriteria']} berhasil ditambahkan! Silakan sesuaikan atau gunakan 'Auto-Normalize 100%' agar total bobot pas 100%.");
    }

    public function toggleKriteria(int $id): RedirectResponse
    {
        $k = TopsisKriteria::findOrFail($id);
        $k->is_active = !$k->is_active;
        if (!$k->is_active) {
            $k->bobot = 0.00;
        }
        $k->save();

        $status = $k->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Kriteria [{$k->kode_kriteria}] {$k->nama_kriteria} berhasil {$status}. Total bobot kriteria aktif akan divalidasi ke 100%.");
    }

    public function destroyKriteria(int $id): RedirectResponse
    {
        $k = TopsisKriteria::findOrFail($id);
        $code = $k->kode_kriteria;
        $name = $k->nama_kriteria;
        $k->delete();

        return back()->with('success', "Kriteria [{$code}] {$name} berhasil dihapus dari sistem.");
    }

    public function updateBobot(Request $request): RedirectResponse
    {
        $bobotInput = $request->input('bobot', []);
        
        if (empty($bobotInput)) {
            return back()->with('error', 'Tidak ada data bobot kriteria yang dikirimkan.');
        }

        // Ambil ID kriteria yang aktif
        $activeIds = TopsisKriteria::where('is_active', true)->pluck('id')->toArray();

        // Konversi jika user menginput dalam format persentase bulat (misal 35, 25, 25, 15 => total 100)
        $rawSum = 0;
        foreach ($bobotInput as $id => $val) {
            if (in_array((int)$id, $activeIds)) {
                $rawSum += (float)$val;
            }
        }
        $isPercentageMode = ($rawSum > 2.0);

        $processed = [];
        $total = 0;
        foreach ($bobotInput as $id => $val) {
            if (in_array((int)$id, $activeIds)) {
                $num = (float)$val;
                $processed[$id] = $isPercentageMode ? ($num / 100.0) : $num;
                $total += $processed[$id];
            }
        }

        // Validasi ketat total bobot harus tepat 1.00 (toleransi desimal 0.99 s/d 1.01)
        if ($total < 0.99 || $total > 1.01) {
            $persenDisplay = round($total * 100, 1);
            return back()->with('error', "Validasi Gagal: Akumulasi total bobot kriteria aktif wajib tepat 100% (1.00). Total input saat ini: {$persenDisplay}% ({$total}). Gunakan tombol 'Auto-Normalize 100%'.");
        }

        // Simpan bobot baru untuk kriteria aktif
        foreach ($processed as $id => $val) {
            TopsisKriteria::where('id', $id)->update(['bobot' => round($val, 4)]);
        }

        return back()->with('success', 'Bobot kriteria aktif berhasil diperbarui (Total: 100%). Perhitungan peringkat TOPSIS akan menggunakan proporsi kriteria ini.');
    }

    public function proses(Request $request): View|RedirectResponse
    {
        $request->validate([
            'unit_tujuan_id' => 'required|exists:unit_kerja,id',
        ]);

        $unitTujuan = UnitKerja::findOrFail($request->unit_tujuan_id);
        
        // 1. Ambil kriteria yang berstatus AKTIF saja
        $kriteriaModels = TopsisKriteria::where('is_active', true)->orderBy('kode_kriteria')->get();

        if ($kriteriaModels->isEmpty()) {
            return back()->with('error', 'Tidak ada kriteria aktif! Silakan aktifkan minimal 1 kriteria di panel kriteria.');
        }

        // Verifikasi total bobot kriteria aktif harus 100% (1.00)
        $totalBobot = $kriteriaModels->sum('bobot');
        if ($totalBobot < 0.99 || $totalBobot > 1.01) {
            $persen = round($totalBobot * 100, 1);
            return back()->with('error', "Kalkulasi Dibatalkan: Akumulasi bobot kriteria aktif saat ini adalah {$persen}%. Wajib tepat 100% (1.00). Silakan klik 'Auto-Normalize 100%' terlebih dahulu.");
        }

        // 2. Ambil kandidat ASN yang bukan dari unit tujuan dan punya titik koordinat domisili
        $candidates = AsnMaster::with('unitSaatIni')
            ->where('id_unit_saat_ini', '!=', $unitTujuan->id)
            ->whereNotNull('domisili_lat')
            ->whereNotNull('domisili_lng')
            ->get();

        if ($candidates->isEmpty()) {
            return view('topsis.hasil', [
                'unitTujuan' => $unitTujuan,
                'kriteria' => [],
                'ranked' => [],
                'details' => [],
                'message' => 'Tidak ditemukan kandidat ASN yang memenuhi syarat koordinat.',
            ]);
        }

        // 3. Siapkan alternatif matriks keputusan secara dinamis untuk setiap kriteria aktif
        $alternatives = [];
        foreach ($candidates as $asn) {
            $jarakBaru = $this->haversine->calculateDistance(
                $asn->domisili_lat,
                $asn->domisili_lng,
                $unitTujuan->kantor_lat,
                $unitTujuan->kantor_lng
            );

            $hematJarak = max(0.0, (float)$asn->jarak_ke_kantor_km - $jarakBaru);

            // Skoring pangkat golongan (II/c = 1 ... IV/c = 9)
            $golScore = match ($asn->pangkat_golongan) {
                'II/c' => 1, 'II/d' => 2,
                'III/a' => 3, 'III/b' => 4, 'III/c' => 5, 'III/d' => 6,
                'IV/a' => 7, 'IV/b' => 8, 'IV/c' => 9,
                default => 3,
            };

            // Estimasi usia dari NIP (8 digit pertama = YYYYMMDD)
            $birthYear = (int)substr($asn->nip, 0, 4);
            $usia = ($birthYear >= 1950 && $birthYear <= 2010) ? (2026 - $birthYear) : 40;

            $altRow = [
                'id' => $asn->id,
                'asn_model' => $asn,
                'nip' => $asn->nip,
                'nama' => $asn->nama_lengkap,
                'unit_asal' => $asn->unitSaatIni->nama_unit ?? '-',
                'jarak_lama' => (float)$asn->jarak_ke_kantor_km,
                'jarak_baru' => (float)$jarakBaru,
                'hemat_jarak' => (float)$hematJarak,
            ];

            // Mapping dinamis untuk setiap kode kriteria aktif (C1, C2, C3, C4, C5, dst.)
            foreach ($kriteriaModels as $k) {
                $code = $k->kode_kriteria;
                $key = $k->parameter_key ?? 'jarak_baru';

                $altRow[$code] = match ($key) {
                    'jarak_baru' => (float)$jarakBaru,
                    'hemat_jarak' => (float)$hematJarak,
                    'masa_kerja' => (float)$asn->masa_kerja_tahun,
                    'pangkat_golongan' => (float)$golScore,
                    'nilai_skp' => (float)($asn->nilai_skp ?? 88.5),
                    'usia_pegawai' => (float)$usia,
                    'tanggungan_keluarga' => (float)($asn->tanggungan_keluarga ?? 2),
                    default => (float)($asn->{$key} ?? 0.0),
                };
            }

            $alternatives[] = $altRow;
        }

        // 4. Siapkan array kriteria untuk TopsisService
        $criteria = [];
        foreach ($kriteriaModels as $k) {
            $criteria[$k->kode_kriteria] = [
                'bobot' => (float)$k->bobot,
                'atribut' => $k->atribut,
                'nama' => $k->nama_kriteria,
                'parameter_key' => $k->parameter_key,
            ];
        }

        // 5. Hitung menggunakan TopsisService secara dinamis
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
