<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekomendasiMutasi extends Model
{
    protected $table = 'rekomendasi_mutasi';

    protected $fillable = [
        'id_asn',
        'id_unit_asal',
        'id_unit_tujuan',
        'jarak_asal_km',
        'jarak_baru_km',
        'selisih_hemat_jarak_km',
        'nilai_preferensi_v',
        'ranking',
        'status_usulan',
        'catatan_baperjakat',
        'tanggal_kalkulasi',
    ];

    protected $casts = [
        'jarak_asal_km' => 'float',
        'jarak_baru_km' => 'float',
        'selisih_hemat_jarak_km' => 'float',
        'nilai_preferensi_v' => 'float',
        'ranking' => 'integer',
        'tanggal_kalkulasi' => 'datetime',
    ];

    public function asn(): BelongsTo
    {
        return $this->belongsTo(AsnMaster::class, 'id_asn');
    }

    public function unitAsal(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit_asal');
    }

    public function unitTujuan(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit_tujuan');
    }
}
