<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('topsis_kriteria', function (Blueprint $table) {
            if (!Schema::hasColumn('topsis_kriteria', 'parameter_key')) {
                $table->string('parameter_key', 50)->default('jarak_baru')->after('kode_kriteria');
            }
            if (!Schema::hasColumn('topsis_kriteria', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('atribut');
            }
        });

        Schema::table('asn_master', function (Blueprint $table) {
            if (!Schema::hasColumn('asn_master', 'nilai_skp')) {
                $table->decimal('nilai_skp', 5, 2)->default(88.50)->after('masa_kerja_tahun');
            }
            if (!Schema::hasColumn('asn_master', 'tanggungan_keluarga')) {
                $table->unsignedTinyInteger('tanggungan_keluarga')->default(2)->after('nilai_skp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topsis_kriteria', function (Blueprint $table) {
            $table->dropColumn(['parameter_key', 'is_active']);
        });

        Schema::table('asn_master', function (Blueprint $table) {
            $table->dropColumn(['nilai_skp', 'tanggungan_keluarga']);
        });
    }
};
