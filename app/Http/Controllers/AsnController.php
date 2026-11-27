<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\MasterJabatan;
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
        $query = AsnMaster::with(['unitSaatIni', 'masterJabatan', 'formasiJabatan']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%")
                  ->orWhere('jabatan', 'like', "%{$search}%")
                  ->orWhere('kecamatan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('jenis_jabatan')) {
            $query->where('jenis_jabatan', $request->jenis_jabatan);
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
        $jabatanList = MasterJabatan::orderBy('nama_jabatan')->get();
        $formasiList = FormasiJabatan::with('masterJabatan')
            ->orderBy('nama_jabatan')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'id_unit_kerja' => $f->id_unit_kerja,
                'nama_jabatan' => $f->nama_jabatan,
                'jenis_jabatan' => $f->jenis_jabatan,
                'jenjang_jabatan' => $f->jenjang_jabatan,
                'kebutuhan_abk' => (int)$f->kebutuhan_abk,
                'bezetting_riil' => (int)$f->bezetting_riil,
                'kuota_lowong' => (int)$f->kuota_lowong,
                'status' => $f->status,
            ])->values();

        return view('asn.create', compact('units', 'kecamatans', 'jabatanList', 'formasiList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nip' => 'required|numeric|digits:18|unique:asn_master,nip',
            'nama' => 'required|string|max:150',
            'gelar_depan' => 'nullable|string|max:20',
            'gelar_belakang' => 'nullable|string|max:20',
            'id_unit_saat_ini' => 'required|exists:unit_kerja,id',
            'id_formasi_jabatan' => 'required|exists:formasi_jabatan,id',
            'pangkat_golongan' => 'required|string|max:20',
            'tmt_golongan' => 'nullable|date',
            'tgl_jabatan' => 'nullable|date',
            'tgl_cpns' => 'nullable|date',
            'tgl_pns' => 'nullable|date',
            'tingkat_pendidikan' => 'nullable|string|max:50',
            'pendidikan_terakhir' => 'nullable|string|max:150',
            'masa_kerja_tahun' => 'nullable|integer|min:0',
            'unit_kerja_induk' => 'nullable|string|max:255',
            'domisili_lat' => 'required|numeric|between:-90,90',
            'domisili_lng' => 'required|numeric|between:-180,180',
            'provinsi' => 'nullable|string|max:100',
            'kabupaten_kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kelurahan_desa' => 'nullable|string|max:100',
            'alamat_detail' => 'nullable|string',
        ]);

        // Validasi formasi jabatan wajib milik unit kerja terpilih & masih tersedia kuota lowong
        $formasiUnit = FormasiJabatan::with('masterJabatan')
            ->where('id', $validated['id_formasi_jabatan'])
            ->where('id_unit_kerja', $validated['id_unit_saat_ini'])
            ->first();

        if (!$formasiUnit) {
            return back()->withInput()->withErrors([
                'id_formasi_jabatan' => 'Formasi jabatan tidak ditemukan pada unit kerja yang dipilih.',
            ]);
        }

        if ((int)$formasiUnit->kuota_lowong <= 0) {
            return back()->withInput()->withErrors([
                'id_formasi_jabatan' => "Formasi jabatan {$formasiUnit->nama_jabatan} pada unit kerja ini sudah penuh (kuota lowong 0).",
            ]);
        }

        // Ambil nama jabatan, jenis jabatan, dan id_master_jabatan otomatis dari Formasi/Master Jabatan
        $validated['id_master_jabatan'] = $formasiUnit->id_master_jabatan;
        $validated['jabatan'] = $formasiUnit->masterJabatan->nama_jabatan ?? $formasiUnit->nama_jabatan;
        $validated['jenis_jabatan'] = $formasiUnit->masterJabatan->jenis_jabatan ?? $formasiUnit->jenis_jabatan ?? 'pelaksana';

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

        DB::transaction(function () use ($validated, $jarak, $zona, $formasiUnit) {
            $asn = AsnMaster::create(array_merge($validated, [
                'jarak_ke_kantor_km' => $jarak,
                'zona_jarak' => $zona,
                'status_permohonan_mutasi' => false,
            ]));

            // Hitung ulang bezetting & kuota lowong formasi secara otomatis
            $formasiUnit->recalculateBezetting();

            // Otomatis buat akun User Login mandiri (role: asn, default password: NIP)
            $email = "{$asn->nip}@bkpsdm.kabxyz.go.id";
            if (!User::where('nip', $asn->nip)->exists()) {
                User::create([
                    'name' => $asn->nama,
                    'nip' => $asn->nip,
                    'email' => $email,
                    'password' => Hash::make($asn->nip),
                    'role' => 'asn',
                    'asn_id' => $asn->id,
                ]);
            }
        });

        return redirect()->route('asn.index')
            ->with('success', "Pegawai ASN {$validated['nama']} berhasil didaftarkan dan bezetting formasi telah diperbarui.");
    }

    public function show(int $id): View
    {
        $asn = AsnMaster::with(['unitSaatIni', 'masterJabatan', 'formasiJabatan.unitKerja', 'rekomendasi.unitTujuan'])->findOrFail($id);
        return view('asn.show', compact('asn'));
    }

    public function edit(int $id): View
    {
        $asn = AsnMaster::with(['unitSaatIni', 'masterJabatan', 'formasiJabatan'])->findOrFail($id);
        $units = UnitKerja::orderBy('nama_unit')->get();
        $kecamatans = MasterLokasi::orderBy('nama_kecamatan')->get();
        $jabatanList = MasterJabatan::orderBy('nama_jabatan')->get();
        $formasiList = FormasiJabatan::with('masterJabatan')
            ->orderBy('nama_jabatan')
            ->get()
            ->map(fn ($f) => [
                'id' => $f->id,
                'id_unit_kerja' => $f->id_unit_kerja,
                'nama_jabatan' => $f->nama_jabatan,
                'jenis_jabatan' => $f->jenis_jabatan,
                'jenjang_jabatan' => $f->jenjang_jabatan,
                'kebutuhan_abk' => (int)$f->kebutuhan_abk,
                'bezetting_riil' => (int)$f->bezetting_riil,
                'kuota_lowong' => (int)$f->kuota_lowong,
                'status' => $f->status,
            ])->values();

        return view('asn.edit', compact('asn', 'units', 'kecamatans', 'jabatanList', 'formasiList'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $asn = AsnMaster::findOrFail($id);

        $validated = $request->validate([
            'nip' => 'required|numeric|digits:18|unique:asn_master,nip,' . $asn->id,
            'nama' => 'required|string|max:150',
            'gelar_depan' => 'nullable|string|max:20',
            'gelar_belakang' => 'nullable|string|max:20',
            'id_unit_saat_ini' => 'required|exists:unit_kerja,id',
            'id_formasi_jabatan' => 'required|exists:formasi_jabatan,id',
            'pangkat_golongan' => 'required|string|max:20',
            'tmt_golongan' => 'nullable|date',
            'tgl_jabatan' => 'nullable|date',
            'tgl_cpns' => 'nullable|date',
            'tgl_pns' => 'nullable|date',
            'tingkat_pendidikan' => 'nullable|string|max:50',
            'pendidikan_terakhir' => 'nullable|string|max:150',
            'masa_kerja_tahun' => 'nullable|integer|min:0',
            'unit_kerja_induk' => 'nullable|string|max:255',
            'domisili_lat' => 'required|numeric|between:-90,90',
            'domisili_lng' => 'required|numeric|between:-180,180',
            'provinsi' => 'nullable|string|max:100',
            'kabupaten_kota' => 'nullable|string|max:100',
            'kecamatan' => 'nullable|string|max:100',
            'kelurahan_desa' => 'nullable|string|max:100',
            'alamat_detail' => 'nullable|string',
        ]);

        // Validasi formasi jabatan milik unit kerja terpilih
        $formasiUnit = FormasiJabatan::with('masterJabatan')
            ->where('id', $validated['id_formasi_jabatan'])
            ->where('id_unit_kerja', $validated['id_unit_saat_ini'])
            ->first();

        if (!$formasiUnit) {
            return back()->withInput()->withErrors([
                'id_formasi_jabatan' => 'Formasi jabatan tidak ditemukan pada unit kerja yang dipilih.',
            ]);
        }

        // Jika berpindah ke formasi baru, pastikan formasi tujuan masih ada kuota lowong
        $isChangingFormasi = ((int)$asn->id_formasi_jabatan !== (int)$formasiUnit->id);
        if ($isChangingFormasi && (int)$formasiUnit->kuota_lowong <= 0) {
            return back()->withInput()->withErrors([
                'id_formasi_jabatan' => "Formasi jabatan {$formasiUnit->nama_jabatan} pada unit kerja ini sudah penuh (kuota lowong 0).",
            ]);
        }

        $oldFormasiId = $asn->id_formasi_jabatan;

        $validated['id_master_jabatan'] = $formasiUnit->id_master_jabatan;
        $validated['jabatan'] = $formasiUnit->masterJabatan->nama_jabatan ?? $formasiUnit->nama_jabatan;
        $validated['jenis_jabatan'] = $formasiUnit->masterJabatan->jenis_jabatan ?? $formasiUnit->jenis_jabatan ?? 'pelaksana';

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

        DB::transaction(function () use ($asn, $validated, $jarak, $zona, $formasiUnit, $oldFormasiId) {
            $asn->update(array_merge($validated, [
                'jarak_ke_kantor_km' => $jarak,
                'zona_jarak' => $zona,
            ]));

            // Hitung ulang bezetting formasi baru & formasi lama (jika pindah)
            $formasiUnit->recalculateBezetting();
            if ($oldFormasiId && (int)$oldFormasiId !== (int)$formasiUnit->id) {
                FormasiJabatan::find($oldFormasiId)?->recalculateBezetting();
            }

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
        $oldFormasiId = $asn->id_formasi_jabatan;

        DB::transaction(function () use ($asn, $oldFormasiId) {
            // Hapus akun user terkait
            User::where('asn_id', $asn->id)->orWhere('nip', $asn->nip)->delete();
            $asn->delete();

            // Kembalikan kursi formasi secara otomatis
            if ($oldFormasiId) {
                FormasiJabatan::find($oldFormasiId)?->recalculateBezetting();
            }
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
        ]));

        if ($user->isAsn()) {
            return redirect()->route('asn.edit-lokasi', $asn->id)
                ->with('success', 'Titik domisili dan kalkulasi jarak spasial berhasil diperbarui.');
        }

        return redirect()->route('asn.show', $asn->id)
            ->with('success', 'Titik domisili dan kalkulasi jarak spasial berhasil diperbarui.');
    }
}
