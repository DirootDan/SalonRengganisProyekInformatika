<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {Schema::create('pelanggan', function (Blueprint $table) {
            $table->id('id_pelanggan'); // Otomatis menjadi unsignedBigInteger & Primary Key
            $table->string('nama_cust', 50);
            $table->string('no_telp', 15)->nullable();
            $table->string('email', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pelanggan');
    }
};
