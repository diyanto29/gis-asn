<?php

namespace App\Http\Controllers;

use App\Models\AsnMaster;
use App\Models\FormasiJabatan;
use App\Models\RekomendasiMutasi;
use App\Models\TopsisKriteria;
use App\Models\UnitKerja;
use App\Services\HaversineService;
use App\Services\MutasiRuleService;
use App\Services\TopsisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Class SimulasiMandiriController
 *
 * Mengelola SPK TOPSIS Portal PNS: Rekomendasi Lokasi Penempatan Optimal
 * bagi Pegawai ASN ke Unit Kerja dengan formasi jabatan yang cocok & tersedia,
 * menggunakan 5 kriteria standar BKPSDM dan syarat mutlak (Poin 1-3).
 *
 * @package App\Http\Controllers
 */
class SimulasiMandiriController extends Controller
{
    public function __construct(
        protected HaversineService $haversine,
        protected TopsisService $topsis,
        protected MutasiRuleService $mutasiRule
    ) {}

    /**
     * Menampilkan dashboard simulasi rekomendasi mandiri ASN (Portal PNS).
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = Auth::user();

        if ($user->isAsn() && $user->asn) {
            $asn = $user->asn->load(['unitSaatIni', 'masterJabatan']);
        } else {
            $targetAsnId = $request->query('asn_id');
            $asn = $targetAsnId
                ? AsnMaster::with(['unitSaatIni', 'masterJabatan'])->findOrFail($targetAsnId)
                : AsnMaster::with(['unitSaatIni', 'masterJabatan'])->whereNotNull('domisili_lat')->first();
        }

        if (!$asn) {
            return redirect()->route('login')
                ->with('error', 'Profil data ASN Anda belum tertaut. Silakan hubungi Administrator BKPSDM.');
        }

        $asnList = $user->isAdmin()
            ? AsnMaster::orderBy('nama')->get(['id', 'nip', 'nama'])
            : collect([$asn]);

        $unitStats = $this->mutasiRule->getAllUnitStats();
        $kelayakan = $this->mutasiRule->cekKelayakanAsn($asn);
        $unitAsalEval = $this->mutasiRule->evaluasiUnitAsal($asn->unitSaatIni, $unitStats);

        // POIN 3 MUTLAK: Masa jabatan wajib >= batas minimal (default 2 tahun)
        if (!$kelayakan['masa_jabatan_ok']) {
            return view('asn.simulasi-mandiri', [
                'asn' => $asn,
                'asnList' => $asnList,
                'ranked' => [],
                'details' => [],
                'statusKomitmen' => $asn->status_komitmen_10_tahun,
                'kelayakan' => $kelayakan,
                'unitAsalEval' => $unitAsalEval,
                'message' => $kelayakan['alasan_tidak_layak'],
            ]);
        }

        // POIN 1 & 2 MUTLAK: Ambil formasi terbuka (kuota_lowong > 0) di unit lain yang cocok
        $openFormasiLain = FormasiJabatan::with(['masterJabatan', 'unitKerja'])
            ->where('kuota_lowong', '>', 0)
            ->where('id_unit_kerja', '!=', $asn->id_unit_saat_ini)
            ->get();

        $matchingFormasis = $this->mutasiRule->cariFormasiCocok($asn, $openFormasiLain);
        $matchingByUnit = $matchingFormasis->groupBy('id_unit_kerja');

        if ($matchingByUnit->isEmpty()) {
            $aturanMatch = $this->mutasiRule->deskripsiAturanMatch($asn);

            return view('asn.simulasi-mandiri', [
                'asn' => $asn,
                'asnList' => $asnList,
                'ranked' => [],
                'details' => [],
                'statusKomitmen' => $asn->status_komitmen_10_tahun,
                'kelayakan' => $kelayakan,
                'unitAsalEval' => $unitAsalEval,
                'message' => "Saat ini belum ada Unit Kerja tujuan yang memiliki formasi lowong yang cocok untuk {$aturanMatch}.",
            ]);
        }

        // Bentuk matriks 5 kriteria TOPSIS (C1-C5)
        $alternatives = [];
        foreach ($matchingByUnit as $unitId => $formasiList) {
            $unit = $formasiList->first()->unitKerja;
            if (!$unit || !$unit->kantor_lat || !$unit->kantor_lng) {
                continue;
            }

            $jarakBaru = $this->haversine->calculateDistance(
                (float)$asn->domisili_lat,
                (float)$asn->domisili_lng,
                (float)$unit->kantor_lat,
                (float)$unit->kantor_lng
            );

            $hematJarak = round(max(0.0, (float)$asn->jarak_ke_kantor_km - $jarakBaru), 2);
            $kuotaCocok = (int)$formasiList->sum('kuota_lowong');

            $formasiTersedia = $formasiList->map(function ($f) {
                $kls = $f->masterJabatan?->kelas_jabatan ? " [Kls {$f->masterJabatan->kelas_jabatan}]" : '';
                return "{$f->nama_jabatan}{$kls} ({$f->kuota_lowong} kursi)";
            })->values()->toArray();

            $alternatives[] = [
                'id' => $unit->id,
                'nama' => $unit->nama_unit,
                'kategori' => $unit->kategori,
                'kantor_lat' => (float)$unit->kantor_lat,
                'kantor_lng' => (float)$unit->kantor_lng,
                'kuota' => $kuotaCocok,
                'c1_jarak' => (float)$jarakBaru,
                'c3_hemat' => (float)$hematJarak,
                'formasi_tersedia' => $formasiTersedia,

                // 5 Kriteria TOPSIS standar BKPSDM
                'C1' => (float)$jarakBaru,
                'C2' => (float)$kelayakan['masa_jabatan_tahun'],
                'C3' => (float)$kelayakan['masa_kerja_tahun'],
                'C4' => (float)$kuotaCocok,
                'C5' => (float)$unitAsalEval['skor'],
            ];
        }

        // Ambil bobot kriteria dinamis dari database (mode pns_to_dinas)
        $kriteriaModels = TopsisKriteria::where('kategori_mode', 'pns_to_dinas')
            ->where('is_active', true)
            ->orderBy('kode_kriteria')
            ->get();

        $criteria = [];
        foreach ($kriteriaModels as $k) {
            $criteria[$k->kode_kriteria] = [
                'bobot' => (float)$k->bobot,
                'atribut' => $k->atribut,
            ];
        }

        if (empty($criteria)) {
            $criteria = [
                'C1' => ['bobot' => 0.35, 'atribut' => 'cost'],
                'C2' => ['bobot' => 0.20, 'atribut' => 'benefit'],
                'C3' => ['bobot' => 0.15, 'atribut' => 'benefit'],
                'C4' => ['bobot' => 0.15, 'atribut' => 'benefit'],
                'C5' => ['bobot' => 0.15, 'atribut' => 'benefit'],
            ];
        }

        $topsisResult = $this->topsis->calculate($alternatives, $criteria);

        return view('asn.simulasi-mandiri', [
            'asn' => $asn,
            'asnList' => $asnList,
            'ranked' => $topsisResult['ranked'],
            'details' => $topsisResult['details'],
            'statusKomitmen' => $asn->status_komitmen_10_tahun,
            'kelayakan' => $kelayakan,
            'unitAsalEval' => $unitAsalEval,
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

