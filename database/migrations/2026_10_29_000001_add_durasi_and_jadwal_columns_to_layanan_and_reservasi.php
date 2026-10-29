<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom durasi, jeda (buffer), dan eksklusivitas petugas pada tabel layanan
        Schema::table('layanan', function (Blueprint $table) {
            if (! Schema::hasColumn('layanan', 'durasi_menit')) {
                $table->unsignedSmallInteger('durasi_menit')->default(60)->after('harga_layanan');
            }
            if (! Schema::hasColumn('layanan', 'jeda_menit')) {
                $table->unsignedSmallInteger('jeda_menit')->default(15)->after('durasi_menit');
            }
            if (! Schema::hasColumn('layanan', 'petugas_eksklusif')) {
                $table->boolean('petugas_eksklusif')->default(true)->after('jeda_menit');
            }
        });

        // 2. Tambah kolom jam_selesai, total_durasi_menit, dan snapshot promo pada tabel reservasi
        Schema::table('reservasi', function (Blueprint $table) {
            if (! Schema::hasColumn('reservasi', 'jam_selesai')) {
                $table->string('jam_selesai', 5)->default('11:00')->after('jam_booking');
            }
            if (! Schema::hasColumn('reservasi', 'total_durasi_menit')) {
                $table->unsignedSmallInteger('total_durasi_menit')->default(60)->after('jam_selesai');
            }
            if (! Schema::hasColumn('reservasi', 'id_promo')) {
                $table->foreignId('id_promo')
                    ->nullable()
                    ->after('pelanggaran_dihitung')
                    ->constrained('promo', 'id_promo')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('reservasi', 'kode_promo')) {
                $table->string('kode_promo', 40)->nullable()->after('id_promo');
            }
            if (! Schema::hasColumn('reservasi', 'tipe_diskon_promo')) {
                $table->string('tipe_diskon_promo', 20)->nullable()->after('kode_promo');
            }
            if (! Schema::hasColumn('reservasi', 'nilai_diskon_promo')) {
                $table->decimal('nilai_diskon_promo', 12, 2)->default(0)->after('tipe_diskon_promo');
            }
            if (! Schema::hasColumn('reservasi', 'nominal_diskon')) {
                $table->decimal('nominal_diskon', 12, 2)->default(0)->after('nilai_diskon_promo');
            }

            $table->index(['tgl_booking', 'status'], 'idx_reservasi_tgl_status');
            $table->index(['id_pelanggan', 'status'], 'idx_reservasi_pelanggan_status');
        });

        // 3. Tambah kolom timeline pengerjaan & snapshot durasi pada detail_reservasi
        Schema::table('detail_reservasi', function (Blueprint $table) {
            if (! Schema::hasColumn('detail_reservasi', 'jam_mulai')) {
                $table->string('jam_mulai', 5)->nullable()->after('id_karyawan');
            }
            if (! Schema::hasColumn('detail_reservasi', 'jam_selesai')) {
                $table->string('jam_selesai', 5)->nullable()->after('jam_mulai');
            }
            if (! Schema::hasColumn('detail_reservasi', 'jam_selesai_blokir')) {
                $table->string('jam_selesai_blokir', 5)->nullable()->after('jam_selesai');
            }
            if (! Schema::hasColumn('detail_reservasi', 'durasi_saat_ini')) {
                $table->unsignedSmallInteger('durasi_saat_ini')->default(60)->after('jam_selesai_blokir');
            }
            if (! Schema::hasColumn('detail_reservasi', 'jeda_saat_ini')) {
                $table->unsignedSmallInteger('jeda_saat_ini')->default(15)->after('durasi_saat_ini');
            }

            $table->index(['id_karyawan', 'jam_mulai', 'jam_selesai_blokir'], 'idx_detail_reservasi_jadwal_petugas');
        });

        Schema::table('transaksi', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_transaksi_created_at');
        });

        // 4. Backfill data durasi layanan lama secara aman
        $daftarLayanan = DB::table('layanan')->get();
        foreach ($daftarLayanan as $item) {
            $nama = strtolower($item->nama_layanan);
            $durasi = 60;
            $jeda = 15;

            if (str_contains($nama, 'smoothing') || str_contains($nama, 'rebonding') || str_contains($nama, 'coloring') || str_contains($nama, 'warna')) {
                $durasi = 180;
                $jeda = 15;
            } elseif (str_contains($nama, 'potong') || str_contains($nama, 'cuci') || str_contains($nama, 'blow')) {
                $durasi = 45;
                $jeda = 15;
            }

            DB::table('layanan')
                ->where('id_layanan', $item->id_layanan)
                ->update([
                    'durasi_menit' => $durasi,
                    'jeda_menit' => $jeda,
                    'petugas_eksklusif' => true,
                ]);
        }

        // 5. Backfill timeline detail_reservasi & reservasi yang sudah ada
        $layananMap = DB::table('layanan')->get()->keyBy('id_layanan');
        $reservasiLama = DB::table('reservasi')->get();

        foreach ($reservasiLama as $res) {
            $jamMulaiRes = $res->jam_booking ?: '10:00';
            $kursorWaktu = Carbon::createFromFormat('H:i', substr($jamMulaiRes, 0, 5));
            $totalDurasi = 0;

            $details = DB::table('detail_reservasi')
                ->where('id_reservasi', $res->id_reservasi)
                ->orderBy('id_layanan')
                ->get();

            foreach ($details as $det) {
                $lay = $layananMap->get($det->id_layanan);
                $durasi = (int) ($lay->durasi_menit ?? 60);
                $jeda = (int) ($lay->jeda_menit ?? 15);

                $mulaiStr = $kursorWaktu->format('H:i');
                $selesai = $kursorWaktu->copy()->addMinutes($durasi);
                $blokir = $selesai->copy()->addMinutes($jeda);

                DB::table('detail_reservasi')
                    ->where('id_reservasi', $res->id_reservasi)
                    ->where('id_layanan', $det->id_layanan)
                    ->update([
                        'jam_mulai' => $mulaiStr,
                        'jam_selesai' => $selesai->format('H:i'),
                        'jam_selesai_blokir' => $blokir->format('H:i'),
                        'durasi_saat_ini' => $durasi,
                        'jeda_saat_ini' => $jeda,
                    ]);

                $kursorWaktu = $selesai;
                $totalDurasi += $durasi;
            }

            DB::table('reservasi')
                ->where('id_reservasi', $res->id_reservasi)
                ->update([
                    'jam_selesai' => $kursorWaktu->format('H:i'),
                    'total_durasi_menit' => max(15, $totalDurasi ?: 60),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('transaksi', function (Blueprint $table) {
            $table->dropIndex('idx_transaksi_created_at');
        });

        Schema::table('detail_reservasi', function (Blueprint $table) {
            $table->dropIndex('idx_detail_reservasi_jadwal_petugas');
            $table->dropColumn([
                'jam_mulai',
                'jam_selesai',
                'jam_selesai_blokir',
                'durasi_saat_ini',
                'jeda_saat_ini',
            ]);
        });

        Schema::table('reservasi', function (Blueprint $table) {
            $table->dropIndex('idx_reservasi_tgl_status');
            $table->dropIndex('idx_reservasi_pelanggan_status');
            $table->dropConstrainedForeignId('id_promo');
            $table->dropColumn([
                'jam_selesai',
                'total_durasi_menit',
                'kode_promo',
                'tipe_diskon_promo',
                'nilai_diskon_promo',
                'nominal_diskon',
            ]);
        });

        Schema::table('layanan', function (Blueprint $table) {
            $table->dropColumn([
                'durasi_menit',
                'jeda_menit',
                'petugas_eksklusif',
            ]);
        });
    }
};
