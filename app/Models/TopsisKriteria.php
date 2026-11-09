<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopsisKriteria extends Model
{
    protected $table = 'topsis_kriteria';

    protected $fillable = [
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
}
