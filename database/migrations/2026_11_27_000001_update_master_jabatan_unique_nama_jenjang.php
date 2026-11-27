<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengubah aturan keunikan pada master_jabatan:
     * nama_jabatan boleh sama selama jenjang_jabatan berbeda (composite unique).
     */
    public function up(): void
    {
        if (Schema::hasTable('master_jabatan')) {
            Schema::table('master_jabatan', function (Blueprint $table) {
                $table->dropUnique('master_jabatan_nama_jabatan_unique');
                $table->unique(['nama_jabatan', 'jenjang_jabatan'], 'master_jabatan_nama_jenjang_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('master_jabatan')) {
            Schema::table('master_jabatan', function (Blueprint $table) {
                $table->dropUnique('master_jabatan_nama_jenjang_unique');
                $table->unique('nama_jabatan', 'master_jabatan_nama_jabatan_unique');
            });
        }
    }
};
