<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterJabatan extends Model
{
    use HasFactory;

    protected $table = 'master_jabatan';

    protected $fillable = [
        'kode_jabatan',
        'nama_jabatan',
        'jenis_jabatan',
        'jenjang_jabatan',
        'rumpun_jabatan',
        'kualifikasi_pendidikan',
    ];

    public function asns(): HasMany
    {
        return $this->hasMany(AsnMaster::class, 'id_master_jabatan');
    }

    public function formasis(): HasMany
    {
        return $this->hasMany(FormasiJabatan::class, 'nama_jabatan', 'nama_jabatan');
    }
}
