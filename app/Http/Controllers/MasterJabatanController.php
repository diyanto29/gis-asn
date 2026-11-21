<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\MasterJabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterJabatanController extends Controller
{
    /**
     * Menampilkan katalog master referensi jabatan standar ASN (Pelaksana & Fungsional).
     */
    public function index(Request $request): View
    {
        $query = MasterJabatan::query();

        // Filter Jenis Jabatan
        if ($request->filled('jenis') && $request->jenis !== 'all') {
            $query->where('jenis_jabatan', $request->jenis);
        }

        // Pencarian Nama Jabatan / Kode
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_jabatan', 'like', "%{$search}%")
                  ->orWhere('kode_jabatan', 'like', "%{$search}%")
                  ->orWhere('jenjang_jabatan', 'like', "%{$search}%");
            });
        }

        $jabatanList = $query->withCount('asns')
            ->orderBy('nama_jabatan')
            ->get();

        // Statistik Master
        $totalJabatan = MasterJabatan::count();
        $totalPelaksana = MasterJabatan::where('jenis_jabatan', 'pelaksana')->count();
        $totalFungsional = MasterJabatan::where('jenis_jabatan', 'fungsional')->count();
        $totalStruktural = MasterJabatan::where('jenis_jabatan', 'struktural')->count();

        return view('master-jabatan.index', compact(
            'jabatanList',
            'totalJabatan',
            'totalPelaksana',
            'totalFungsional',
            'totalStruktural'
        ));
    }

    /**
     * Menampilkan form penambahan master jabatan baru.
     */
    public function create(): View
    {
        return view('master-jabatan.create');
    }

    /**
     * Menyimpan master jabatan baru ke basis data.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:master_jabatan,nama_jabatan',
            'kode_jabatan' => 'nullable|string|max:50|unique:master_jabatan,kode_jabatan',
            'jenis_jabatan' => 'required|in:pelaksana,fungsional,struktural',
            'jenjang_jabatan' => 'nullable|string|max:100',
            'rumpun_jabatan' => 'nullable|string|max:150',
            'kualifikasi_pendidikan' => 'nullable|string',
        ]);

        MasterJabatan::create($validated);

        return redirect()->route('master-jabatan.index')
            ->with('success', 'Master Jabatan "' . $validated['nama_jabatan'] . '" berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit master jabatan.
     */
    public function edit(int $id): View
    {
        $jabatan = MasterJabatan::findOrFail($id);
        return view('master-jabatan.edit', compact('jabatan'));
    }

    /**
     * Memperbarui data master jabatan.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $jabatan = MasterJabatan::findOrFail($id);

        $validated = $request->validate([
            'nama_jabatan' => 'required|string|max:255|unique:master_jabatan,nama_jabatan,' . $id,
            'kode_jabatan' => 'nullable|string|max:50|unique:master_jabatan,kode_jabatan,' . $id,
            'jenis_jabatan' => 'required|in:pelaksana,fungsional,struktural',
            'jenjang_jabatan' => 'nullable|string|max:100',
            'rumpun_jabatan' => 'nullable|string|max:150',
            'kualifikasi_pendidikan' => 'nullable|string',
        ]);

        $oldName = $jabatan->nama_jabatan;
        $jabatan->update($validated);

        // Jika nama jabatan diperbarui, sinkronkan nama di asn_master yang berelasi
        if ($oldName !== $validated['nama_jabatan']) {
            AsnMaster::where('id_master_jabatan', $id)
                ->update(['jabatan' => $validated['nama_jabatan'], 'jenis_jabatan' => $validated['jenis_jabatan']]);
        }

        return redirect()->route('master-jabatan.index')
            ->with('success', 'Master Jabatan "' . $jabatan->nama_jabatan . '" berhasil diperbarui.');
    }

    /**
     * Menghapus master jabatan jika tidak sedang digunakan oleh data ASN.
     */
    public function destroy(int $id): RedirectResponse
    {
        $jabatan = MasterJabatan::withCount('asns')->findOrFail($id);

        if ($jabatan->asns_count > 0) {
            return redirect()->route('master-jabatan.index')
                ->with('error', 'Gagal menghapus: Jabatan "' . $jabatan->nama_jabatan . '" sedang digunakan oleh ' . $jabatan->asns_count . ' data pegawai ASN.');
        }

        $nama = $jabatan->nama_jabatan;
        $jabatan->delete();

        return redirect()->route('master-jabatan.index')
            ->with('success', 'Master Jabatan "' . $nama . '" berhasil dihapus.');
    }
}
