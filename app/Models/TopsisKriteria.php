<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopsisKriteria extends Model
{
    protected $table = 'topsis_kriteria';

    protected $fillable = [
        'kategori_mode',
        'kode_kriteria',
        'parameter_key',
        'nama_kriteria',
        'bobot',
        'atribut',
        'is_active',
        'keterangan',
    ];

    protected $casts = [
        'bobot' => 'float',
        'is_active' => 'boolean',
    ];

    public function scopePnsToDinas($query)
    {
        return $query->where('kategori_mode', 'pns_to_dinas');
    }

    public function scopeDinasToPns($query)
    {
        return $query->where('kategori_mode', 'dinas_to_pns');
    }
}
