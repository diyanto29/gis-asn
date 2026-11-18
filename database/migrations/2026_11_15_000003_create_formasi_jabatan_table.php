<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel formasi_jabatan untuk tracing riwayat formasi multi-tahun
     * dan spesifikasi jabatan linier pada unit kerja (ABK Dinamis).
     */
    public function up(): void
    {
        Schema::create('formasi_jabatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_unit_kerja')->constrained('unit_kerja')->onDelete('cascade');
            $table->integer('tahun_anggaran')->index(); // Contoh: 2024, 2025, 2026, 2027
            $table->string('periode', 50)->default('Reguler'); // Contoh: Reguler, Semester 1, Seleksi Terbuka
            $table->string('nama_jabatan', 150)->index(); // Contoh: Analis SDM Aparatur, Pranata Komputer
            $table->string('jenjang_jabatan', 50)->nullable(); // Contoh: Ahli Pertama, Ahli Muda, Pelaksana
            $table->string('kualifikasi_pendidikan', 150)->nullable(); // Contoh: S-1 Komputer / Manajemen / Hukum
            $table->integer('kebutuhan_abk')->default(1);
            $table->integer('bezetting_riil')->default(0);
            $table->integer('kuota_lowong')->default(1);
            $table->enum('status', ['terbuka', 'terisi', 'ditutup'])->default('terbuka')->index();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('formasi_jabatan');
    }
};

