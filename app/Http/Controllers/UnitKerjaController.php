<?php

namespace App\Http\Controllers;

use App\Models\UnitKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitKerjaController extends Controller
{
    public function index(Request $request): View
    {
        $query = UnitKerja::withCount('pegawai');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_unit', 'like', "%{$search}%")
                  ->orWhere('kode_unit', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $units = $query->orderBy('nama_unit')->get();

        return view('unit-kerja.index', compact('units'));
    }

    public function create(): View
    {
        return view('unit-kerja.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode_unit' => 'required|string|max:50|unique:unit_kerja,kode_unit',
            'nama_unit' => 'required|string|max:150',
            'kategori' => 'required|string|max:50',
            'kantor_lat' => 'required|numeric|between:-90,90',
            'kantor_lng' => 'required|numeric|between:-180,180',
            'alamat_kantor' => 'nullable|string',
            'kuota_formasi_kosong' => 'nullable|integer|min:0',
            'kebutuhan_abk' => 'nullable|integer|min:0',
            'bezetting_saat_ini' => 'nullable|integer|min:0',
        ]);

        $validated['kuota_formasi_kosong'] = $validated['kuota_formasi_kosong'] ?? 0;
        $validated['kebutuhan_abk'] = $validated['kebutuhan_abk'] ?? 0;
        $validated['bezetting_saat_ini'] = $validated['bezetting_saat_ini'] ?? 0;

        UnitKerja::create($validated);

        return redirect()->route('unit-kerja.index')
            ->with('success', "Unit kerja {$validated['nama_unit']} berhasil ditambahkan.");
    }

    public function edit(int $id): View
    {
        $unit = UnitKerja::withCount('pegawai')->findOrFail($id);
        return view('unit-kerja.edit', compact('unit'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'nama_unit' => 'required|string|max:150',
            'kategori' => 'required|string|max:50',
            'kantor_lat' => 'required|numeric|between:-90,90',
            'kantor_lng' => 'required|numeric|between:-180,180',
            'alamat_kantor' => 'nullable|string',
            'kuota_formasi_kosong' => 'nullable|integer|min:0',
            'kebutuhan_abk' => 'nullable|integer|min:0',
            'bezetting_saat_ini' => 'nullable|integer|min:0',
        ]);

        $unit = UnitKerja::findOrFail($id);
        $unit->update($validated);

        return redirect()->route('unit-kerja.index')
            ->with('success', "Data unit kerja {$unit->nama_unit} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $unit = UnitKerja::withCount('pegawai')->findOrFail($id);

        if ($unit->pegawai_count > 0) {
            return redirect()->route('unit-kerja.index')
                ->with('error', "Unit kerja {$unit->nama_unit} tidak dapat dihapus karena masih menaungi {$unit->pegawai_count} pegawai ASN aktif.");
        }

        $nama = $unit->nama_unit;
        $unit->delete();

        return redirect()->route('unit-kerja.index')
            ->with('success', "Unit kerja {$nama} berhasil dihapus dari sistem.");
    }
}
