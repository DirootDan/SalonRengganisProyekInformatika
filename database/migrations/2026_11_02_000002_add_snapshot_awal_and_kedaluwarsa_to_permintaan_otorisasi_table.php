<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permintaan_otorisasi', function (Blueprint $table) {
            $table->json('snapshot_awal')->nullable()->after('total_akhir');
            $table->timestamp('kedaluwarsa_pada')->nullable()->after('diputuskan_pada');
        });
    }

    public function down(): void
    {
        Schema::table('permintaan_otorisasi', function (Blueprint $table) {
            $table->dropColumn(['snapshot_awal', 'kedaluwarsa_pada']);
        });
    }
};
