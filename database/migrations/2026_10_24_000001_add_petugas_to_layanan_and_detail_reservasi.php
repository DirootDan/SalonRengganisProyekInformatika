<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Setiap layanan menentukan jabatan petugas yang menanganinya (Stylist / Therapist)
        Schema::table('layanan', function (Blueprint $table) {
            $table->string('jabatan_petugas', 20)->default('Stylist')->after('harga_layanan');
        });

        // Setiap layanan dalam satu reservasi bisa ditangani petugas yang berbeda
        Schema::table('detail_reservasi', function (Blueprint $table) {
            $table->foreignId('id_karyawan')
                ->nullable()
                ->after('id_layanan')
                ->constrained('karyawan', 'id_karyawan')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });

        // Sesuaikan data layanan yang sudah ada
        DB::table('layanan')
            ->whereIn('nama_layanan', ['Facial', 'Manicure & Pedicure'])
            ->update(['jabatan_petugas' => 'Therapist']);
    }

    public function down(): void
    {
        Schema::table('detail_reservasi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_karyawan');
        });

        Schema::table('layanan', function (Blueprint $table) {
            $table->dropColumn('jabatan_petugas');
        });
    }
};
