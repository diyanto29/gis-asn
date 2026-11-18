<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\RekomendasiMutasi;
use App\Models\UnitKerja;
use App\Services\HaversineService;
use App\Services\TopsisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class SimulasiMandiriController
 *
 * Mengelola SPK TOPSIS Tahap 2 (Bottom-Up): Rekomendasi Lokasi Penempatan Optimal
 * bagi Pegawai ASN ke Unit-Unit Kerja yang Membuka Formasi Lowong (ABK > 0).
 *
 * @package App\Http\Controllers
 */
class SimulasiMandiriController extends Controller
{
    public function __construct(
        protected HaversineService $haversine,
        protected TopsisService $topsis
    ) {}

    /**
     * Menampilkan dashboard simulasi rekomendasi mandiri ASN (Tahap 2).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        // Tentukan ASN target: Jika role ASN gunakan profilnya, jika Admin bisa memilih via dropdown
        if ($user->isAsn() && $user->asn) {
            $asn = $user->asn->load('unitSaatIni');
        } else {
            $targetAsnId = $request->query('asn_id');
            $asn = $targetAsnId 
                ? AsnMaster::with('unitSaatIni')->findOrFail($targetAsnId)
                : AsnMaster::with('unitSaatIni')->whereNotNull('domisili_lat')->first();
        }

        if (!$asn) {
            return redirect()->route('dashboard')
                ->with('error', 'Profil data ASN Anda belum tertaut. Silakan hubungi Administrator BKPSDM.');
        }

        // Ambil daftar ASN untuk dropdown pemilih (jika Admin yang mengakses)
        $asnList = $user->isAdmin() 
            ? AsnMaster::orderBy('nama')->get(['id', 'nip', 'nama']) 
            : collect([$asn]);

        // =========================================================================
        // TAHAP 2 TOPSIS: KANDIDAT ALTERNATIF ADALAH UNIT KERJA BERFORMASI (ABK > 0)
        // Unit kerja saat ini dari ASN dieksklusi untuk mencegah rekomendasi sirkular.
        // =========================================================================
        $unitAlternatif = UnitKerja::with(['formasiJabatan' => function($q) {
                $q->where('status', 'terbuka')->orderBy('nama_jabatan');
            }])
            ->where('kuota_formasi_kosong', '>', 0)
            ->where('id', '!=', $asn->id_unit_saat_ini)
            ->get();

        if ($unitAlternatif->isEmpty()) {
            return view('asn.simulasi-mandiri', [
                'asn' => $asn,
                'asnList' => $asnList,
                'ranked' => [],
                'details' => [],
                'statusKomitmen' => $asn->status_komitmen_10_tahun,
                'message' => 'Saat ini belum ada Unit Kerja yang membuka kuota formasi lowong (ABK > 0).',
            ]);
        }

        // =========================================================================
        // LANGKAH 1: Pembentukan Matriks Keputusan Alternatif Unit Kerja
        // Kriteria Mode 2 (Atribut Unit Kerja terhadap ASN):
        // - C1: Jarak Rute dari Rumah ASN ke Kantor Unit Baru (Cost)
        // - C2: Sisa Kuota Formasi ABK (Benefit)
        // - C3: Efisiensi Penghematan Jarak (Benefit)
        // =========================================================================
        $alternatives = [];
        foreach ($unitAlternatif as $unit) {
            $jarakBaru = $this->haversine->calculateDistance(
                (float)$asn->domisili_lat,
                (float)$asn->domisili_lng,
                (float)$unit->kantor_lat,
                (float)$unit->kantor_lng
            );

            $hematJarak = max(0.0, (float)$asn->jarak_ke_kantor_km - $jarakBaru);

            $formasiTersedia = $unit->formasiJabatan
                ->map(fn($f) => "{$f->nama_jabatan} ({$f->kuota_lowong} kursi)")
                ->values()
                ->toArray();

            $alternatives[] = [
                'id' => $unit->id,
                'nama' => $unit->nama_unit,
                'kategori' => $unit->kategori,
                'kantor_lat' => (float)$unit->kantor_lat,
                'kantor_lng' => (float)$unit->kantor_lng,
                'kuota' => (int)$unit->kuota_formasi_kosong,
                'c1_jarak' => (float)$jarakBaru,
                'c2_kuota' => (float)$unit->kuota_formasi_kosong,
                'c3_hemat' => (float)$hematJarak,
                'formasi_tersedia' => $formasiTersedia,
            ];
        }

        // =========================================================================
        // LANGKAH 2: Konfigurasi Bobot Dinamis Berdasarkan Kebijakan Kepegawaian (Non-AHP)
        // Total Bobot Wajib = 1.00 (100%)
        // =========================================================================
        $criteria = [
            'c1_jarak' => ['bobot' => 0.40, 'atribut' => 'cost'],
            'c2_kuota' => ['bobot' => 0.30, 'atribut' => 'benefit'],
            'c3_hemat' => ['bobot' => 0.30, 'atribut' => 'benefit'],
        ];

        // Jalankan Komputasi TOPSIS
        $topsisResult = $this->topsis->calculate($alternatives, $criteria);

        return view('asn.simulasi-mandiri', [
            'asn' => $asn,
            'asnList' => $asnList,
            'ranked' => $topsisResult['ranked'],
            'details' => $topsisResult['details'],
            'statusKomitmen' => $asn->status_komitmen_10_tahun,
            'message' => null,
        ]);
    }

    /**
     * Memproses pengajuan mutasi mandiri oleh ASN ke unit kerja pilihan.
     */
    public function ajukan(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'asn_id' => 'required|exists:asn_master,id',
            'unit_tujuan_id' => 'required|exists:unit_kerja,id',
            'alasan_mutasi' => 'required|string|max:500',
            'nilai_preferensi_v' => 'nullable|numeric',
            'ranking' => 'nullable|integer',
        ], [
            'unit_tujuan_id.required' => 'Silakan pilih unit kerja tujuan mutasi.',
            'alasan_mutasi.required' => 'Alasan permohonan mutasi wajib diisi.',
        ]);

        $asn = AsnMaster::with('unitSaatIni')->findOrFail($validated['asn_id']);
        $unitTujuan = UnitKerja::findOrFail($validated['unit_tujuan_id']);

        // Verifikasi kepatuhan: Kuota formasi unit tujuan wajib > 0
        if ($unitTujuan->kuota_formasi_kosong <= 0) {
            return back()->with('error', "Permohonan Ditolak: Unit Kerja {$unitTujuan->nama_unit} tidak memiliki kuota formasi kosong (Zero-ABK).");
        }

        // Hitung jarak baru
        $jarakBaru = $this->haversine->calculateDistance(
            (float)$asn->domisili_lat,
            (float)$asn->domisili_lng,
            (float)$unitTujuan->kantor_lat,
            (float)$unitTujuan->kantor_lng
        );

        $hematJarak = max(0.0, (float)$asn->jarak_ke_kantor_km - $jarakBaru);

        // 1. Simpan atau perbarui permohonan ke tabel rekomendasi_mutasi
        RekomendasiMutasi::create([
            'id_asn' => $asn->id,
            'id_unit_asal' => $asn->id_unit_saat_ini,
            'id_unit_tujuan' => $unitTujuan->id,
            'jarak_asal_km' => $asn->jarak_ke_kantor_km,
            'jarak_baru_km' => $jarakBaru,
            'selisih_hemat_jarak_km' => $hematJarak,
            'nilai_preferensi_v' => (float)($validated['nilai_preferensi_v'] ?? 0.850000),
            'ranking' => (int)($validated['ranking'] ?? 1),
            'status_usulan' => 'diajukan_baperjakat',
            'catatan_baperjakat' => "Permohonan mandiri diajukan oleh {$asn->nama}. Alasan: {$validated['alasan_mutasi']}",
        ]);

        // 2. Update status permohonan pada profil master ASN
        $asn->update([
            'status_permohonan_mutasi' => true,
            'alasan_mutasi' => $validated['alasan_mutasi'],
        ]);

        return redirect()->route('asn.simulasi-mandiri', ['asn_id' => $asn->id])
            ->with('success', "Permohonan mutasi mandiri ke '{$unitTujuan->nama_unit}' berhasil diajukan! Berkas permohonan telah diteruskan ke sidang tim Baperjakat BKPSDM.");
    }
}

