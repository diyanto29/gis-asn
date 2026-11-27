<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel pengajuan_ubah_lokasi untuk mencatat setiap usulan perubahan
     * pin titik domisili mandiri oleh PNS beserta unggahan bukti KTP,
     * status verifikasi/approval Admin BKPSDM, dan riwayat audit pergantian pin.
     */
    public function up(): void
    {
        Schema::create('pengajuan_ubah_lokasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_asn')->constrained('asn_master')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Snapshot lokasi lama (sebelum perubahan)
            $table->decimal('lat_lama', 10, 7)->nullable();
            $table->decimal('lng_lama', 10, 7)->nullable();
            $table->text('alamat_lama')->nullable();
            $table->decimal('jarak_lama_km', 8, 2)->nullable();

            // Usulan lokasi baru
            $table->decimal('lat_baru', 10, 7);
            $table->decimal('lng_baru', 10, 7);
            $table->string('provinsi_baru', 100)->nullable();
            $table->string('kabupaten_kota_baru', 100)->nullable();
            $table->string('kecamatan_baru', 100)->nullable();
            $table->string('kelurahan_desa_baru', 100)->nullable();
            $table->text('alamat_detail_baru')->nullable();
            $table->decimal('jarak_baru_km', 8, 2)->nullable();
            $table->string('zona_baru', 20)->nullable();

            // Dokumen bukti KTP (disimpan di storage lokal privat)
            $table->string('ktp_path', 255)->nullable();

            // Status verifikasi & audit trail
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending')->index();
            $table->text('catatan_admin')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index(['id_asn', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_ubah_lokasi');
    }
};
