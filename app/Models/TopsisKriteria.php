<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TopsisKriteria extends Model
{
    protected $table = 'topsis_kriteria';

    protected $fillable = [
        'kode_kriteria',
        'nama_kriteria',
        'bobot',
        'atribut',
        'keterangan',
    ];

    protected $casts = [
        'bobot' => 'float',
    ];
}
