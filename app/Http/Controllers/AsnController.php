<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\MasterLokasi;
use App\Models\UnitKerja;
use App\Models\User;
use App\Services\HaversineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

        $asns = $query->orderBy('jarak_ke_kantor_km', 'desc')->get();
        $units = UnitKerja::orderBy('nama_unit')->get();

        return view('asn.index', compact('asns', 'units'));
    }

    public function create(): View
    {
        $units = UnitKerja::orderBy('nama_unit')->get();
        $kecamatans = MasterLokasi::orderBy('nama_kecamatan')->get();
        $jabatanList = FormasiJabatan::select('nama_jabatan', 'jenis_jabatan')
            ->distinct()
            ->orderBy('nama_jabatan')
            ->get();
        return view('asn.create', compact('units', 'kecamatans', 'jabatanList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => 'required|numeric|digits:18|unique:asn_master,nip',
            'nama' => 'required|string|max:150',
            'gelar_depan' => 'nullable|string|max:20',
            'gelar_belakang' => 'nullable|string|max:20',
            'jabatan' => 'required|string|max:150',
            'jenis_jabatan' => 'required|in:struktural,fungsional,pelaksana',
            'pangkat_golongan' => 'required|string|max:20',
            'tmt_golongan' => 'nullable|date',
            'tgl_jabatan' => 'nullable|date',
            'tgl_cpns' => 'nullable|date',
            'tgl_pns' => 'nullable|date',
            'tingkat_pendidikan' => 'nullable|string|max:50',
            'pendidikan_terakhir' => 'nullable|string|max:150',
            'masa_kerja_tahun' => 'nullable|integer|min:0',
            'id_unit_saat_ini' => 'required|exists:unit_kerja,id',
            'unit_kerja_induk' => 'nullable|string|max:255',
            'domisili_lat' => 'required|numeric|between:-90,90',
            'domisili_lng' => 'required|numeric|between:-180,180',
            'provinsi' => 'nullable|string|max:100',
            'kabupaten_kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kelurahan_desa' => 'nullable|string|max:100',
            'alamat_detail' => 'nullable|string',
        ]);

        // Hitung masa kerja otomatis jika ada tgl_cpns
        if (!empty($validated['tgl_cpns'])) {
            $thnCpns = (int)date('Y', strtotime($validated['tgl_cpns']));
            $validated['masa_kerja_tahun'] = max(0, 2026 - $thnCpns);
        } else {
            $validated['masa_kerja_tahun'] = $validated['masa_kerja_tahun'] ?? 5;
        }

        $validated['tmt_kerja_unit'] = $validated['tgl_jabatan'] ?? $validated['tgl_cpns'] ?? now();

        $unit = UnitKerja::findOrFail($validated['id_unit_saat_ini']);

        // Hitung jarak awal ke unit kerja terpilih
        $jarak = $this->haversine->calculateDistance(
            $validated['domisili_lat'],
            $validated['domisili_lng'],
            $unit->kantor_lat,
            $unit->kantor_lng
        );
        $zona = $this->haversine->classifyZone($jarak);

        DB::transaction(function () use ($validated, $jarak, $zona) {
            $asn = AsnMaster::create(array_merge($validated, [
                'jarak_ke_kantor_km' => $jarak,
                'zona_jarak' => $zona,
                'status_permohonan_mutasi' => false,
            ]));

            // Otomatis buat akun User Login mandiri (role: asn)
            $email = "{$asn->nip}@bkpsdm.indramayukab.go.id";
            if (!User::where('nip', $asn->nip)->exists()) {
                User::create([
                    'name' => $asn->nama,
                    'nip' => $asn->nip,
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'role' => 'asn',
                    'asn_id' => $asn->id,
                ]);
            }
        });

        return redirect()->route('asn.index')
            ->with('success', "Pegawai ASN {$validated['nama']} berhasil didaftarkan beserta akun login mandiri.");
    }

    public function show(int $id): View
    {
        $asn = AsnMaster::with('unitSaatIni', 'rekomendasi.unitTujuan')->findOrFail($id);
        return view('asn.show', compact('asn'));
    }

    public function edit(int $id): View
    {
        $asn = AsnMaster::with('unitSaatIni')->findOrFail($id);
        $units = UnitKerja::orderBy('nama_unit')->get();
        $kecamatans = MasterLokasi::orderBy('nama_kecamatan')->get();
        $jabatanList = FormasiJabatan::select('nama_jabatan', 'jenis_jabatan')
            ->distinct()
            ->orderBy('nama_jabatan')
            ->get();
        return view('asn.edit', compact('asn', 'units', 'kecamatans', 'jabatanList'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $asn = AsnMaster::findOrFail($id);

        $validated = $request->validate([
            'nip' => 'required|numeric|digits:18|unique:asn_master,nip,' . $asn->id,
            'nama' => 'required|string|max:150',
            'gelar_depan' => 'nullable|string|max:20',
            'gelar_belakang' => 'nullable|string|max:20',
            'jabatan' => 'required|string|max:150',
            'jenis_jabatan' => 'required|in:struktural,fungsional,pelaksana',
            'pangkat_golongan' => 'required|string|max:20',
            'tmt_golongan' => 'nullable|date',
            'tgl_jabatan' => 'nullable|date',
            'tgl_cpns' => 'nullable|date',
            'tgl_pns' => 'nullable|date',
            'tingkat_pendidikan' => 'nullable|string|max:50',
            'pendidikan_terakhir' => 'nullable|string|max:150',
            'masa_kerja_tahun' => 'nullable|integer|min:0',
            'id_unit_saat_ini' => 'required|exists:unit_kerja,id',
            'unit_kerja_induk' => 'nullable|string|max:255',
            'domisili_lat' => 'required|numeric|between:-90,90',
            'domisili_lng' => 'required|numeric|between:-180,180',
            'provinsi' => 'nullable|string|max:100',
            'kabupaten_kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kelurahan_desa' => 'nullable|string|max:100',
            'alamat_detail' => 'nullable|string',
        ]);

        if (!empty($validated['tgl_cpns'])) {
            $thnCpns = (int)date('Y', strtotime($validated['tgl_cpns']));
            $validated['masa_kerja_tahun'] = max(0, 2026 - $thnCpns);
        }

        $unit = UnitKerja::findOrFail($validated['id_unit_saat_ini']);

        // Hitung ulang jarak ke kantor
        $jarak = $this->haversine->calculateDistance(
            $validated['domisili_lat'],
            $validated['domisili_lng'],
            $unit->kantor_lat,
            $unit->kantor_lng
        );
        $zona = $this->haversine->classifyZone($jarak);

        DB::transaction(function () use ($asn, $validated, $jarak, $zona) {
            $asn->update(array_merge($validated, [
                'jarak_ke_kantor_km' => $jarak,
                'zona_jarak' => $zona,
            ]));

            // Sinkronkan data ke tabel users jika ada
            if ($asn->user) {
                $asn->user->update([
                    'name' => $asn->nama,
                    'nip' => $asn->nip,
                ]);
            }
        });

        return redirect()->route('asn.show', $asn->id)
            ->with('success', "Data profil dan koordinat pegawai ASN {$asn->nama} berhasil diperbarui.");
    }

    public function destroy(int $id): RedirectResponse
    {
        $asn = AsnMaster::findOrFail($id);
        $nama = $asn->nama;

        DB::transaction(function () use ($asn) {
            // Hapus akun user terkait
            User::where('asn_id', $asn->id)->orWhere('nip', $asn->nip)->delete();
            $asn->delete();
        });

        return redirect()->route('asn.index')
            ->with('success', "Data pegawai ASN {$nama} dan akun loginnya berhasil dihapus.");
    }

    public function editLokasi(int $id): View
    {
        $asn = AsnMaster::with('unitSaatIni')->findOrFail($id);
        $user = auth()->user();

        // Guard RBAC: Pegawai ASN hanya boleh memperbarui titik domisili miliknya sendiri
        if (!$user->isAdmin() && $user->nip !== $asn->nip && $user->asn_id !== $asn->id) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin memperbarui koordinat domisili profil Anda sendiri.');
        }

        $kecamatans = MasterLokasi::orderBy('nama_kecamatan')->get();
        return view('asn.edit-lokasi', compact('asn', 'kecamatans'));
    }

    public function updateLokasi(Request $request, int $id): RedirectResponse
    {
        $asn = AsnMaster::with('unitSaatIni')->findOrFail($id);
        $user = auth()->user();

        // Guard RBAC: Pegawai ASN hanya boleh memperbarui titik domisili miliknya sendiri
        if (!$user->isAdmin() && $user->nip !== $asn->nip && $user->asn_id !== $asn->id) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin memperbarui koordinat domisili profil Anda sendiri.');
        }

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
