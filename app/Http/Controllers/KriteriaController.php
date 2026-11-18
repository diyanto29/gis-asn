<?php

namespace App\Http\Controllers;

use App\Models\TopsisKriteria;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KriteriaController extends Controller
{
    /**
     * Menampilkan halaman Master Kriteria & Pembobotan Dinamis TOPSIS.
     */
    public function index(): View
    {
        $kriteria = TopsisKriteria::orderBy('kode_kriteria')->get();
        $activeKriteria = $kriteria->where('is_active', true);
        $totalBobot = $activeKriteria->sum('bobot');

        return view('master-kriteria.index', compact('kriteria', 'activeKriteria', 'totalBobot'));
    }

    /**
     * Menambah kriteria baru ke dalam sistem.
     */
    public function store(Request $request): RedirectResponse
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

        return redirect()->route('master-kriteria.index')
            ->with('success', "Kriteria baru [{$newCode}] {$validated['nama_kriteria']} berhasil ditambahkan! Silakan gunakan 'Auto-Normalize 100%' agar total bobot tepat 100%.");
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
        return redirect()->route('master-kriteria.index')
            ->with('success', "Kriteria [{$k->kode_kriteria}] {$k->nama_kriteria} berhasil {$status}.");
    }

    /**
     * Memperbarui bobot kriteria aktif dengan validasi ketat total 100%.
     */
    public function updateBobot(Request $request): RedirectResponse
    {
        $bobotInput = $request->input('bobot', []);

        if (empty($bobotInput)) {
            return redirect()->route('master-kriteria.index')
                ->with('error', 'Tidak ada data bobot kriteria yang dikirimkan.');
        }

        $activeKriteria = TopsisKriteria::where('is_active', true)->get();
        $totalInput = 0;

        foreach ($activeKriteria as $k) {
            if (isset($bobotInput[$k->id])) {
                $totalInput += (float)$bobotInput[$k->id];
            }
        }

        // Toleransi floating point 0.999 s.d. 1.001
        if (abs($totalInput - 1.00) > 0.01) {
            return redirect()->route('master-kriteria.index')
                ->with('error', "Gagal menyimpan: Total bobot kriteria aktif adalah " . round($totalInput * 100) . "% (" . number_format($totalInput, 2) . "). Sesuai kaidah ilmiah TOPSIS, total bobot wajib tepat 100% (1.00). Gunakan tombol 'Auto-Normalize' jika ingin sistem membaginya secara otomatis.");
        }

        foreach ($activeKriteria as $k) {
            if (isset($bobotInput[$k->id])) {
                $k->bobot = (float)$bobotInput[$k->id];
                $k->save();
            }
        }

        return redirect()->route('master-kriteria.index')
            ->with('success', 'Pengaturan bobot kriteria TOPSIS berhasil disimpan.');
    }

    /**
     * Menghapus kriteria custom dari sistem.
     */
    public function destroy(int $id): RedirectResponse
    {
        $k = TopsisKriteria::findOrFail($id);
        $code = $k->kode_kriteria;
        $name = $k->nama_kriteria;
        $k->delete();

        return redirect()->route('master-kriteria.index')
            ->with('success', "Kriteria [{$code}] {$name} berhasil dihapus dari sistem.");
    }
}

