<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengaturan_struk') && ! Schema::hasColumn('pengaturan_struk', 'ukuran_kertas')) {
            Schema::table('pengaturan_struk', function (Blueprint $table) {
                $table->string('ukuran_kertas', 15)->default('80mm')->after('teks_footer');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengaturan_struk') && Schema::hasColumn('pengaturan_struk', 'ukuran_kertas')) {
            Schema::table('pengaturan_struk', function (Blueprint $table) {
                $table->dropColumn('ukuran_kertas');
            });
        }
    }
};

