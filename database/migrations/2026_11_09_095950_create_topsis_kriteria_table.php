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
        Schema::create('topsis_kriteria', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kriteria', 10)->unique()->index(); // C1, C2, C3, C4
            $table->string('nama_kriteria', 100);
            $table->decimal('bobot', 5, 2)->default(0.25); // e.g. 0.40, 0.30
            $table->enum('atribut', ['cost', 'benefit'])->default('benefit');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('topsis_kriteria');
    }
};
