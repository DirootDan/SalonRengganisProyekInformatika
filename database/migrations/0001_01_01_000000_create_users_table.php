<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id('id_pelanggan');
            $table->string('nama_cust');
            $table->string('no_telp', 20);
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('karyawan', function (Blueprint $table) {
            $table->id('id_karyawan');
            $table->string('nama_staff');
            $table->string('role_jabatan');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('produk', function (Blueprint $table) {
            $table->id('id_produk');
            $table->string('nama_produk');
            $table->integer('stok_produk')->default(0);
            $table->decimal('harga_produk', 12, 2);
            $table->timestamps();
        });

        Schema::create('layanan', function (Blueprint $table) {
            $table->id('id_layanan');
            $table->string('nama_layanan');
            $table->decimal('harga_layanan', 12, 2);
            $table->timestamps();
        });

        Schema::create('reservasi', function (Blueprint $table) {
            $table->id('id_reservasi');
            $table->foreignId('id_pelanggan')
                ->constrained('pelanggan', 'id_pelanggan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('id_karyawan')
                ->nullable()
                ->constrained('karyawan', 'id_karyawan')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->date('tgl_booking');
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('detail_reservasi', function (Blueprint $table) {
            $table->foreignId('id_reservasi')
                ->constrained('reservasi', 'id_reservasi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_layanan')
                ->constrained('layanan', 'id_layanan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->primary(['id_reservasi', 'id_layanan']);
        });

        Schema::create('transaksi', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->foreignId('id_reservasi')
                ->unique()
                ->constrained('reservasi', 'id_reservasi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('id_karyawan')
                ->constrained('karyawan', 'id_karyawan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->decimal('total_bayar', 12, 2)->default(0);
            $table->decimal('nominal_uang', 12, 2)->default(0);
            $table->decimal('kembalian', 12, 2)->default(0);
            $table->string('metode_bayar');
            $table->decimal('komisi_stylist', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('detail_transaksi', function (Blueprint $table) {
            $table->foreignId('id_transaksi')
                ->constrained('transaksi', 'id_transaksi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('id_produk')
                ->constrained('produk', 'id_produk')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->integer('jumlah')->default(1);
            $table->primary(['id_transaksi', 'id_produk']);
        });
    }

    public function down(): void
    {
        // Urutan dibalik: tabel anak dihapus lebih dulu
        Schema::dropIfExists('detail_transaksi');
        Schema::dropIfExists('transaksi');
        Schema::dropIfExists('detail_reservasi');
        Schema::dropIfExists('reservasi');
        Schema::dropIfExists('layanan');
        Schema::dropIfExists('produk');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('karyawan');
        Schema::dropIfExists('pelanggan');
    }
};
