<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('id_Transaksi');
            $table->unsignedBigInteger('id_Reservasi')->nullable();
            $table->unsignedBigInteger('id_Karyawan')->nullable();
            $table->decimal('total_Bayar', 10, 2)->nullable();
            $table->string('metode_Bayar', 50)->nullable();
            $table->decimal('Komisi_Stylist', 10, 2)->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_Reservasi')->references('id_Reservasi')->on('reservasi')
                  ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_Karyawan')->references('id_Karyawan')->on('karyawan')
                  ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaksi');
    }
};
