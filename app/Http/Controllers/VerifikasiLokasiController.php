<?php

namespace App\Http\Controllers;

use App\Models\PengajuanUbahLokasi;
use App\Services\HaversineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class VerifikasiLokasiController
 *
 * Mengelola alur approval / verifikasi perubahan pin titik lokasi mandiri
 * yang diajukan PNS beserta dokumen bukti KTP dan riwayat pergantian pin.
 *
 * @package App\Http\Controllers
 */
class VerifikasiLokasiController extends Controller
{
    public function __construct(
        protected HaversineService $haversine
    ) {}

    /**
     * Menampilkan daftar pengajuan & riwayat pergantian pin titik lokasi untuk Admin BKPSDM.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->get('status', 'all');
        if (!in_array($statusFilter, ['all', 'pending', 'disetujui', 'ditolak'], true)) {
            $statusFilter = 'all';
        }

        $query = PengajuanUbahLokasi::with(['asn.unitSaatIni', 'pengaju', 'verifikator'])
            ->latest();

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        $pengajuanList = $query->get();

        $stats = [
            'pending' => PengajuanUbahLokasi::where('status', 'pending')->count(),
            'disetujui' => PengajuanUbahLokasi::where('status', 'disetujui')->count(),
            'ditolak' => PengajuanUbahLokasi::where('status', 'ditolak')->count(),
            'total' => PengajuanUbahLokasi::count(),
        ];

        return view('verifikasi-lokasi.index', compact('pengajuanList', 'stats', 'statusFilter'));
    }

    /**
     * Menyetujui (memverifikasi) pengajuan perubahan pin titik lokasi dan memperbarui asn_master.
     */
    public function approve(Request $request, int $id): RedirectResponse
    {
        $pengajuan = PengajuanUbahLokasi::with('asn.unitSaatIni')->findOrFail($id);

        if ($pengajuan->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $catatan = trim((string)$request->input('catatan_admin', '')) ?: 'Lokasi domisili telah diverifikasi sesuai KTP oleh Admin BKPSDM.';
        $asn = $pengajuan->asn;

        if (!$asn) {
            return back()->with('error', 'Data pegawai ASN terkait tidak ditemukan.');
        }

        // Hitung ulang jarak & zona secara akurat terhadap kantor saat ini
        $jarak = $pengajuan->jarak_baru_km;
        if ($asn->unitSaatIni && $asn->unitSaatIni->kantor_lat && $asn->unitSaatIni->kantor_lng) {
            $jarak = $this->haversine->calculateDistance(
                (float)$pengajuan->lat_baru,
                (float)$pengajuan->lng_baru,
                (float)$asn->unitSaatIni->kantor_lat,
                (float)$asn->unitSaatIni->kantor_lng
            );
        }
        $zona = $this->haversine->classifyZone((float)$jarak);

        DB::transaction(function () use ($pengajuan, $asn, $jarak, $zona, $catatan) {
            $asn->update([
                'domisili_lat' => $pengajuan->lat_baru,
                'domisili_lng' => $pengajuan->lng_baru,
                'provinsi' => $pengajuan->provinsi_baru ?: $asn->provinsi,
                'kabupaten_kota' => $pengajuan->kabupaten_kota_baru ?: $asn->kabupaten_kota,
                'kecamatan' => $pengajuan->kecamatan_baru ?: $asn->kecamatan,
                'kelurahan_desa' => $pengajuan->kelurahan_desa_baru ?: $asn->kelurahan_desa,
                'alamat_detail' => $pengajuan->alamat_detail_baru ?? $asn->alamat_detail,
                'jarak_ke_kantor_km' => $jarak,
                'zona_jarak' => $zona,
            ]);

            $pengajuan->update([
                'jarak_baru_km' => $jarak,
                'zona_baru' => $zona,
                'status' => 'disetujui',
                'catatan_admin' => $catatan,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
            ]);
        });

        return back()->with('success', "Pin titik lokasi untuk {$asn->nama} berhasil DIVERIFIKASI & diperbarui!");
    }

    /**
     * Menolak pengajuan perubahan pin titik lokasi dengan catatan alasan penolakan.
     */
    public function reject(Request $request, int $id): RedirectResponse
    {
        $pengajuan = PengajuanUbahLokasi::with('asn')->findOrFail($id);

        if ($pengajuan->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $validated = $request->validate([
            'catatan_admin' => 'nullable|string|max:500',
        ]);

        $catatan = trim((string)($validated['catatan_admin'] ?? '')) ?: 'Titik lokasi tidak sesuai dengan alamat pada KTP yang diunggah.';

        $pengajuan->update([
            'status' => 'ditolak',
            'catatan_admin' => $catatan,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $nama = $pengajuan->asn->nama ?? 'Pegawai';
        return back()->with('success', "Pengajuan perubahan lokasi untuk {$nama} telah DITOLAK.");
    }

    /**
     * Menyajikan file KTP secara aman (RBAC: hanya Admin atau PNS pemilik pengajuan).
     */
    public function showKtp(int $id): Response
    {
        $pengajuan = PengajuanUbahLokasi::with('asn')->findOrFail($id);
        $user = auth()->user();

        $isOwner = $pengajuan->asn && ($user->asn_id === $pengajuan->asn->id || $user->nip === $pengajuan->asn->nip);
        if (!$user->isAdmin() && !$isOwner) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin melihat dokumen KTP ini.');
        }

        if (!$pengajuan->ktp_path || !Storage::disk('local')->exists($pengajuan->ktp_path)) {
            abort(404, 'Dokumen KTP tidak ditemukan.');
        }

        $fullPath = Storage::disk('local')->path($pengajuan->ktp_path);
        return response()->file($fullPath);
    }
}
