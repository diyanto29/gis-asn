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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip', 25)->nullable()->unique()->after('name');
            $table->string('role', 20)->default('asn')->after('password'); // 'admin' atau 'asn'
            $table->unsignedBigInteger('asn_id')->nullable()->after('role');
            $table->index(['nip', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nip', 'role']);
            $table->dropColumn(['nip', 'role', 'asn_id']);
        });
    }
};

