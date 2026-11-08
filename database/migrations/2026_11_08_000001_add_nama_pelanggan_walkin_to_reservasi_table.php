<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_pelanggan')->nullable()->change();

            if (! Schema::hasColumn('reservasi', 'nama_pelanggan_walkin')) {
                $table->string('nama_pelanggan_walkin', 100)->nullable()->after('id_pelanggan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservasi', function (Blueprint $table) {
            if (Schema::hasColumn('reservasi', 'nama_pelanggan_walkin')) {
                $table->dropColumn('nama_pelanggan_walkin');
            }
        });
    }
};
