<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_Transaksi');
            $table->unsignedBigInteger('id_Produk');
            $table->integer('jumlah')->default(1);
            $table->timestamps();

            // Composite Primary Key
            $table->primary(['id_Transaksi', 'id_Produk']);

            // Foreign Keys
            $table->foreign('id_Transaksi')->references('id_Transaksi')->on('transaksi')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_Produk')->references('id_Produk')->on('produk')
                  ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_transaksi');
    }
};