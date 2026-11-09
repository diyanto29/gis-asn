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
        'pangkat_golongan',
        'masa_kerja_tahun',
        'tmt_kerja_unit',
        'id_unit_saat_ini',
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
        'status_permohonan_mutasi' => 'boolean',
    ];

    public function unitSaatIni(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit_saat_ini');
    }

    public function rekomendasi(): HasMany
    {
        return $this->hasMany(RekomendasiMutasi::class, 'id_asn');
    }

    public function getNamaLengkapAttribute(): string
    {
        $depan = $this->gelar_depan ? $this->gelar_depan . ' ' : '';
        $belakang = $this->gelar_belakang ? ', ' . $this->gelar_belakang : '';
        return $depan . $this->nama . $belakang;
    }
}
