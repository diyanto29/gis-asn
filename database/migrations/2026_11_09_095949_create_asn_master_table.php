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
        Schema::create('asn_master', function (Blueprint $table) {
            $table->id();
            $table->string('nip', 30)->unique()->index();
            $table->string('nama', 150)->index();
            $table->string('gelar_depan', 20)->nullable();
            $table->string('gelar_belakang', 20)->nullable();
            $table->string('jabatan', 150);
            $table->string('pangkat_golongan', 20)->index(); // misal: III/a, III/b, IV/a
            $table->integer('masa_kerja_tahun')->default(0)->index();
            $table->date('tmt_kerja_unit')->nullable();
            $table->foreignId('id_unit_saat_ini')->constrained('unit_kerja')->onDelete('cascade');
            $table->decimal('domisili_lat', 10, 8)->nullable()->index();
            $table->decimal('domisili_lng', 11, 8)->nullable()->index();
            $table->string('provinsi', 100)->nullable();
            $table->string('kabupaten_kota', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kelurahan_desa', 100)->nullable();
            $table->string('kode_desa', 20)->nullable()->index();
            $table->text('alamat_detail')->nullable();
            $table->decimal('jarak_ke_kantor_km', 8, 2)->nullable()->index();
            $table->enum('zona_jarak', ['dekat', 'sedang', 'jauh'])->nullable()->index();
            $table->boolean('status_permohonan_mutasi')->default(false)->index();
            $table->text('alasan_mutasi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asn_master');
    }
};
