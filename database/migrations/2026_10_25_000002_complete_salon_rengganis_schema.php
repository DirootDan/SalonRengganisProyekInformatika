<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Kolom tambahan pada pelanggan (pinalti & blokir)
        Schema::table('pelanggan', function (Blueprint $table) {
            if (! Schema::hasColumn('pelanggan', 'jumlah_pelanggaran')) {
                $table->unsignedInteger('jumlah_pelanggaran')->default(0)->after('password');
            }
            if (! Schema::hasColumn('pelanggan', 'diblokir_sampai')) {
                $table->timestamp('diblokir_sampai')->nullable()->after('jumlah_pelanggaran');
            }
            if (! Schema::hasColumn('pelanggan', 'alasan_blokir')) {
                $table->string('alasan_blokir')->nullable()->after('diblokir_sampai');
            }
        });

        // 2. Kolom tambahan pada karyawan (persentase komisi, status aktif, softDeletes)
        Schema::table('karyawan', function (Blueprint $table) {
            if (! Schema::hasColumn('karyawan', 'persen_komisi')) {
                $table->decimal('persen_komisi', 5, 2)->default(15.00)->after('role_jabatan');
            }
            if (! Schema::hasColumn('karyawan', 'aktif')) {
                $table->boolean('aktif')->default(true)->after('persen_komisi');
            }
            if (! Schema::hasColumn('karyawan', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 3. Kolom tambahan pada layanan (kategori & softDeletes)
        Schema::table('layanan', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan', 'kategori')) {
                $table->string('kategori', 50)->default('Rambut')->after('nama_layanan');
            }
            if (! Schema::hasColumn('layanan', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 4. Kolom softDeletes pada produk
        Schema::table('produk', function (Blueprint $table) {
            if (! Schema::hasColumn('produk', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // 5. Kolom tambahan pada reservasi (jam_booking, alasan_penolakan, waktu_batal, pelanggaran_dihitung)
        Schema::table('reservasi', function (Blueprint $table) {
            if (! Schema::hasColumn('reservasi', 'jam_booking')) {
                $table->string('jam_booking', 5)->default('10:00')->after('tgl_booking');
            }
            if (! Schema::hasColumn('reservasi', 'alasan_penolakan')) {
                $table->string('alasan_penolakan')->nullable()->after('status');
            }
            if (! Schema::hasColumn('reservasi', 'dibatalkan_oleh')) {
                $table->string('dibatalkan_oleh', 30)->nullable()->after('alasan_penolakan');
            }
            if (! Schema::hasColumn('reservasi', 'pelanggaran_dihitung')) {
                $table->boolean('pelanggaran_dihitung')->default(false)->after('dibatalkan_oleh');
            }
        });

        // 6. Kolom snapshot pada detail_reservasi (harga_saat_ini, persen_komisi_saat_ini, nominal_komisi, status_layanan)
        Schema::table('detail_reservasi', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_reservasi', 'harga_saat_ini')) {
                $table->decimal('harga_saat_ini', 12, 2)->default(0)->after('id_karyawan');
            }
            if (! Schema::hasColumn('detail_reservasi', 'persen_komisi')) {
                $table->decimal('persen_komisi', 5, 2)->default(15.00)->after('harga_saat_ini');
            }
            if (! Schema::hasColumn('detail_reservasi', 'nominal_komisi')) {
                $table->decimal('nominal_komisi', 12, 2)->default(0)->after('persen_komisi');
            }
            if (! Schema::hasColumn('detail_reservasi', 'status_layanan')) {
                $table->string('status_layanan', 20)->default('Menunggu')->after('nominal_komisi');
            }
        });

        // 7. Kolom tambahan pada transaksi (diskon & kode_promo) & detail_transaksi (harga_saat_ini)
        Schema::table('transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksi', 'kode_promo')) {
                $table->string('kode_promo', 50)->nullable()->after('id_karyawan');
            }
            if (! Schema::hasColumn('transaksi', 'diskon')) {
                $table->decimal('diskon', 12, 2)->default(0)->after('kode_promo');
            }
        });

        Schema::table('detail_transaksi', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_transaksi', 'harga_saat_ini')) {
                $table->decimal('harga_saat_ini', 12, 2)->default(0)->after('jumlah');
            }
        });

        // 8. Tabel promo
        if (! Schema::hasTable('promo')) {
            Schema::create('promo', function (Blueprint $table) {
                $table->id('id_promo');
                $table->string('kode_promo', 40)->unique();
                $table->string('nama_promo');
                $table->string('tipe_diskon', 20)->default('Persentase'); // Persentase / Nominal
                $table->decimal('nilai_diskon', 12, 2)->default(0);
                $table->decimal('min_transaksi', 12, 2)->default(0);
                $table->date('berlaku_mulai')->nullable();
                $table->date('berlaku_sampai');
                $table->boolean('aktif')->default(true);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        // 9. Tabel metode_pembayaran
        if (! Schema::hasTable('metode_pembayaran')) {
            Schema::create('metode_pembayaran', function (Blueprint $table) {
                $table->id('id_metode');
                $table->string('nama_metode');
                $table->string('kategori', 50)->default('Tunai');
                $table->string('nomor_rekening')->nullable();
                $table->string('atas_nama')->nullable();
                $table->string('ikon', 16)->default('💳');
                $table->boolean('aktif')->default(true);
                $table->timestamps();
            });
        }

        // 10. Tabel pengaturan_struk
        if (! Schema::hasTable('pengaturan_struk')) {
            Schema::create('pengaturan_struk', function (Blueprint $table) {
                $table->id();
                $table->string('nama_salon')->default('SALON RENGGANIS');
                $table->string('slogan')->default('Pusat Perawatan Kecantikan & Rambut');
                $table->string('alamat')->default('Jl. Paingan No. 88, Maguwoharjo, Sleman');
                $table->string('telepon')->default('0812-3456-7890');
                $table->string('instagram')->default('@salonrengganis.id');
                $table->string('teks_header')->nullable();
                $table->text('teks_footer')->nullable();
                $table->boolean('tampil_logo')->default(true);
                $table->boolean('tampil_petugas')->default(true);
                $table->boolean('tampil_kasir')->default(true);
                $table->boolean('tampil_sosmed')->default(true);
                $table->timestamps();
            });
        }

        // 11. Tabel pengaturan_kebijakan (Parameter Pinalti yang dapat diatur Manajer)
        if (! Schema::hasTable('pengaturan_kebijakan')) {
            Schema::create('pengaturan_kebijakan', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('batas_pelanggaran')->default(5);
                $table->unsignedInteger('lama_blokir_hari')->default(7);
                $table->unsignedInteger('toleransi_bayar_hari')->default(1);
                $table->unsignedInteger('batas_batal_jam')->default(3);
                $table->boolean('kebijakan_aktif')->default(true);
                $table->timestamps();
            });
        }

        // 12. Tabel riwayat_pelanggaran
        if (! Schema::hasTable('riwayat_pelanggaran')) {
            Schema::create('riwayat_pelanggaran', function (Blueprint $table) {
                $table->id('id_pelanggaran');
                $table->foreignId('id_pelanggan')
                    ->constrained('pelanggan', 'id_pelanggan')
                    ->cascadeOnDelete();
                $table->foreignId('id_reservasi')
                    ->nullable()
                    ->constrained('reservasi', 'id_reservasi')
                    ->nullOnDelete();
                $table->string('jenis', 40)->default('Pelanggaran'); // Pelanggaran / Buka Blokir / Reset Otomatis
                $table->date('tanggal');
                $table->string('keterangan');
                $table->timestamps();
            });
        }

        // 13. Tabel notifikasi_pelanggan
        if (! Schema::hasTable('notifikasi_pelanggan')) {
            Schema::create('notifikasi_pelanggan', function (Blueprint $table) {
                $table->id('id_notifikasi');
                $table->foreignId('id_pelanggan')
                    ->constrained('pelanggan', 'id_pelanggan')
                    ->cascadeOnDelete();
                $table->foreignId('id_reservasi')
                    ->nullable()
                    ->constrained('reservasi', 'id_reservasi')
                    ->nullOnDelete();
                $table->string('judul');
                $table->text('pesan');
                $table->string('tipe', 30)->default('info'); // sukses, peringatan, bahaya, info
                $table->boolean('dibaca')->default(false);
                $table->timestamps();
            });
        }

        // Isi data awal kategori layanan, kebijakan, struk, metode pembayaran, produk, dan promo
        DB::table('layanan')
            ->whereIn('nama_layanan', ['Facial', 'Manicure & Pedicure'])
            ->update(['kategori' => 'Perawatan Wajah & Tubuh', 'jabatan_petugas' => 'Therapist']);

        DB::table('layanan')
            ->whereNotIn('nama_layanan', ['Facial', 'Manicure & Pedicure'])
            ->update(['kategori' => 'Perawatan Rambut', 'jabatan_petugas' => 'Stylist']);

        // Normalisasi status reservasi lama ('pending' -> 'Menunggu')
        DB::table('reservasi')->where('status', 'pending')->update(['status' => 'Menunggu']);

        if (DB::table('pengaturan_kebijakan')->count() === 0) {
            DB::table('pengaturan_kebijakan')->insert([
                'batas_pelanggaran' => 5,
                'lama_blokir_hari' => 7,
                'toleransi_bayar_hari' => 1,
                'batas_batal_jam' => 3,
                'kebijakan_aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('pengaturan_struk')->count() === 0) {
            DB::table('pengaturan_struk')->insert([
                'nama_salon' => 'SALON RENGGANIS',
                'slogan' => 'Pusat Perawatan Kecantikan & Rambut',
                'alamat' => 'Jl. Paingan No. 88, Maguwoharjo, Sleman',
                'telepon' => '0812-3456-7890',
                'instagram' => '@salonrengganis.id',
                'teks_header' => 'Selamat Datang di Salon Rengganis',
                'teks_footer' => 'Terima kasih atas kunjungan Anda! Simpan struk ini sebagai bukti pembayaran yang sah.',
                'tampil_logo' => true,
                'tampil_petugas' => true,
                'tampil_kasir' => true,
                'tampil_sosmed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('metode_pembayaran')->count() === 0) {
            DB::table('metode_pembayaran')->insert([
                ['nama_metode' => 'Tunai', 'kategori' => 'Tunai', 'nomor_rekening' => 'Pembayaran langsung di kasir', 'atas_nama' => 'Kasir Salon Rengganis', 'ikon' => '💵', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nama_metode' => 'QRIS Semua Pembayaran', 'kategori' => 'Dompet Digital / QRIS', 'nomor_rekening' => 'NMID: ID102003004005', 'atas_nama' => 'Salon Rengganis', 'ikon' => '📱', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nama_metode' => 'Transfer Bank BCA', 'kategori' => 'Transfer Bank', 'nomor_rekening' => '8420-9912-34', 'atas_nama' => 'PT Rengganis Cantika', 'ikon' => '🏦', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
                ['nama_metode' => 'Transfer Bank Mandiri', 'kategori' => 'Transfer Bank', 'nomor_rekening' => '137-00-9821345-6', 'atas_nama' => 'PT Rengganis Cantika', 'ikon' => '🏦', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (DB::table('produk')->count() === 0) {
            DB::table('produk')->insert([
                ['nama_produk' => 'Vitamin Rambut Keratin 100ml', 'stok_produk' => 24, 'harga_produk' => 65000, 'created_at' => now(), 'updated_at' => now()],
                ['nama_produk' => 'Masker Rambut Argan Oil 250g', 'stok_produk' => 15, 'harga_produk' => 95000, 'created_at' => now(), 'updated_at' => now()],
                ['nama_produk' => 'Serum Wajah Pencerah 30ml', 'stok_produk' => 8, 'harga_produk' => 120000, 'created_at' => now(), 'updated_at' => now()],
                ['nama_produk' => 'Sampo Anti Ketombe Salon 500ml', 'stok_produk' => 4, 'harga_produk' => 85000, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        if (DB::table('promo')->count() === 0) {
            DB::table('promo')->insert([
                ['kode_promo' => 'CANTIK20', 'nama_promo' => 'Diskon Perawatan Akhir Pekan', 'tipe_diskon' => 'Persentase', 'nilai_diskon' => 20, 'min_transaksi' => 150000, 'berlaku_mulai' => now()->subDays(10)->toDateString(), 'berlaku_sampai' => now()->addMonths(3)->toDateString(), 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
                ['kode_promo' => 'HEMAT25', 'nama_promo' => 'Potongan Langsung Rp 25.000', 'tipe_diskon' => 'Nominal', 'nilai_diskon' => 25000, 'min_transaksi' => 100000, 'berlaku_mulai' => now()->subDays(5)->toDateString(), 'berlaku_sampai' => now()->addMonths(2)->toDateString(), 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_pelanggan');
        Schema::dropIfExists('riwayat_pelanggaran');
        Schema::dropIfExists('pengaturan_kebijakan');
        Schema::dropIfExists('pengaturan_struk');
        Schema::dropIfExists('metode_pembayaran');
        Schema::dropIfExists('promo');
    }
};
