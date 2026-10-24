<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Produk;
use App\Models\Promo;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data awal aplikasi Salon Rengganis.
     */
    public function run(): void
    {
        $this->call([
            PelangganSeeder::class,
        ]);

        // Data Karyawan (Manajer, Kasir, Stylist, Therapist)
        $daftarKaryawan = [
            ['Budi Santoso',    'Manajer',   0,  'manajer_budi',  'manajer@rengganis.com'],
            ['Ayu Lestari',     'Kasir',     0,  'kasir_ayu',     'kasir@rengganis.com'],
            ['Sinta Maharani',  'Stylist',   15, 'stylist_sinta', 'sinta@rengganis.com'],
            ['Nadia Putri',     'Stylist',   15, 'stylist_nadia', 'nadia@rengganis.com'],
            ['Larasati Dewi',   'Therapist', 20, 'therapist_laras','laras@rengganis.com'],
            ['Citra Kirana',    'Therapist', 20, 'therapist_citra','citra@rengganis.com'],
        ];

        foreach ($daftarKaryawan as [$nama, $jabatan, $komisi, $username, $email]) {
            Karyawan::updateOrCreate(
                ['email' => $email],
                [
                    'nama_staff'    => $nama,
                    'role_jabatan'  => $jabatan,
                    'persen_komisi' => $komisi,
                    'aktif'         => true,
                    'username'      => $username,
                    'password'      => 'password123',
                ]
            );
        }

        // Data Layanan beserta Kategori & Jabatan Petugas
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

        foreach ($daftarLayanan as [$nama, $kategori, $jabatan, $harga]) {
            Layanan::updateOrCreate(
                ['nama_layanan' => $nama],
                [
                    'kategori'        => $kategori,
                    'jabatan_petugas' => $jabatan,
                    'harga_layanan'   => $harga,
                ]
            );
        }

        // Data Produk Salon
        $daftarProduk = [
            ['Serum Vitamin Rambut Keratin 100ml', 25, 65000],
            ['Masker Rambut Argan Oil 250gr',      18, 95000],
            ['Toner Wajah Rose Water 150ml',       30, 55000],
            ['Hair Tonic Ginseng Penguat Akar',    12, 75000],
        ];

        foreach ($daftarProduk as [$nama, $stok, $harga]) {
            Produk::updateOrCreate(
                ['nama_produk' => $nama],
                [
                    'stok_produk'  => $stok,
                    'harga_produk' => $harga,
                ]
            );
        }

        // Data Promo Awal
        Promo::updateOrCreate(
            ['kode_promo' => 'CANTIK15'],
            [
                'nama_promo'     => 'Diskon Spesial Perawatan 15%',
                'tipe_diskon'    => 'Persentase',
                'nilai_diskon'   => 15,
                'min_transaksi'  => 100000,
                'berlaku_mulai'  => now()->subDays(5)->toDateString(),
                'berlaku_sampai' => now()->addMonths(3)->toDateString(),
                'aktif'          => true,
            ]
        );
    }
}
