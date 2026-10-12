<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reservasi', function (Blueprint $table) {
            $table->id('id_Reservasi');
            $table->unsignedBigInteger('id_Pelanggan')->nullable();
            $table->unsignedBigInteger('id_Karyawan')->nullable();
            $table->dateTime('tgl_Booking')->nullable();
            $table->timestamps();

            // Foreign Keys
            $table->foreign('id_Pelanggan')->references('id_pelanggan')->on('pelanggan')
                  ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('id_Karyawan')->references('id_Karyawan')->on('karyawan')
                  ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservasi');
    }
};
