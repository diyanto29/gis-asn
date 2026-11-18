<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model MasterLokasi
 * 
 * Merepresentasikan data referensi spasial kecamatan dan titik koordinat pusat
 * di wilayah Kabupaten Indramayu untuk mendukung sistem independen SIMUTASI-GIS.
 */
class MasterLokasi extends Model
{
    use HasFactory;

    protected $table = 'master_lokasi';

    protected $fillable = [
        'kode_kecamatan',
        'nama_kecamatan',
        'pusat_lat',
        'pusat_lng',
        'jumlah_desa',
        'keterangan',
    ];

    protected $casts = [
        'pusat_lat' => 'float',
        'pusat_lng' => 'float',
        'jumlah_desa' => 'integer',
    ];
}

