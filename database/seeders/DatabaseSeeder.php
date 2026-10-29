<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\PengaturanStruk;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use App\Models\RiwayatPelanggaran;
use App\Models\Transaksi;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data contoh lengkap aplikasi Salon Rengganis untuk keperluan demo seluruh fitur.
     */
    public function run(): void
    {
        $this->call([
            PelangganSeeder::class,
        ]);

        // 1. Pengaturan Kebijakan Pinalti & Struk Default
        PengaturanKebijakan::updateOrCreate(
            ['id' => 1],
            [
                'batas_pelanggaran' => 5,
                'rentang_hari_pelanggaran' => 90,
                'lama_blokir_hari' => 7,
                'lama_blokir_kedua_hari' => 30,
                'blokir_ketiga_permanen' => true,
                'toleransi_bayar_hari' => 1,
                'batas_batal_jam' => 3,
                'kebijakan_aktif' => true,
            ]
        );

        PengaturanStruk::aktif();

        // 2. Metode Pembayaran
        $daftarMetode = [
            ['Tunai (Cash)',          'Tunai',               'Pembayaran langsung di laci kasir', 'Kasir Salon Rengganis', '💵', true],
            ['QRIS Salon Rengganis',  'Dompet Digital / QR', 'NMID: ID102003004005',              'Salon Rengganis Official', '📱', true],
            ['Transfer Bank BCA',     'Transfer Bank',       '8420-9912-34',                      'PT Salon Rengganis Cantika', '🏦', true],
            ['Transfer Bank Mandiri', 'Transfer Bank',       '137-00-9887766-5',                  'PT Salon Rengganis Cantika', '🏦', true],
            ['Debit / Kartu Kredit',  'Mesin EDC',           'Terminal EDC BCA & Mandiri',        'Salon Rengganis Official', '💳', false],
        ];

        foreach ($daftarMetode as [$nama, $kat, $rek, $an, $ikon, $aktif]) {
            MetodePembayaran::updateOrCreate(
                ['nama_metode' => $nama],
                [
                    'kategori' => $kat,
                    'nomor_rekening' => $rek,
                    'atas_nama' => $an,
                    'ikon' => $ikon,
                    'aktif' => $aktif,
                ]
            );
        }

        // 3. Data Karyawan (Manajer, Kasir, Stylist, Therapist)
        $daftarKaryawan = [
            ['Budi Santoso',   'Manajer',   0,  'manajer_budi',    'manajer@rengganis.com'],
            ['Ayu Lestari',    'Kasir',     0,  'kasir_ayu',       'kasir@rengganis.com'],
            ['Sinta Maharani', 'Stylist',   15, 'stylist_sinta',   'sinta@rengganis.com'],
            ['Nadia Putri',    'Stylist',   15, 'stylist_nadia',   'nadia@rengganis.com'],
            ['Larasati Dewi',  'Therapist', 20, 'therapist_laras', 'laras@rengganis.com'],
            ['Citra Kirana',   'Therapist', 20, 'therapist_citra', 'citra@rengganis.com'],
        ];

        $mapKaryawan = [];
        foreach ($daftarKaryawan as [$nama, $jabatan, $komisi, $username, $email]) {
            $mapKaryawan[$email] = Karyawan::updateOrCreate(
                ['email' => $email],
                [
                    'nama_staff' => $nama,
                    'role_jabatan' => $jabatan,
                    'persen_komisi' => $komisi,
                    'aktif' => true,
                    'username' => $username,
                    'password' => 'password123',
                ]
            );
        }

        // 4. Data Layanan per Kategori (Rambut -> Stylist, Perawatan -> Therapist)
        $daftarLayanan = [
            ['Potong & Blow Rambut',        'Rambut',    'Stylist',   60000],
            ['Creambath Tradisional',       'Rambut',    'Stylist',   85000],
            ['Hair Spa Keratin',            'Rambut',    'Stylist',   135000],
            ['Smoothing & Rebonding',       'Rambut',    'Stylist',   280000],
            ['Pewarnaan Rambut (Coloring)', 'Rambut',    'Stylist',   250000],
            ['Facial Wajah Glowing',        'Perawatan', 'Therapist', 120000],
            ['Totok Wajah & Masker Emas',   'Perawatan', 'Therapist', 95000],
            ['Manicure & Pedicure Spa',     'Perawatan', 'Therapist', 110000],
            ['Lulur & Body Massage',        'Perawatan', 'Therapist', 175000],
        ];

        $mapLayanan = [];
        foreach ($daftarLayanan as [$nama, $kategori, $jabatan, $harga]) {
            $mapLayanan[$nama] = Layanan::updateOrCreate(
                ['nama_layanan' => $nama],
                [
                    'kategori' => $kategori,
                    'jabatan_petugas' => $jabatan,
                    'harga_layanan' => $harga,
                ]
            );
        }

        // 5. Data Produk Salon
        $daftarProduk = [
            ['Serum Vitamin Rambut Keratin 100ml', 25, 65000],
            ['Masker Rambut Argan Oil 250gr',      18, 95000],
            ['Toner Wajah Rose Water 150ml',       30, 55000],
            ['Hair Tonic Ginseng Penguat Akar',    12, 75000],
            ['Essential Oil Lavender Relaxing',     8, 85000],
        ];

        $mapProduk = [];
        foreach ($daftarProduk as [$nama, $stok, $harga]) {
            $mapProduk[$nama] = Produk::updateOrCreate(
                ['nama_produk' => $nama],
                [
                    'stok_produk' => $stok,
                    'harga_produk' => $harga,
                ]
            );
        }

        // 6. Data Promo
        Promo::updateOrCreate(
            ['kode_promo' => 'CANTIK15'],
            [
                'nama_promo' => 'Diskon Spesial Perawatan 15%',
                'tipe_diskon' => 'Persentase',
                'nilai_diskon' => 15,
                'min_transaksi' => 100000,
                'berlaku_mulai' => now()->subDays(10)->toDateString(),
                'berlaku_sampai' => now()->addMonths(3)->toDateString(),
                'aktif' => true,
            ]
        );

        Promo::updateOrCreate(
            ['kode_promo' => 'HEMAT25'],
            [
                'nama_promo' => 'Potongan Langsung Rp 25.000',
                'tipe_diskon' => 'Nominal',
                'nilai_diskon' => 25000,
                'min_transaksi' => 150000,
                'berlaku_mulai' => now()->subDays(5)->toDateString(),
                'berlaku_sampai' => now()->addMonths(2)->toDateString(),
                'aktif' => true,
            ]
        );

        // 7. Data Contoh Booking dengan Berbagai Status & Pelanggan Hampir Terblokir (Maya Sari: 4/5 Pelanggaran)
        if (Reservasi::count() === 0) {
            $rina = Pelanggan::where('email', 'rina@gmail.com')->first();
            $dewi = Pelanggan::where('email', 'dewi.lestari@gmail.com')->first();
            $siti = Pelanggan::where('email', 'siti.nurhaliza@yahoo.com')->first();
            $maya = Pelanggan::where('email', 'maya.sari@gmail.com')->first();

            $sinta = $mapKaryawan['sinta@rengganis.com'];
            $nadia = $mapKaryawan['nadia@rengganis.com'];
            $laras = $mapKaryawan['laras@rengganis.com'];
            $citra = $mapKaryawan['citra@rengganis.com'];
            $kasir = $mapKaryawan['kasir@rengganis.com'];

            $potong = $mapLayanan['Potong & Blow Rambut'];
            $creambath = $mapLayanan['Creambath Tradisional'];
            $hairSpa = $mapLayanan['Hair Spa Keratin'];
            $facial = $mapLayanan['Facial Wajah Glowing'];
            $totok = $mapLayanan['Totok Wajah & Masker Emas'];

            // A. Status SELESAI + Transaksi Lunas (Kemarin) -> untuk demo Laporan & Komisi
            if ($rina) {
                $resSelesai = Reservasi::create([
                    'id_pelanggan' => $rina->id_pelanggan,
                    'id_karyawan' => $sinta->id_karyawan,
                    'tgl_booking' => now()->subDay()->toDateString(),
                    'jam_booking' => '10:00',
                    'status' => Reservasi::STATUS_SELESAI,
                ]);

                $resSelesai->layanan()->attach([
                    $hairSpa->id_layanan => [
                        'id_karyawan' => $sinta->id_karyawan,
                        'harga_saat_ini' => 135000,
                        'persen_komisi' => 15,
                        'nominal_komisi' => 20250,
                        'status_layanan' => 'Selesai',
                    ],
                    $facial->id_layanan => [
                        'id_karyawan' => $laras->id_karyawan,
                        'harga_saat_ini' => 120000,
                        'persen_komisi' => 20,
                        'nominal_komisi' => 24000,
                        'status_layanan' => 'Selesai',
                    ],
                ]);

                $trx = Transaksi::create([
                    'id_reservasi' => $resSelesai->id_reservasi,
                    'id_karyawan' => $kasir->id_karyawan,
                    'kode_promo' => 'HEMAT25',
                    'diskon' => 25000,
                    'total_bayar' => 295000,
                    'nominal_uang' => 300000,
                    'kembalian' => 5000,
                    'metode_bayar' => 'Tunai (Cash)',
                    'komisi_stylist' => 44250,
                ]);

                $serum = $mapProduk['Serum Vitamin Rambut Keratin 100ml'] ?? null;
                if ($serum) {
                    $trx->produk()->attach($serum->id_produk, [
                        'jumlah' => 1,
                        'harga_saat_ini' => 65000,
                    ]);
                }

                // B. Status DITERIMA Hari Ini (untuk demo Antrean Hari Ini, Jadwal Stylist/Therapist, dan POS Kasir)
                $resDiterima = Reservasi::create([
                    'id_pelanggan' => $rina->id_pelanggan,
                    'id_karyawan' => $sinta->id_karyawan,
                    'tgl_booking' => now()->toDateString(),
                    'jam_booking' => '14:00',
                    'status' => Reservasi::STATUS_DITERIMA,
                ]);

                $resDiterima->layanan()->attach([
                    $creambath->id_layanan => [
                        'id_karyawan' => $sinta->id_karyawan,
                        'harga_saat_ini' => 85000,
                        'persen_komisi' => 15,
                        'nominal_komisi' => 12750,
                        'status_layanan' => 'Menunggu',
                    ],
                    $totok->id_layanan => [
                        'id_karyawan' => $citra->id_karyawan,
                        'harga_saat_ini' => 95000,
                        'persen_komisi' => 20,
                        'nominal_komisi' => 19000,
                        'status_layanan' => 'Menunggu',
                    ],
                ]);

                NotifikasiPelanggan::kirim(
                    $rina->id_pelanggan,
                    'Permintaan Booking Diterima',
                    "Kabar gembira! Reservasi #{$resDiterima->id_reservasi} untuk hari ini pukul 14:00 WIB telah diterima oleh kasir.",
                    'sukses',
                    $resDiterima->id_reservasi
                );

                // C. Status MENUNGGU Besok (untuk demo Permintaan Booking Kasir)
                $resMenunggu1 = Reservasi::create([
                    'id_pelanggan' => $rina->id_pelanggan,
                    'id_karyawan' => $nadia->id_karyawan,
                    'tgl_booking' => now()->addDay()->toDateString(),
                    'jam_booking' => '10:00',
                    'status' => Reservasi::STATUS_MENUNGGU,
                ]);

                $resMenunggu1->layanan()->attach($potong->id_layanan, [
                    'id_karyawan' => $nadia->id_karyawan,
                    'harga_saat_ini' => 60000,
                    'persen_komisi' => 15,
                    'nominal_komisi' => 9000,
                    'status_layanan' => 'Menunggu',
                ]);
            }

            if ($dewi) {
                // Status MENUNGGU kedua
                $resMenunggu2 = Reservasi::create([
                    'id_pelanggan' => $dewi->id_pelanggan,
                    'id_karyawan' => $laras->id_karyawan,
                    'tgl_booking' => now()->addDay()->toDateString(),
                    'jam_booking' => '13:00',
                    'status' => Reservasi::STATUS_MENUNGGU,
                ]);

                $resMenunggu2->layanan()->attach($facial->id_layanan, [
                    'id_karyawan' => $laras->id_karyawan,
                    'harga_saat_ini' => 120000,
                    'persen_komisi' => 20,
                    'nominal_komisi' => 24000,
                    'status_layanan' => 'Menunggu',
                ]);

                // Status DITOLAK (2 hari lalu)
                $resDitolak = Reservasi::create([
                    'id_pelanggan' => $dewi->id_pelanggan,
                    'id_karyawan' => $sinta->id_karyawan,
                    'tgl_booking' => now()->subDays(2)->toDateString(),
                    'jam_booking' => '16:00',
                    'status' => Reservasi::STATUS_DITOLAK,
                    'alasan_penolakan' => 'Jadwal peralatan steamer rambut sedang dalam perawatan berkala.',
                    'dibatalkan_oleh' => 'Kasir',
                ]);

                $resDitolak->layanan()->attach($hairSpa->id_layanan, [
                    'id_karyawan' => $sinta->id_karyawan,
                    'harga_saat_ini' => 135000,
                    'persen_komisi' => 15,
                    'nominal_komisi' => 20250,
                    'status_layanan' => 'Menunggu',
                ]);

                NotifikasiPelanggan::kirim(
                    $dewi->id_pelanggan,
                    'Permintaan Booking Ditolak',
                    "Mohon maaf, reservasi #{$resDitolak->id_reservasi} belum dapat kami terima. Alasan: Jadwal peralatan steamer rambut sedang dalam perawatan berkala.",
                    'bahaya',
                    $resDitolak->id_reservasi
                );
            }

            if ($siti) {
                // Status DIBATALKAN oleh Pelanggan
                $resBatal = Reservasi::create([
                    'id_pelanggan' => $siti->id_pelanggan,
                    'id_karyawan' => $citra->id_karyawan,
                    'tgl_booking' => now()->subDays(3)->toDateString(),
                    'jam_booking' => '11:00',
                    'status' => Reservasi::STATUS_DIBATALKAN,
                    'alasan_penolakan' => 'Dibatalkan oleh pelanggan sebelum batas waktu.',
                    'dibatalkan_oleh' => 'Pelanggan',
                ]);

                $resBatal->layanan()->attach($totok->id_layanan, [
                    'id_karyawan' => $citra->id_karyawan,
                    'harga_saat_ini' => 95000,
                    'persen_komisi' => 20,
                    'nominal_komisi' => 19000,
                    'status_layanan' => 'Menunggu',
                ]);
            }

            // D. Pelanggan Hampir Terblokir: Maya Sari (4 dari 5 pelanggaran aktif dalam 90 hari terakhir)
            if ($maya) {
                $hariHangus = [20, 15, 10, 5];
                foreach ($hariHangus as $idx => $hariLalu) {
                    $tgl = now()->subDays($hariLalu)->toDateString();
                    $resHangus = Reservasi::create([
                        'id_pelanggan' => $maya->id_pelanggan,
                        'id_karyawan' => $sinta->id_karyawan,
                        'tgl_booking' => $tgl,
                        'jam_booking' => sprintf('%02d:00', 10 + $idx),
                        'status' => Reservasi::STATUS_HANGUS,
                        'pelanggaran_dihitung' => true,
                        'alasan_penolakan' => 'Melewati batas waktu pembayaran H+1 pukul 23:59.',
                    ]);

                    $resHangus->layanan()->attach($potong->id_layanan, [
                        'id_karyawan' => $sinta->id_karyawan,
                        'harga_saat_ini' => 60000,
                        'persen_komisi' => 15,
                        'nominal_komisi' => 9000,
                        'status_layanan' => 'Menunggu',
                    ]);

                    RiwayatPelanggaran::create([
                        'id_pelanggan' => $maya->id_pelanggan,
                        'id_reservasi' => $resHangus->id_reservasi,
                        'jenis' => 'Pelanggaran',
                        'tanggal' => $tgl,
                        'keterangan' => "Reservasi #{$resHangus->id_reservasi} tanggal ".now()->subDays($hariLalu)->format('d/m/Y').' hangus karena tidak diselesaikan pembayarannya.',
                    ]);
                }

                $maya->update([
                    'jumlah_pelanggaran' => 4,
                    'jumlah_kali_blokir' => 0,
                    'diblokir_permanen' => false,
                    'diblokir_sampai' => null,
                ]);

                NotifikasiPelanggan::kirim(
                    $maya->id_pelanggan,
                    'Peringatan Penting: 4 dari 5 Pelanggaran Reservasi',
                    'Anda saat ini memiliki 4 dari 5 pelanggaran dalam 90 hari terakhir. Tersisa 1 kesempatan lagi sebelum akun Anda diblokir sementara selama 7 hari.',
                    'peringatan'
                );
            }
        }
    }
}
