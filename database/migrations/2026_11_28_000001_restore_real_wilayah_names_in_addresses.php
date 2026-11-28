<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mengembalikan nama wilayah administratif riil (Kabupaten Indramayu) pada kolom
     * alamat geografis (asn_master.kabupaten_kota & unit_kerja.alamat_kantor) agar
     * sinkron 100% dengan API Wilayah.id (Provinsi -> Kabupaten/Kota -> Kecamatan -> Desa).
     */
    public function up(): void
    {
        DB::table('asn_master')
            ->where('kabupaten_kota', 'Kabupaten XYZ')
            ->update(['kabupaten_kota' => 'Kabupaten Indramayu']);

        DB::table('unit_kerja')
            ->where('alamat_kantor', 'like', '%Kabupaten XYZ%')
            ->update([
                'alamat_kantor' => DB::raw("REPLACE(alamat_kantor, 'Kabupaten XYZ', 'Kabupaten Indramayu')"),
            ]);
    }

    public function down(): void
    {
        // Tidak perlu revert karena nama wilayah administratif harus riil untuk Wilayah.id.
    }
};
