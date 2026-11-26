<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan atribut referensi SIASN BKN pada master_jabatan
     * (siasn_id, kode_cepat, kelas_jabatan, bup_usia) serta memperkuat
     * relasi foreign key id_master_jabatan pada tabel formasi_jabatan.
     */
    public function up(): void
    {
        if (Schema::hasTable('master_jabatan')) {
            Schema::table('master_jabatan', function (Blueprint $table) {
                if (!Schema::hasColumn('master_jabatan', 'siasn_id')) {
                    $table->string('siasn_id', 64)->nullable()->unique()->after('kode_jabatan');
                }
                if (!Schema::hasColumn('master_jabatan', 'kode_cepat')) {
                    $table->string('kode_cepat', 30)->nullable()->index()->after('siasn_id');
                }
                if (!Schema::hasColumn('master_jabatan', 'kelas_jabatan')) {
                    $table->unsignedTinyInteger('kelas_jabatan')->nullable()->index()->after('jenjang_jabatan');
                }
                if (!Schema::hasColumn('master_jabatan', 'bup_usia')) {
                    $table->unsignedTinyInteger('bup_usia')->nullable()->after('kelas_jabatan');
                }
            });
        }

        if (Schema::hasTable('formasi_jabatan') && !Schema::hasColumn('formasi_jabatan', 'id_master_jabatan')) {
            Schema::table('formasi_jabatan', function (Blueprint $table) {
                $table->foreignId('id_master_jabatan')
                    ->nullable()
                    ->after('id_unit_kerja')
                    ->constrained('master_jabatan')
                    ->nullOnDelete();
            });

            // Sinkronkan relasi existing jika sudah ada data pada formasi_jabatan
            DB::statement('
                UPDATE formasi_jabatan f
                JOIN master_jabatan m ON LOWER(TRIM(f.nama_jabatan)) = LOWER(TRIM(m.nama_jabatan))
                SET f.id_master_jabatan = m.id
                WHERE f.id_master_jabatan IS NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('formasi_jabatan') && Schema::hasColumn('formasi_jabatan', 'id_master_jabatan')) {
            Schema::table('formasi_jabatan', function (Blueprint $table) {
                $table->dropForeign(['id_master_jabatan']);
                $table->dropColumn('id_master_jabatan');
            });
        }

        if (Schema::hasTable('master_jabatan')) {
            Schema::table('master_jabatan', function (Blueprint $table) {
                $columns = array_values(array_filter(
                    ['siasn_id', 'kode_cepat', 'kelas_jabatan', 'bup_usia'],
                    fn ($col) => Schema::hasColumn('master_jabatan', $col)
                ));

                if (in_array('siasn_id', $columns, true)) {
                    $table->dropUnique(['siasn_id']);
                }
                if (in_array('kode_cepat', $columns, true)) {
                    $table->dropIndex(['kode_cepat']);
                }
                if (in_array('kelas_jabatan', $columns, true)) {
                    $table->dropIndex(['kelas_jabatan']);
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
