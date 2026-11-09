<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\UnitKerja;
use App\Services\HaversineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AsnController extends Controller
{
    public function __construct(
        protected HaversineService $haversine
    ) {}

    public function index(Request $request): View
    {
        $query = AsnMaster::with('unitSaatIni');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('zona')) {
            $query->where('zona_jarak', $request->zona);
        }

        if ($request->filled('unit_id')) {
            $query->where('id_unit_saat_ini', $request->unit_id);
        }

        $asns = $query->orderBy('jarak_ke_kantor_km', 'desc')->paginate(15)->withQueryString();
        $units = UnitKerja::orderBy('nama_unit')->get();

        return view('asn.index', compact('asns', 'units'));
    }

    public function show(int $id): View
    {
        $asn = AsnMaster::with('unitSaatIni', 'rekomendasi.unitTujuan')->findOrFail($id);
        return view('asn.show', compact('asn'));
    }

    public function editLokasi(int $id): View
    {
        $asn = AsnMaster::with('unitSaatIni')->findOrFail($id);
        return view('asn.edit-lokasi', compact('asn'));
    }

    public function updateLokasi(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'domisili_lat' => 'required|numeric|between:-90,90',
            'domisili_lng' => 'required|numeric|between:-180,180',
            'provinsi' => 'nullable|string|max:100',
            'kabupaten_kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kelurahan_desa' => 'nullable|string|max:100',
            'kode_desa' => 'nullable|string|max:20',
            'alamat_detail' => 'nullable|string',
            'status_permohonan_mutasi' => 'nullable|boolean',
            'alasan_mutasi' => 'nullable|string',
        ]);

        $asn = AsnMaster::with('unitSaatIni')->findOrFail($id);

        // Hitung jarak baru ke unit kerja saat ini
        $jarak = $this->haversine->calculateDistance(
            $validated['domisili_lat'],
            $validated['domisili_lng'],
            $asn->unitSaatIni->kantor_lat,
            $asn->unitSaatIni->kantor_lng
        );

        $zona = $this->haversine->classifyZone($jarak);

        $asn->update(array_merge($validated, [
            'jarak_ke_kantor_km' => $jarak,
            'zona_jarak' => $zona,
            'status_permohonan_mutasi' => $request->has('status_permohonan_mutasi'),
        ]));

        return redirect()->route('asn.show', $asn->id)
            ->with('success', 'Titik domisili dan kalkulasi jarak spasial berhasil diperbarui.');
    }
}
