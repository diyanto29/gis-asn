<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengajuanUbahLokasi extends Model
{
    protected $table = 'pengajuan_ubah_lokasi';

    protected $fillable = [
        'id_asn',
        'user_id',
        'lat_lama',
        'lng_lama',
        'alamat_lama',
        'jarak_lama_km',
        'lat_baru',
        'lng_baru',
        'provinsi_baru',
        'kabupaten_kota_baru',
        'kecamatan_baru',
        'kelurahan_desa_baru',
        'alamat_detail_baru',
        'jarak_baru_km',
        'zona_baru',
        'ktp_path',
        'status',
        'catatan_admin',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'lat_lama' => 'float',
        'lng_lama' => 'float',
        'jarak_lama_km' => 'float',
        'lat_baru' => 'float',
        'lng_baru' => 'float',
        'jarak_baru_km' => 'float',
        'verified_at' => 'datetime',
    ];

    public function asn(): BelongsTo
    {
        return $this->belongsTo(AsnMaster::class, 'id_asn');
    }

    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
