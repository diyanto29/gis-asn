<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AsnMaster extends Model
{
    protected $table = 'asn_master';

    protected $fillable = [
        'nip',
        'nama',
        'gelar_depan',
        'gelar_belakang',
        'jabatan',
        'id_master_jabatan',
        'id_formasi_jabatan',
        'jenis_jabatan',
        'pangkat_golongan',
        'tmt_golongan',
        'masa_kerja_tahun',
        'nilai_skp',
        'tanggungan_keluarga',
        'tmt_kerja_unit',
        'tgl_jabatan',
        'tgl_cpns',
        'tgl_pns',
        'tingkat_pendidikan',
        'pendidikan_terakhir',
        'id_unit_saat_ini',
        'unit_kerja_induk',
        'domisili_lat',
        'domisili_lng',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'kelurahan_desa',
        'kode_desa',
        'alamat_detail',
        'jarak_ke_kantor_km',
        'zona_jarak',
        'status_permohonan_mutasi',
        'alasan_mutasi',
    ];

    protected $casts = [
        'domisili_lat' => 'float',
        'domisili_lng' => 'float',
        'jarak_ke_kantor_km' => 'float',
        'masa_kerja_tahun' => 'integer',
        'tmt_kerja_unit' => 'date',
        'tgl_jabatan' => 'date',
        'tgl_cpns' => 'date',
        'tgl_pns' => 'date',
        'tmt_golongan' => 'date',
        'status_permohonan_mutasi' => 'boolean',
    ];

    protected $appends = [
        'nama_lengkap',
    ];

    public function masterJabatan(): BelongsTo
    {
        return $this->belongsTo(MasterJabatan::class, 'id_master_jabatan');
    }

    public function formasiJabatan(): BelongsTo
    {
        return $this->belongsTo(FormasiJabatan::class, 'id_formasi_jabatan');
    }

    public function unitSaatIni(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit_saat_ini');
    }

    public function rekomendasi(): HasMany
    {
        return $this->hasMany(RekomendasiMutasi::class, 'id_asn');
    }

    public function pengajuanLokasi(): HasMany
    {
        return $this->hasMany(PengajuanUbahLokasi::class, 'id_asn');
    }

    public function getNamaLengkapAttribute(): string
    {
        $depan = $this->gelar_depan ? $this->gelar_depan . ' ' : '';
        $belakang = $this->gelar_belakang ? ', ' . $this->gelar_belakang : '';
        return $depan . $this->nama . $belakang;
    }

    /**
     * Parsing tahun pengangkatan CPNS dari NIP (digit 9-12).
     */
    public function getTahunCpnsAttribute(): int
    {
        if (strlen($this->nip) >= 12) {
            $year = (int)substr($this->nip, 8, 4);
            if ($year >= 1970 && $year <= 2030) {
                return $year;
            }
        }
        return now()->year - ($this->masa_kerja_tahun ?? 5);
    }

    /**
     * Hitung masa pengabdian riil sejak SK CPNS.
     */
    public function getMasaPengabdianTahunAttribute(): int
    {
        return max(0, (int)now()->year - $this->tahun_cpns);
    }

    /**
     * Status Regulasi Komitmen Mutasi Keluar Daerah (Aturan Dinamis, Default: Minimal 5 Tahun Pengabdian).
     */
    public function getStatusKomitmen10TahunAttribute(): array
    {
        $rules = \App\Services\MutasiRuleService::getRules();
        $minTahunKeluar = (int)($rules['min_masa_kerja_keluar_tahun'] ?? 5);
        $tahun = $this->tahun_cpns;
        $masaPengabdian = $this->masa_pengabdian_tahun;

        if ($masaPengabdian < $minTahunKeluar) {
            $sisa = $minTahunKeluar - $masaPengabdian;
            return [
                'is_bound' => true,
                'is_locked_external' => true,
                'tahun_cpns' => $tahun,
                'masa_pengabdian' => $masaPengabdian,
                'sisa_tahun' => $sisa,
                'min_tahun_keluar' => $minTahunKeluar,
                'status_label' => "Ikatan Dinas {$tahun} (Sisa {$sisa} Th)",
                'badge_color' => 'bg-amber-100 text-amber-800 border-amber-300',
                'pill_short' => "Ikatan {$sisa} Th",
                'hak_rotasi_internal' => true,
                'hak_mutasi_keluar' => false,
                'dasar_hukum' => "Aturan Mutasi Keluar Daerah (Minimal {$minTahunKeluar} Tahun)",
                'notice' => "Wajib mengabdi minimal {$minTahunKeluar} tahun (TMT CPNS {$tahun}). Rotasi internal antar-SKPD Kabupaten XYZ DIIZINKAN, namun permohonan mutasi keluar daerah TERKUNCI (Sisa {$sisa} tahun)."
            ];
        }

        return [
            'is_bound' => false,
            'is_locked_external' => false,
            'tahun_cpns' => $tahun,
            'masa_pengabdian' => $masaPengabdian,
            'sisa_tahun' => 0,
            'min_tahun_keluar' => $minTahunKeluar,
            'status_label' => "Memenuhi Syarat {$minTahunKeluar} Th",
            'badge_color' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'pill_short' => "Syarat {$minTahunKeluar} Th Terpenuhi",
            'hak_rotasi_internal' => true,
            'hak_mutasi_keluar' => true,
            'dasar_hukum' => "Aturan Mutasi Keluar Daerah (Minimal {$minTahunKeluar} Tahun)",
            'notice' => "Masa pengabdian telah memenuhi syarat minimal {$minTahunKeluar} tahun ({$masaPengabdian} tahun). Berhak mengajukan rotasi internal maupun usulan mutasi keluar daerah."
        ];
    }

    /**
     * Analisis Kategori Spasial Domisili (Indramayu Internal / Perbatasan Cirebon / Ekstra-Teritorial).
     */
    public function getStatusSpasialRegionalAttribute(): array
    {
        $jarak = (float)$this->jarak_ke_kantor_km;
        $kab = strtolower($this->kabupaten_kota ?? '');

        // Kasus 1: Ekstra-Teritorial (Luar Pulau / Luar Jangkauan Komuter > 100 km)
        if ($jarak > 100.0 || (str_contains($kab, 'riau') || str_contains($kab, 'medan') || str_contains($kab, 'surabaya') || str_contains($kab, 'pekanbaru'))) {
            return [
                'type' => 'ekstra_teritorial',
                'badge_label' => 'Domisili Ekstra-Teritorial (>100 km)',
                'badge_color' => 'bg-rose-100 text-rose-800 border-rose-300',
                'pill_short' => 'Luar Daerah (>100 km)',
                'is_alert' => true,
                'notice_title' => 'Peringatan Anomali Domisili (Luar Jangkauan Komuter Harian)',
                'notice' => "Domisili ASN terdeteksi di luar radius komuter harian ({$this->kabupaten_kota}, jarak ~" . number_format($jarak, 1) . " km). Rotasi internal antar-kantor di Kabupaten XYZ tidak lagi efektif memangkas jarak perjalanan harian. Direkomendasikan penelaahan status khusus Baperjakat untuk 'Usulan Pindah Instansi Luar Daerah (Lolos Butuh BKN)'."
            ];
        }

        // Kasus 2: Komuter Wilayah Perbatasan (Cirebon, Majalengka, Subang 20-100 km)
        if (str_contains($kab, 'cirebon') || str_contains($kab, 'majalengka') || str_contains($kab, 'subang') || $jarak >= 25.0) {
            return [
                'type' => 'perbatasan_komuter',
                'badge_label' => 'Komuter Lintas Batas (Perbatasan Antar-Kabupaten)',
                'badge_color' => 'bg-purple-100 text-purple-800 border-purple-300',
                'pill_short' => 'Komuter Cirebon/Batas',
                'is_alert' => false,
                'notice_title' => 'Optimalisasi Komuter Wilayah Perbatasan',
                'notice' => "ASN berdomisili di wilayah perbatasan ({$this->kabupaten_kota}). Sistem SPK TOPSIS memprioritaskan rekomendasi unit kerja Kabupaten XYZ di zona koridor terdekat (seperti Krangkeng, Kertasmaya, Sukagumiwang) guna memangkas durasi perjalanan tanpa melanggar yurisdiksi Pemkab XYZ."
            ];
        }

        // Kasus 3: Wilayah Internal Indramayu Biasa
        return [
            'type' => 'internal_indramayu',
            'badge_label' => 'Domisili Internal Kabupaten XYZ',
            'badge_color' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'pill_short' => 'Lokal Kabupaten XYZ',
            'is_alert' => false,
            'notice_title' => 'Mobilitas Internal Daerah',
            'notice' => "Domisili berada di dalam lingkup Kabupaten XYZ. Pemetaan rute mengemudi OSRM dioptimalkan mengikuti jaringan jalan aspal lokal."
        ];
    }
}
