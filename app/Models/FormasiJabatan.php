<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model FormasiJabatan
 * 
 * Merepresentasikan detail formasi spesifik per jabatan, jenjang, kualifikasi,
 * dan tahun anggaran untuk mendukung audit tracing riwayat formasi multi-tahun.
 */
class FormasiJabatan extends Model
{
    use HasFactory;

    protected $table = 'formasi_jabatan';

    protected $fillable = [
        'id_unit_kerja',
        'id_master_jabatan',
        'tahun_anggaran',
        'periode',
        'nama_jabatan',
        'jenis_jabatan',
        'jenjang_jabatan',
        'kualifikasi_pendidikan',
        'kebutuhan_abk',
        'bezetting_riil',
        'kuota_lowong',
        'status',
        'keterangan',
    ];

    protected $casts = [
        'tahun_anggaran' => 'integer',
        'kebutuhan_abk' => 'integer',
        'bezetting_riil' => 'integer',
        'kuota_lowong' => 'integer',
    ];

    public function unitKerja(): BelongsTo
    {
        return $this->belongsTo(UnitKerja::class, 'id_unit_kerja');
    }

    public function masterJabatan(): BelongsTo
    {
        return $this->belongsTo(MasterJabatan::class, 'id_master_jabatan');
    }

    public function asns(): HasMany
    {
        return $this->hasMany(AsnMaster::class, 'id_formasi_jabatan');
    }

    /**
     * Menghitung ulang Bezetting Riil secara otomatis dari jumlah PNS pemangku
     * jabatan pada unit kerja ini, lalu menghitung Kuota Lowong = max(0, ABK - Bezetting)
     * dan Status Formasi ('terbuka' jika lowong > 0, 'terisi' jika penuh).
     */
    public function recalculateBezetting(): void
    {
        $bezetting = AsnMaster::where('id_unit_saat_ini', $this->id_unit_kerja)
            ->where(function ($q) {
                $q->where('id_formasi_jabatan', $this->id);
                if ($this->id_master_jabatan) {
                    $q->orWhere('id_master_jabatan', $this->id_master_jabatan);
                }
            })
            ->count();

        $lowong = max(0, (int)$this->kebutuhan_abk - $bezetting);

        $this->bezetting_riil = $bezetting;
        $this->kuota_lowong = $lowong;
        $this->status = $lowong > 0 ? 'terbuka' : 'terisi';
        $this->save();

        if ($this->unitKerja) {
            $this->unitKerja->syncKuotaFormasi((int)$this->tahun_anggaran);
        }
    }

    public function scopeTahun($query, int $tahun)
    {
        return $query->where('tahun_anggaran', $tahun);
    }

    public function scopeTerbuka($query)
    {
        return $query->where('status', 'terbuka');
    }
}

