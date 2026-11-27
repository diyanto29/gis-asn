<?php

namespace App\Http\Controllers;

use App\Models\FormasiJabatan;
use App\Models\MasterJabatan;
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
        $query = FormasiJabatan::with(['unitKerja', 'masterJabatan']);

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
        $masterJabatans = MasterJabatan::orderBy('nama_jabatan')->get();
        return view('formasi-jabatan.create', compact('units', 'masterJabatans'));
    }

    /**
     * Menyimpan data formasi jabatan baru (atribut jabatan otomatis dari MasterJabatan,
     * bezetting & kuota lowong dihitung otomatis dari pemangku PNS).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_unit_kerja' => 'required|exists:unit_kerja,id',
            'id_master_jabatan' => 'required|exists:master_jabatan,id',
            'tahun_anggaran' => 'required|integer|min:2020|max:2035',
            'kebutuhan_abk' => 'required|integer|min:1',
        ]);

        $master = MasterJabatan::findOrFail($validated['id_master_jabatan']);

        $formasi = FormasiJabatan::create([
            'id_unit_kerja' => $validated['id_unit_kerja'],
            'id_master_jabatan' => $master->id,
            'tahun_anggaran' => $validated['tahun_anggaran'],
            'periode' => 'Tahun ' . $validated['tahun_anggaran'],
            'nama_jabatan' => $master->nama_jabatan,
            'jenis_jabatan' => $master->jenis_jabatan,
            'jenjang_jabatan' => $master->jenjang_jabatan,
            'kualifikasi_pendidikan' => $master->kualifikasi_pendidikan,
            'kebutuhan_abk' => (int)$validated['kebutuhan_abk'],
            'bezetting_riil' => 0,
            'kuota_lowong' => (int)$validated['kebutuhan_abk'],
            'status' => 'terbuka',
            'keterangan' => null,
        ]);

        // Hitung otomatis bezetting riil dari pemangku PNS, kuota lowong, status, & sync unit kerja
        $formasi->recalculateBezetting();

        return redirect()->route('formasi-jabatan.index', ['tahun' => $validated['tahun_anggaran']])
            ->with('success', "Formasi jabatan {$formasi->nama_jabatan} untuk tahun {$formasi->tahun_anggaran} berhasil didaftarkan (Bezetting: {$formasi->bezetting_riil}, Lowong: {$formasi->kuota_lowong}).");
    }

    /**
     * Form edit data formasi jabatan.
     */
    public function edit(int $id): View
    {
        $formasi = FormasiJabatan::with(['unitKerja', 'masterJabatan'])->findOrFail($id);
        $units = UnitKerja::orderBy('nama_unit')->get();
        $masterJabatans = MasterJabatan::orderBy('nama_jabatan')->get();
        return view('formasi-jabatan.edit', compact('formasi', 'units', 'masterJabatans'));
    }

    /**
     * Memperbarui data formasi jabatan dan hitung ulang bezetting/kuota otomatis.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $formasi = FormasiJabatan::findOrFail($id);

        $validated = $request->validate([
            'id_unit_kerja' => 'required|exists:unit_kerja,id',
            'id_master_jabatan' => 'required|exists:master_jabatan,id',
            'tahun_anggaran' => 'required|integer|min:2020|max:2035',
            'kebutuhan_abk' => 'required|integer|min:1',
        ]);

        $master = MasterJabatan::findOrFail($validated['id_master_jabatan']);
        $oldUnitId = $formasi->id_unit_kerja;
        $oldTahun = (int)$formasi->tahun_anggaran;

        $formasi->update([
            'id_unit_kerja' => $validated['id_unit_kerja'],
            'id_master_jabatan' => $master->id,
            'tahun_anggaran' => $validated['tahun_anggaran'],
            'periode' => 'Tahun ' . $validated['tahun_anggaran'],
            'nama_jabatan' => $master->nama_jabatan,
            'jenis_jabatan' => $master->jenis_jabatan,
            'jenjang_jabatan' => $master->jenjang_jabatan,
            'kualifikasi_pendidikan' => $master->kualifikasi_pendidikan,
            'kebutuhan_abk' => (int)$validated['kebutuhan_abk'],
        ]);

        $formasi->recalculateBezetting();

        if ($oldUnitId != $formasi->id_unit_kerja) {
            $oldUnit = UnitKerja::find($oldUnitId);
            $oldUnit?->syncKuotaFormasi($oldTahun);
        }

        return redirect()->route('formasi-jabatan.index', ['tahun' => $validated['tahun_anggaran']])
            ->with('success', "Formasi jabatan {$formasi->nama_jabatan} berhasil diperbarui (Bezetting: {$formasi->bezetting_riil}, Lowong: {$formasi->kuota_lowong}).");
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

