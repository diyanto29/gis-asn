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
}
