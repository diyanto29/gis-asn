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
        'siasn_id',
        'kode_cepat',
        'nama_jabatan',
        'jenis_jabatan',
        'jenjang_jabatan',
        'kelas_jabatan',
        'bup_usia',
        'rumpun_jabatan',
        'kualifikasi_pendidikan',
    ];

    protected $casts = [
        'kelas_jabatan' => 'integer',
        'bup_usia' => 'integer',
    ];

    public function asns(): HasMany
    {
        return $this->hasMany(AsnMaster::class, 'id_master_jabatan');
    }

    public function formasis(): HasMany
    {
        return $this->hasMany(FormasiJabatan::class, 'id_master_jabatan');
    }
}
