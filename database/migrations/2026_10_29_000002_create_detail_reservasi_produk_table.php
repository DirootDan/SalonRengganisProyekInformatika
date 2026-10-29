<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('detail_reservasi_produk')) {
            Schema::create('detail_reservasi_produk', function (Blueprint $table) {
                $table->foreignId('id_reservasi')
                    ->constrained('reservasi', 'id_reservasi')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreignId('id_produk')
                    ->constrained('produk', 'id_produk')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
                $table->unsignedInteger('jumlah')->default(1);
                $table->decimal('harga_saat_ini', 12, 2)->default(0);
                $table->boolean('stok_dipotong')->default(false);
                $table->timestamps();

                $table->primary(['id_reservasi', 'id_produk']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_reservasi_produk');
    }
};
