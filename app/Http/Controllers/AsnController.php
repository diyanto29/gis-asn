<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\MasterJabatan;
use App\Models\MasterLokasi;
use App\Models\PengajuanUbahLokasi;
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
        $riwayatLokasi = PengajuanUbahLokasi::with('verifikator')
            ->where('id_asn', $asn->id)
            ->latest()
            ->get();
        $pendingPengajuan = $riwayatLokasi->firstWhere('status', 'pending');

        return view('asn.edit-lokasi', compact('asn', 'kecamatans', 'riwayatLokasi', 'pendingPengajuan'));
    }

    public function updateLokasi(Request $request, int $id): RedirectResponse
    {
        $asn = AsnMaster::with('unitSaatIni')->findOrFail($id);
        $user = auth()->user();

        // Guard RBAC: Pegawai ASN hanya boleh memperbarui titik domisili miliknya sendiri
        if (!$user->isAdmin() && $user->nip !== $asn->nip && $user->asn_id !== $asn->id) {
            abort(403, 'Akses Ditolak: Anda hanya memiliki izin memperbarui koordinat domisili profil Anda sendiri.');
        }

        $rules = [
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
            'ktp_file' => $user->isAsn()
                ? 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'
                : 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];

        $validated = $request->validate($rules, [
            'ktp_file.required' => 'Upload foto/scan KTP wajib dilampirkan untuk verifikasi perubahan titik lokasi.',
            'ktp_file.mimes' => 'Format file KTP harus JPG, PNG, atau PDF.',
            'ktp_file.max' => 'Ukuran file KTP maksimal 5 MB.',
        ]);

        // Hitung jarak baru ke unit kerja saat ini
        $jarak = $this->haversine->calculateDistance(
            (float)$validated['domisili_lat'],
            (float)$validated['domisili_lng'],
            (float)$asn->unitSaatIni->kantor_lat,
            (float)$asn->unitSaatIni->kantor_lng
        );

        $zona = $this->haversine->classifyZone($jarak);

        // Simpan file KTP ke storage lokal privat (hanya bisa diakses Admin & pemilik via route terproteksi)
        $ktpPath = null;
        if ($request->hasFile('ktp_file')) {
            $ktpPath = $request->file('ktp_file')->store('ktp_lokasi', 'local');
        }

        // Snapshot alamat lama sebelum perubahan
        $alamatLamaParts = array_filter([
            $asn->alamat_detail,
            $asn->kelurahan_desa,
            $asn->kecamatan ? 'Kec. ' . $asn->kecamatan : null,
            $asn->kabupaten_kota,
            $asn->provinsi,
        ]);
        $alamatLama = implode(', ', $alamatLamaParts) ?: '-';

        // FLOW PNS: Masuk antrean approval Admin BKPSDM (koordinat asn_master belum berubah sampai disetujui)
        if ($user->isAsn()) {
            PengajuanUbahLokasi::create([
                'id_asn' => $asn->id,
                'user_id' => $user->id,
                'lat_lama' => $asn->domisili_lat,
                'lng_lama' => $asn->domisili_lng,
                'alamat_lama' => $alamatLama,
                'jarak_lama_km' => $asn->jarak_ke_kantor_km,
                'lat_baru' => (float)$validated['domisili_lat'],
                'lng_baru' => (float)$validated['domisili_lng'],
                'provinsi_baru' => $validated['provinsi'] ?? $asn->provinsi,
                'kabupaten_kota_baru' => $validated['kabupaten_kota'] ?? $asn->kabupaten_kota,
                'kecamatan_baru' => $validated['kecamatan'] ?? $asn->kecamatan,
                'kelurahan_desa_baru' => $validated['kelurahan_desa'] ?? $asn->kelurahan_desa,
                'alamat_detail_baru' => $validated['alamat_detail'] ?? $asn->alamat_detail,
                'jarak_baru_km' => $jarak,
                'zona_baru' => $zona,
                'ktp_path' => $ktpPath,
                'status' => 'pending',
            ]);

            return redirect()->route('asn.edit-lokasi', $asn->id)
                ->with('success', 'Pengajuan perubahan pin titik lokasi beserta bukti KTP berhasil dikirim! Menunggu verifikasi Admin BKPSDM.');
        }

        // FLOW ADMIN: Langsung perbarui koordinat & catat otomatis di riwayat sebagai disetujui
        DB::transaction(function () use ($asn, $user, $validated, $jarak, $zona, $alamatLama, $ktpPath) {
            PengajuanUbahLokasi::create([
                'id_asn' => $asn->id,
                'user_id' => $user->id,
                'lat_lama' => $asn->domisili_lat,
                'lng_lama' => $asn->domisili_lng,
                'alamat_lama' => $alamatLama,
                'jarak_lama_km' => $asn->jarak_ke_kantor_km,
                'lat_baru' => (float)$validated['domisili_lat'],
                'lng_baru' => (float)$validated['domisili_lng'],
                'provinsi_baru' => $validated['provinsi'] ?? $asn->provinsi,
                'kabupaten_kota_baru' => $validated['kabupaten_kota'] ?? $asn->kabupaten_kota,
                'kecamatan_baru' => $validated['kecamatan'] ?? $asn->kecamatan,
                'kelurahan_desa_baru' => $validated['kelurahan_desa'] ?? $asn->kelurahan_desa,
                'alamat_detail_baru' => $validated['alamat_detail'] ?? $asn->alamat_detail,
                'jarak_baru_km' => $jarak,
                'zona_baru' => $zona,
                'ktp_path' => $ktpPath,
                'status' => 'disetujui',
                'catatan_admin' => 'Diperbarui langsung oleh Admin BKPSDM.',
                'verified_by' => $user->id,
                'verified_at' => now(),
            ]);

            $asn->update([
                'domisili_lat' => (float)$validated['domisili_lat'],
                'domisili_lng' => (float)$validated['domisili_lng'],
                'provinsi' => $validated['provinsi'] ?? $asn->provinsi,
                'kabupaten_kota' => $validated['kabupaten_kota'] ?? $asn->kabupaten_kota,
                'kecamatan' => $validated['kecamatan'] ?? $asn->kecamatan,
                'kelurahan_desa' => $validated['kelurahan_desa'] ?? $asn->kelurahan_desa,
                'alamat_detail' => $validated['alamat_detail'] ?? $asn->alamat_detail,
                'jarak_ke_kantor_km' => $jarak,
                'zona_jarak' => $zona,
            ]);
        });

        return redirect()->route('asn.show', $asn->id)
            ->with('success', 'Titik domisili dan kalkulasi jarak spasial berhasil diperbarui dan dicatat di riwayat.');
    }
}
