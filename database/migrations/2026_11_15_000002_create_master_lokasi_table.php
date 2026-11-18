<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat tabel master_lokasi (Kecamatan & Pusat Geospasial Kabupaten Indramayu)
     */
    public function up(): void
    {
        Schema::create('master_lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kecamatan', 20)->unique()->index();
            $table->string('nama_kecamatan', 100)->index();
            $table->decimal('pusat_lat', 10, 8);
            $table->decimal('pusat_lng', 11, 8);
            $table->integer('jumlah_desa')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_lokasi');
    }
};

