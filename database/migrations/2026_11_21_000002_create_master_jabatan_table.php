<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('master_jabatan')) {
            Schema::create('master_jabatan', function (Blueprint $table) {
                $table->id();
                $table->string('kode_jabatan', 50)->nullable()->unique();
                $table->string('nama_jabatan', 255)->unique();
                $table->enum('jenis_jabatan', ['struktural', 'fungsional', 'pelaksana'])->default('pelaksana');
                $table->string('jenjang_jabatan', 100)->nullable();
                $table->string('rumpun_jabatan', 150)->nullable();
                $table->text('kualifikasi_pendidikan')->nullable();
                $table->timestamps();
            });
        }

        // Tambah foreign key id_master_jabatan ke asn_master jika belum ada
        if (Schema::hasTable('asn_master') && !Schema::hasColumn('asn_master', 'id_master_jabatan')) {
            Schema::table('asn_master', function (Blueprint $table) {
                $table->foreignId('id_master_jabatan')->nullable()->after('id_formasi_jabatan')
                    ->constrained('master_jabatan')->nullOnDelete();
            });
        }

        // Populasikan master_jabatan otomatis dari data unik formasi_jabatan
        if (Schema::hasTable('formasi_jabatan')) {
            $uniquePositions = DB::table('formasi_jabatan')
                ->select('nama_jabatan', 'jenis_jabatan', 'jenjang_jabatan', 'kualifikasi_pendidikan')
                ->groupBy('nama_jabatan', 'jenis_jabatan', 'jenjang_jabatan', 'kualifikasi_pendidikan')
                ->get();

            $inserted = 0;
            $seen = [];
            foreach ($uniquePositions as $idx => $pos) {
                $cleanName = trim($pos->nama_jabatan);
                if (empty($cleanName) || isset($seen[$cleanName])) {
                    continue;
                }
                $seen[$cleanName] = true;

                $kode = sprintf('JBT-%04d', ++$inserted);
                DB::table('master_jabatan')->updateOrInsert(
                    ['nama_jabatan' => $cleanName],
                    [
                        'kode_jabatan' => $kode,
                        'jenis_jabatan' => in_array($pos->jenis_jabatan, ['struktural', 'fungsional', 'pelaksana']) ? $pos->jenis_jabatan : 'pelaksana',
                        'jenjang_jabatan' => $pos->jenjang_jabatan,
                        'kualifikasi_pendidikan' => $pos->kualifikasi_pendidikan,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            // Korelasikan asn_master dengan master_jabatan
            DB::statement('
                UPDATE asn_master a
                JOIN master_jabatan m ON a.jabatan = m.nama_jabatan
                SET a.id_master_jabatan = m.id
                WHERE a.id_master_jabatan IS NULL
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('asn_master') && Schema::hasColumn('asn_master', 'id_master_jabatan')) {
            Schema::table('asn_master', function (Blueprint $table) {
                $table->dropForeign(['id_master_jabatan']);
                $table->dropColumn('id_master_jabatan');
            });
        }

        Schema::dropIfExists('master_jabatan');
    }
};
