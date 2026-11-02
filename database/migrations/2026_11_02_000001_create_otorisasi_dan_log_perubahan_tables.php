<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_otorisasi', function (Blueprint $table) {
            $table->id('id_otorisasi');
            $table->string('kode_referensi', 32)->unique();

            $table->foreignId('id_kasir')
                ->constrained('karyawan', 'id_karyawan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_manajer')
                ->nullable()
                ->constrained('karyawan', 'id_karyawan')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_reservasi')
                ->nullable()
                ->constrained('reservasi', 'id_reservasi')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_transaksi')
                ->nullable()
                ->constrained('transaksi', 'id_transaksi')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('konteks', 30);
            $table->string('jenis_aksi', 40);
            $table->string('tipe_target', 30)->nullable();
            $table->unsignedBigInteger('id_target')->nullable();
            $table->string('label_target', 150);
            $table->text('alasan_kasir');

            $table->decimal('total_awal', 12, 2)->default(0);
            $table->decimal('total_akhir', 12, 2)->default(0);

            $table->json('snapshot_keranjang')->nullable();
            $table->string('hash_keranjang', 64)->nullable();
            $table->boolean('sedang_dihold')->default(false);

            $table->string('status', 25)->default('Menunggu');
            $table->string('metode_persetujuan', 30)->nullable();
            $table->text('catatan_manajer')->nullable();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->timestamp('digunakan_pada')->nullable();
            $table->timestamps();

            $table->index(['status', 'konteks'], 'idx_otorisasi_status_konteks');
            $table->index(['id_kasir', 'sedang_dihold', 'status'], 'idx_otorisasi_kasir_hold');
            $table->index(['tipe_target', 'id_target', 'status'], 'idx_otorisasi_target_status');
        });

        Schema::create('log_perubahan_otorisasi', function (Blueprint $table) {
            $table->id('id_log');

            $table->foreignId('id_otorisasi')
                ->nullable()
                ->constrained('permintaan_otorisasi', 'id_otorisasi')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_kasir')
                ->constrained('karyawan', 'id_karyawan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_manajer')
                ->nullable()
                ->constrained('karyawan', 'id_karyawan')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_reservasi')
                ->nullable()
                ->constrained('reservasi', 'id_reservasi')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreignId('id_transaksi')
                ->nullable()
                ->constrained('transaksi', 'id_transaksi')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('konteks', 30);
            $table->string('jenis_perubahan', 40);
            $table->string('mekanisme_otorisasi', 35);
            $table->string('tipe_target', 30)->nullable();
            $table->unsignedBigInteger('id_target')->nullable();
            $table->string('label_target', 150);
            $table->text('alasan');

            $table->decimal('nilai_awal', 12, 2)->default(0);
            $table->decimal('nilai_akhir', 12, 2)->default(0);
            $table->json('data_sebelum')->nullable();
            $table->json('data_sesudah')->nullable();
            $table->timestamps();

            $table->index(['konteks', 'created_at'], 'idx_log_otorisasi_konteks_waktu');
            $table->index(['id_kasir', 'created_at'], 'idx_log_otorisasi_kasir_waktu');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_perubahan_otorisasi');
        Schema::dropIfExists('permintaan_otorisasi');
    }
};
