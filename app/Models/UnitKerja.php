<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitKerja extends Model
{
    protected $table = 'unit_kerja';

    protected $fillable = [
        'kode_unit',
        'nama_unit',
        'kategori',
        'kantor_lat',
        'kantor_lng',
        'alamat_kantor',
        'kuota_formasi_kosong',
        'bezetting_saat_ini',
        'kebutuhan_abk',
    ];

    protected $casts = [
        'kantor_lat' => 'float',
        'kantor_lng' => 'float',
        'kuota_formasi_kosong' => 'integer',
        'bezetting_saat_ini' => 'integer',
        'kebutuhan_abk' => 'integer',
    ];

    public function pegawai(): HasMany
    {
        return $this->hasMany(AsnMaster::class, 'id_unit_saat_ini');
    }

    public function rekomendasiMasuk(): HasMany
    {
        return $this->hasMany(RekomendasiMutasi::class, 'id_unit_tujuan');
    }

    public function rekomendasiKeluar(): HasMany
    {
        return $this->hasMany(RekomendasiMutasi::class, 'id_unit_asal');
    }

    public function formasiJabatan(): HasMany
    {
        return $this->hasMany(FormasiJabatan::class, 'id_unit_kerja');
    }

    /**
     * Sinkronisasi kuota_formasi_kosong unit kerja dengan total kuota_lowong
     * formasi jabatan yang berstatus 'terbuka' pada tahun anggaran tertentu.
     */
    public function syncKuotaFormasi(int $tahun = 2026): void
    {
        $totalKuota = $this->formasiJabatan()
            ->where('tahun_anggaran', $tahun)
            ->where('status', 'terbuka')
            ->sum('kuota_lowong');

        $this->update(['kuota_formasi_kosong' => $totalKuota]);
    }
}
