<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('detail_reservasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_Reservasi');
            $table->unsignedBigInteger('id_Layanan');
            $table->timestamps();

            // Composite Primary Key
            $table->primary(['id_Reservasi', 'id_Layanan']);

            // Foreign Keys
            $table->foreign('id_Reservasi')->references('id_Reservasi')->on('reservasi')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('id_Layanan')->references('id_Layanan')->on('layanan')
                  ->onDelete('restrict')->onUpdate('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('detail_reservasi');
    }
};
