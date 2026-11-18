<?php

namespace App\Http\Controllers;

use App\Models\MasterLokasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MasterLokasiController extends Controller
{
    /**
     * Menampilkan daftar master wilayah / kecamatan dan koordinat spasialnya.
     */
    public function index(Request $request): View
    {
        $query = MasterLokasi::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_kecamatan', 'like', "%{$search}%")
                  ->orWhere('kode_kecamatan', 'like', "%{$search}%");
            });
        }

        $lokasiList = $query->orderBy('kode_kecamatan')->get();

        return view('master-lokasi.index', compact('lokasiList'));
    }

    /**
     * Form tambah master wilayah / kecamatan baru.
     */
    public function create(): View
    {
        return view('master-lokasi.create');
    }

    /**
     * Menyimpan data master wilayah / kecamatan baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_kecamatan' => 'required|string|max:20|unique:master_lokasi,kode_kecamatan',
            'nama_kecamatan' => 'required|string|max:100',
            'pusat_lat' => 'required|numeric|between:-90,90',
            'pusat_lng' => 'required|numeric|between:-180,180',
            'jumlah_desa' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        MasterLokasi::create($validated);

        return redirect()->route('master-lokasi.index')
            ->with('success', "Wilayah {$validated['nama_kecamatan']} berhasil ditambahkan ke master data.");
    }

    /**
     * Form edit master wilayah / kecamatan.
     */
    public function edit(int $id): View
    {
        $lokasi = MasterLokasi::findOrFail($id);
        return view('master-lokasi.edit', compact('lokasi'));
    }

    /**
     * Memperbarui data master wilayah / kecamatan.
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $lokasi = MasterLokasi::findOrFail($id);

        $validated = $request->validate([
            'kode_kecamatan' => "required|string|max:20|unique:master_lokasi,kode_kecamatan,{$id}",
            'nama_kecamatan' => 'required|string|max:100',
            'pusat_lat' => 'required|numeric|between:-90,90',
            'pusat_lng' => 'required|numeric|between:-180,180',
            'jumlah_desa' => 'required|integer|min:0',
            'keterangan' => 'nullable|string',
        ]);

        $lokasi->update($validated);

        return redirect()->route('master-lokasi.index')
            ->with('success', "Data wilayah {$lokasi->nama_kecamatan} berhasil diperbarui.");
    }

    /**
     * Menghapus data master wilayah / kecamatan.
     */
    public function destroy(int $id): RedirectResponse
    {
        $lokasi = MasterLokasi::findOrFail($id);
        $nama = $lokasi->nama_kecamatan;
        $lokasi->delete();

        return redirect()->route('master-lokasi.index')
            ->with('success', "Wilayah {$nama} berhasil dihapus dari master data.");
    }
}

