<?php

namespace App\Http\Controllers;

use App\Models\TopsisKriteria;
use App\Services\MutasiRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KriteriaController extends Controller
{
    /**
     * Menampilkan halaman Master Kriteria & Pembobotan Dinamis TOPSIS untuk 2 Mode.
     */
    public function index(Request $request): View
    {
        $currentMode = $request->get('mode', 'pns_to_dinas');
        if (!in_array($currentMode, ['pns_to_dinas', 'dinas_to_pns'])) {
            $currentMode = 'pns_to_dinas';
        }

        $kriteria = TopsisKriteria::where('kategori_mode', $currentMode)->orderBy('kode_kriteria')->get();
        $activeKriteria = $kriteria->where('is_active', true);
        $totalBobot = $activeKriteria->sum('bobot');

        // Statistik ringkas kedua mode untuk tabs
        $countPnsToDinas = TopsisKriteria::where('kategori_mode', 'pns_to_dinas')->count();
        $countDinasToPns = TopsisKriteria::where('kategori_mode', 'dinas_to_pns')->count();
        $mutasiRules = MutasiRuleService::getRules();

        return view('master-kriteria.index', compact(
            'kriteria',
            'activeKriteria',
            'totalBobot',
            'currentMode',
            'countPnsToDinas',
            'countDinasToPns',
            'mutasiRules'
        ));
    }

    /**
     * Memperbarui aturan dinamis mutasi (minimal masa jabatan & minimal masa kerja keluar daerah).
     */
    public function updateAturan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'min_masa_jabatan_tahun' => 'required|numeric|min:0|max:20',
            'min_masa_kerja_keluar_tahun' => 'required|integer|min:1|max:30',
        ]);

        MutasiRuleService::saveRules(
            (float)$validated['min_masa_jabatan_tahun'],
            (int)$validated['min_masa_kerja_keluar_tahun']
        );

        return back()->with('success', "Aturan mutasi berhasil diperbarui: Minimal Masa Jabatan {$validated['min_masa_jabatan_tahun']} tahun & Minimal Masa Kerja Keluar Daerah {$validated['min_masa_kerja_keluar_tahun']} tahun.");
    }

    /**
     * Menambah kriteria baru ke dalam mode tertentu.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kategori_mode' => 'required|in:pns_to_dinas,dinas_to_pns',
            'nama_kriteria' => 'required|string|max:100',
            'parameter_key' => 'required|string|max:50',
            'atribut' => 'required|in:cost,benefit',
            'bobot' => 'required|numeric|min:0|max:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $mode = $validated['kategori_mode'];

        // Auto-generate kode_kriteria per mode (C1, C2, C3, ...)
        $existingCodes = TopsisKriteria::where('kategori_mode', $mode)->pluck('kode_kriteria')->toArray();
        $maxNum = 0;
        foreach ($existingCodes as $code) {
            if (preg_match('/^C(\d+)$/i', $code, $matches)) {
                $maxNum = max($maxNum, (int)$matches[1]);
            }
        }
        $newCode = 'C' . ($maxNum + 1);

        TopsisKriteria::create([
            'kategori_mode' => $mode,
            'kode_kriteria' => $newCode,
            'nama_kriteria' => $validated['nama_kriteria'],
            'parameter_key' => $validated['parameter_key'],
            'atribut' => $validated['atribut'],
            'bobot' => (float)$validated['bobot'],
            'is_active' => true,
            'keterangan' => $validated['keterangan'] ?? 'Kriteria dinamis tambahan.',
        ]);

        return redirect()->route('master-kriteria.index', ['mode' => $mode])
            ->with('success', "Kriteria baru [{$newCode}] {$validated['nama_kriteria']} berhasil ditambahkan pada mode " . ($mode === 'pns_to_dinas' ? 'PNS to Dinas' : 'Dinas to PNS') . "! Silakan gunakan 'Auto-Normalize 100%' agar total bobot tepat 100%.");
    }

    /**
     * Memperbarui detail kriteria (nama, parameter_key, atribut, bobot, keterangan).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $k = TopsisKriteria::findOrFail($id);

        $validated = $request->validate([
            'nama_kriteria' => 'required|string|max:100',
            'parameter_key' => 'required|string|max:50',
            'atribut' => 'required|in:cost,benefit',
            'bobot' => 'required|numeric|min:0|max:1',
            'keterangan' => 'nullable|string|max:255',
            'radius_terdekat_km' => 'nullable|numeric|min:1|max:500',
            'jarak_dekat_max_km' => 'nullable|numeric|min:0.5|max:200',
            'jarak_jauh_min_km' => 'nullable|numeric|min:1|max:500',
            'min_masa_jabatan_tahun' => 'nullable|numeric|min:0|max:20',
            'min_masa_kerja_keluar_tahun' => 'nullable|integer|min:1|max:30',
            'unit_lengkap_min_pct' => 'nullable|numeric|min:1|max:200',
            'unit_cukup_min_pct' => 'nullable|numeric|min:1|max:100',
            'unit_lengkap_skor' => 'nullable|numeric|min:0.1|max:100',
            'unit_cukup_skor' => 'nullable|numeric|min:0.1|max:100',
            'unit_kurang_skor' => 'nullable|numeric|min:0.1|max:100',
        ]);

        $k->update([
            'nama_kriteria' => $validated['nama_kriteria'],
            'parameter_key' => $validated['parameter_key'],
            'atribut' => $validated['atribut'],
            'bobot' => round((float)$validated['bobot'], 4),
            'keterangan' => $validated['keterangan'],
        ]);

        // Simpan nilai subkriteria dinamis sesuai parameter kriteria yang diedit (C1, C2, C3, C5/C4)
        $rules = MutasiRuleService::getRules();
        $extra = [];
        $updatedSub = false;
        $pKey = strtolower($validated['parameter_key']);

        if (str_contains($pKey, 'jarak')) {
            if ($request->filled('radius_terdekat_km')) {
                $extra['radius_terdekat_km'] = round((float)$validated['radius_terdekat_km'], 1);
                $updatedSub = true;
            }
            if ($request->filled('jarak_dekat_max_km')) {
                $extra['jarak_dekat_max_km'] = round((float)$validated['jarak_dekat_max_km'], 1);
                $updatedSub = true;
            }
            if ($request->filled('jarak_jauh_min_km')) {
                $extra['jarak_jauh_min_km'] = round((float)$validated['jarak_jauh_min_km'], 1);
                $updatedSub = true;
            }
        }
        if ($request->filled('min_masa_jabatan_tahun') && str_contains($pKey, 'masa_jabatan')) {
            $rules['min_masa_jabatan_tahun'] = (float)$validated['min_masa_jabatan_tahun'];
            $updatedSub = true;
        }
        if ($request->filled('min_masa_kerja_keluar_tahun') && str_contains($pKey, 'masa_kerja')) {
            $rules['min_masa_kerja_keluar_tahun'] = (int)$validated['min_masa_kerja_keluar_tahun'];
            $updatedSub = true;
        }
        if (str_contains($pKey, 'kebutuhan_unit_asal')) {
            foreach (['unit_lengkap_min_pct', 'unit_cukup_min_pct', 'unit_lengkap_skor', 'unit_cukup_skor', 'unit_kurang_skor'] as $field) {
                if ($request->filled($field)) {
                    $extra[$field] = round((float)$validated[$field], 1);
                    $updatedSub = true;
                }
            }
        }

        if ($updatedSub) {
            MutasiRuleService::saveRules(
                (float)$rules['min_masa_jabatan_tahun'],
                (int)$rules['min_masa_kerja_keluar_tahun'],
                $extra
            );
        }

        return redirect()->route('master-kriteria.index', ['mode' => $k->kategori_mode])
            ->with('success', "Kriteria [{$k->kode_kriteria}] {$k->nama_kriteria}" . ($updatedSub ? ' beserta subkriterianya' : '') . ' berhasil diperbarui.');
    }

    /**
     * Mengaktifkan atau menonaktifkan kriteria.
     */
    public function toggle(int $id): RedirectResponse
    {
        $k = TopsisKriteria::findOrFail($id);
        $k->is_active = !$k->is_active;
        if (!$k->is_active) {
            $k->bobot = 0.00;
        }
        $k->save();

        $status = $k->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('master-kriteria.index', ['mode' => $k->kategori_mode])
            ->with('success', "Kriteria [{$k->kode_kriteria}] {$k->nama_kriteria} berhasil {$status}. Total bobot kriteria aktif akan divalidasi ke 100%.");
    }

    /**
     * Menghapus kriteria kustom.
     */
    public function destroy(int $id): RedirectResponse
    {
        $k = TopsisKriteria::findOrFail($id);
        $code = $k->kode_kriteria;
        $name = $k->nama_kriteria;
        $mode = $k->kategori_mode;
        $k->delete();

        return redirect()->route('master-kriteria.index', ['mode' => $mode])
            ->with('success', "Kriteria [{$code}] {$name} berhasil dihapus dari sistem.");
    }

    /**
     * Memperbarui bobot seluruh kriteria aktif dengan validasi 100%.
     */
    public function updateBobot(Request $request): RedirectResponse
    {
        $bobotInput = $request->input('bobot', []);
        $mode = $request->input('kategori_mode', 'pns_to_dinas');
        
        if (empty($bobotInput)) {
            return back()->with('error', 'Tidak ada data bobot kriteria yang dikirimkan.');
        }

        $activeIds = TopsisKriteria::where('kategori_mode', $mode)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        // Cek apakah user input dalam skala 100 atau 1.00
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
            return back()->with('error', "Validasi Gagal: Akumulasi total bobot kriteria aktif wajib tepat 100% (1.00). Total input saat ini: {$persenDisplay}% ({$total}). Silakan klik tombol 'Auto-Normalize 100%'.");
        }

        // Simpan bobot baru
        foreach ($processed as $id => $val) {
            TopsisKriteria::where('id', $id)->update(['bobot' => round($val, 4)]);
        }

        return redirect()->route('master-kriteria.index', ['mode' => $mode])
            ->with('success', 'Bobot kriteria aktif berhasil diperbarui (Total: 100%). Perhitungan peringkat TOPSIS akan menggunakan proporsi kriteria ini.');
    }
}
