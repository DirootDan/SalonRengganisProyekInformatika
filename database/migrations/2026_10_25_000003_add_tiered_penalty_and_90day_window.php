<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengaturan_kebijakan')) {
            Schema::table('pengaturan_kebijakan', function (Blueprint $table) {
                if (! Schema::hasColumn('pengaturan_kebijakan', 'rentang_hari_pelanggaran')) {
                    $table->unsignedInteger('rentang_hari_pelanggaran')->default(90)->after('batas_pelanggaran');
                }
                if (! Schema::hasColumn('pengaturan_kebijakan', 'lama_blokir_kedua_hari')) {
                    $table->unsignedInteger('lama_blokir_kedua_hari')->default(30)->after('lama_blokir_hari');
                }
                if (! Schema::hasColumn('pengaturan_kebijakan', 'blokir_ketiga_permanen')) {
                    $table->boolean('blokir_ketiga_permanen')->default(true)->after('lama_blokir_kedua_hari');
                }
            });

            DB::table('pengaturan_kebijakan')->update([
                'rentang_hari_pelanggaran' => 90,
                'batas_pelanggaran' => 5,
                'lama_blokir_hari' => 7,
                'lama_blokir_kedua_hari' => 30,
                'blokir_ketiga_permanen' => true,
                'toleransi_bayar_hari' => 1,
                'batas_batal_jam' => 3,
            ]);
        }

        if (Schema::hasTable('pelanggan')) {
            Schema::table('pelanggan', function (Blueprint $table) {
                if (! Schema::hasColumn('pelanggan', 'jumlah_kali_blokir')) {
                    $table->unsignedInteger('jumlah_kali_blokir')->default(0)->after('jumlah_pelanggaran');
                }
                if (! Schema::hasColumn('pelanggan', 'diblokir_permanen')) {
                    $table->boolean('diblokir_permanen')->default(false)->after('jumlah_kali_blokir');
                }
                if (! Schema::hasColumn('pelanggan', 'reset_pelanggaran_pada')) {
                    $table->timestamp('reset_pelanggaran_pada')->nullable()->after('alasan_blokir');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengaturan_kebijakan')) {
            Schema::table('pengaturan_kebijakan', function (Blueprint $table) {
                $cols = array_filter(
                    ['rentang_hari_pelanggaran', 'lama_blokir_kedua_hari', 'blokir_ketiga_permanen'],
                    fn ($c) => Schema::hasColumn('pengaturan_kebijakan', $c)
                );
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('pelanggan')) {
            Schema::table('pelanggan', function (Blueprint $table) {
                $cols = array_filter(
                    ['jumlah_kali_blokir', 'diblokir_permanen', 'reset_pelanggaran_pada'],
                    fn ($c) => Schema::hasColumn('pelanggan', $c)
                );
                if ($cols) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
