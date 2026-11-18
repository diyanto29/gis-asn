<?php

namespace App\Http\Controllers;

use App\Models\FormasiJabatan;
use App\Models\UnitKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FormasiJabatanController extends Controller
{
    /**
     * Menampilkan daftar master formasi jabatan dan rekam jejak tracing tahunan.
     */
    public function index(Request $request): View
    {
        $query = FormasiJabatan::with('unitKerja');

        // Filter Tahun Anggaran (Tracing Riwayat)
        $selectedYear = $request->input('tahun', 2026);
        if ($selectedYear !== 'all' && !empty($selectedYear)) {
            $query->where('tahun_anggaran', (int)$selectedYear);
        }

        // Filter Unit Kerja
        if ($request->filled('unit_id')) {
            $query->where('id_unit_kerja', $request->unit_id);
        }

        // Filter Status Formasi
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pencarian Nama Jabatan
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_jabatan', 'like', "%{$search}%")
                  ->orWhere('kualifikasi_pendidikan', 'like', "%{$search}%");
            });
        }

        $formasiList = $query->orderBy('id_unit_kerja')->orderBy('nama_jabatan')->get();

        // Data Penunjang Filter & Statistik
        $availableYears = FormasiJabatan::select('tahun_anggaran')
            ->distinct()
            ->orderBy('tahun_anggaran', 'desc')
            ->pluck('tahun_anggaran')
            ->toArray();

        if (!in_array(2026, $availableYears)) {
            $availableYears[] = 2026;
            rsort($availableYears);
        }

        $units = UnitKerja::orderBy('nama_unit')->get();

        // Ringkasan Statistik Tracing
        $statsQuery = FormasiJabatan::query();
        if ($selectedYear !== 'all' && !empty($selectedYear)) {
            $statsQuery->where('tahun_anggaran', (int)$selectedYear);
        }
        $totalFormasi = (clone $statsQuery)->count();
        $formasiTerbuka = (clone $statsQuery)->where('status', 'terbuka')->count();
        $totalLowong = (clone $statsQuery)->where('status', 'terbuka')->sum('kuota_lowong');
        $formasiTerisi = (clone $statsQuery)->where('status', 'terisi')->count();

        return view('formasi-jabatan.index', compact(
            'formasiList',
            'units',
            'availableYears',
            'selectedYear',
            'totalFormasi',
            'formasiTerbuka',
            'totalLowong',
            'formasiTerisi'
        ));
    }

    /**
     * Form buka formasi jabatan baru.
     */
    public function create(): View
    {
        $units = UnitKerja::orderBy('nama_unit')->get();
        return view('formasi-jabatan.create', compact('units'));
    }

    /**
     * Menyimpan data formasi jabatan baru dan sinkronisasi kuota unit kerja.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_unit_kerja' => 'required|exists:unit_kerja,id',
            'tahun_anggaran' => 'required|integer|min:2020|max:2035',
            'periode' => 'required|string|max:50',
            'nama_jabatan' => 'required|string|max:150',
            'jenjang_jabatan' => 'nullable|string|max:50',
            'kualifikasi_pendidikan' => 'nullable|string|max:150',
            'kebutuhan_abk' => 'required|integer|min:1',
            'bezetting_riil' => 'required|integer|min:0',
            'kuota_lowong' => 'nullable|integer|min:0',
            'status' => 'required|in:terbuka,terisi,ditutup',
            'keterangan' => 'nullable|string',
        ]);

        // Standar Regulasi: Kuota lowong = max(0, Kebutuhan ABK - Bezetting Riil)
        // Jika status ditutup / terisi, kuota efektif menjadi 0
        if ($validated['status'] === 'terbuka') {
            $validated['kuota_lowong'] = max(0, (int)$validated['kebutuhan_abk'] - (int)$validated['bezetting_riil']);
        } else {
            $validated['kuota_lowong'] = 0;
        }

        $formasi = FormasiJabatan::create($validated);

        // Auto-sync kuota formasi unit kerja pada tahun anggaran terkait
        $unit = UnitKerja::find($validated['id_unit_kerja']);
        if ($unit) {
            $unit->syncKuotaFormasi((int)$validated['tahun_anggaran']);
        }

        return redirect()->route('formasi-jabatan.index', ['tahun' => $validated['tahun_anggaran']])
            ->with('success', "Formasi jabatan {$formasi->nama_jabatan} untuk tahun {$formasi->tahun_anggaran} berhasil didaftarkan.");
    }

    /**
     * Form edit data formasi jabatan.
     */
    public function edit(int $id): View
    {
        $formasi = FormasiJabatan::with('unitKerja')->findOrFail($id);
        $units = UnitKerja::orderBy('nama_unit')->get();
        return view('formasi-jabatan.edit', compact('formasi', 'units'));
    }

    /**
     * Memperbarui data formasi jabatan dan sinkronisasi kuota unit kerja.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $formasi = FormasiJabatan::findOrFail($id);

        $validated = $request->validate([
            'id_unit_kerja' => 'required|exists:unit_kerja,id',
            'tahun_anggaran' => 'required|integer|min:2020|max:2035',
            'periode' => 'required|string|max:50',
            'nama_jabatan' => 'required|string|max:150',
            'jenjang_jabatan' => 'nullable|string|max:50',
            'kualifikasi_pendidikan' => 'nullable|string|max:150',
            'kebutuhan_abk' => 'required|integer|min:1',
            'bezetting_riil' => 'required|integer|min:0',
            'kuota_lowong' => 'nullable|integer|min:0',
            'status' => 'required|in:terbuka,terisi,ditutup',
            'keterangan' => 'nullable|string',
        ]);

        // Standar Regulasi: Kuota lowong = max(0, Kebutuhan ABK - Bezetting Riil)
        if ($validated['status'] === 'terbuka') {
            $validated['kuota_lowong'] = max(0, (int)$validated['kebutuhan_abk'] - (int)$validated['bezetting_riil']);
        } else {
            $validated['kuota_lowong'] = 0;
        }

        $formasi->update($validated);

        // Auto-sync kuota formasi unit kerja
        $unit = UnitKerja::find($validated['id_unit_kerja']);
        if ($unit) {
            $unit->syncKuotaFormasi((int)$validated['tahun_anggaran']);
        }

        return redirect()->route('formasi-jabatan.index', ['tahun' => $validated['tahun_anggaran']])
            ->with('success', "Formasi jabatan {$formasi->nama_jabatan} berhasil diperbarui.");
    }

    /**
     * Menghapus data formasi jabatan dan sinkronisasi kuota unit kerja.
     */
    public function destroy(int $id): RedirectResponse
    {
        $formasi = FormasiJabatan::findOrFail($id);
        $unitId = $formasi->id_unit_kerja;
        $tahun = $formasi->tahun_anggaran;
        $nama = $formasi->nama_jabatan;

        $formasi->delete();

        // Auto-sync kuota formasi unit kerja setelah dihapus
        $unit = UnitKerja::find($unitId);
        if ($unit) {
            $unit->syncKuotaFormasi($tahun);
        }

        return redirect()->route('formasi-jabatan.index', ['tahun' => $tahun])
            ->with('success', "Formasi {$nama} berhasil dihapus dari rekam jejak tahun {$tahun}.");
    }
}

