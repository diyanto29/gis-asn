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
        Schema::create('rekomendasi_mutasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asn')->constrained('asn_master')->onDelete('cascade');
            $table->foreignId('id_unit_asal')->constrained('unit_kerja')->onDelete('cascade');
            $table->foreignId('id_unit_tujuan')->constrained('unit_kerja')->onDelete('cascade');
            $table->decimal('jarak_asal_km', 8, 2);
            $table->decimal('jarak_baru_km', 8, 2);
            $table->decimal('selisih_hemat_jarak_km', 8, 2)->index();
            $table->decimal('nilai_preferensi_v', 8, 6)->index(); // Nilai Vi TOPSIS (0.000000 - 1.000000)
            $table->integer('ranking')->index();
            $table->enum('status_usulan', ['draft', 'diajukan_baperjakat', 'disetujui', 'ditolak'])->default('draft')->index();
            $table->text('catatan_baperjakat')->nullable();
            $table->timestamp('tanggal_kalkulasi')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rekomendasi_mutasi');
    }
};
