<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\MasterJabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        // Pencarian Nama Jabatan / Jenjang
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_jabatan', 'like', "%{$search}%")
                  ->orWhere('jenjang_jabatan', 'like', "%{$search}%");
            });
        }

        $jabatanList = $query->withCount(['asns', 'formasis'])
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
            'nama_jabatan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_jabatan', 'nama_jabatan')
                    ->where(fn ($q) => $q->where('jenjang_jabatan', $request->input('jenis_jabatan') === 'fungsional' ? $request->input('jenjang_jabatan') : null)),
            ],
            'jenis_jabatan' => 'required|in:pelaksana,fungsional',
            'jenjang_jabatan' => 'nullable|string|max:100',
            'kelas_jabatan' => 'required|integer|min:1|max:12',
            'tingkat_pendidikan' => 'nullable|string|max:60',
            'nama_pendidikan' => 'nullable|string|max:180',
            'kualifikasi_pendidikan' => 'nullable|string',
        ], [
            'nama_jabatan.unique' => 'Kombinasi Nama Jabatan dan Jenjang Jabatan tersebut sudah terdaftar.',
        ]);

        // Selain jabatan fungsional tidak memiliki jenjang jabatan
        if ($validated['jenis_jabatan'] !== 'fungsional') {
            $validated['jenjang_jabatan'] = null;
        }

        // Gabungkan dropdown tingkat_pendidikan + nama_pendidikan ke kualifikasi_pendidikan
        if (!empty($validated['tingkat_pendidikan']) || !empty($validated['nama_pendidikan'])) {
            $parts = array_filter([
                trim((string)($validated['tingkat_pendidikan'] ?? '')),
                trim((string)($validated['nama_pendidikan'] ?? '')),
            ]);
            $validated['kualifikasi_pendidikan'] = implode(' - ', $parts);
        }
        unset($validated['tingkat_pendidikan'], $validated['nama_pendidikan']);

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
            'nama_jabatan' => [
                'required',
                'string',
                'max:255',
                Rule::unique('master_jabatan', 'nama_jabatan')
                    ->where(fn ($q) => $q->where('jenjang_jabatan', $request->input('jenis_jabatan') === 'fungsional' ? $request->input('jenjang_jabatan') : null))
                    ->ignore($id),
            ],
            'jenis_jabatan' => 'required|in:pelaksana,fungsional',
            'jenjang_jabatan' => 'nullable|string|max:100',
            'kelas_jabatan' => 'required|integer|min:1|max:12',
            'tingkat_pendidikan' => 'nullable|string|max:60',
            'nama_pendidikan' => 'nullable|string|max:180',
            'kualifikasi_pendidikan' => 'nullable|string',
        ], [
            'nama_jabatan.unique' => 'Kombinasi Nama Jabatan dan Jenjang Jabatan tersebut sudah terdaftar.',
        ]);

        // Selain jabatan fungsional tidak memiliki jenjang jabatan
        if ($validated['jenis_jabatan'] !== 'fungsional') {
            $validated['jenjang_jabatan'] = null;
        }

        // Gabungkan dropdown tingkat_pendidikan + nama_pendidikan ke kualifikasi_pendidikan
        if ($request->has('tingkat_pendidikan') || $request->has('nama_pendidikan')) {
            $parts = array_filter([
                trim((string)($validated['tingkat_pendidikan'] ?? '')),
                trim((string)($validated['nama_pendidikan'] ?? '')),
            ]);
            $validated['kualifikasi_pendidikan'] = !empty($parts) ? implode(' - ', $parts) : null;
        }
        unset($validated['tingkat_pendidikan'], $validated['nama_pendidikan']);

        $oldName = $jabatan->nama_jabatan;
        $jabatan->update($validated);

        // Jika nama jabatan diperbarui, sinkronkan nama di asn_master & formasi_jabatan yang berelasi
        if ($oldName !== $validated['nama_jabatan']) {
            AsnMaster::where('id_master_jabatan', $id)
                ->update(['jabatan' => $validated['nama_jabatan'], 'jenis_jabatan' => $validated['jenis_jabatan']]);
            FormasiJabatan::where('id_master_jabatan', $id)
                ->update(['nama_jabatan' => $validated['nama_jabatan'], 'jenis_jabatan' => $validated['jenis_jabatan']]);
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
