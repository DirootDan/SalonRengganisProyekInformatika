import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                
                // Auth
                'resources/css/Login/loginpage.css',
                'resources/css/Login/registerpage.css',
                'resources/css/Login/lupapassword.css',
                'resources/css/Login/resetpassword.css',
                'resources/js/loginpage.js',
                
                // Pelanggan CSS
                'resources/css/pelanggan/dashboard.css',
                'resources/css/pelanggan/layanan.css',
                'resources/css/pelanggan/riwayat.css',
                'resources/css/pelanggan/profil.css',
                'resources/css/pelanggan/booking.css',
                
                // Pelanggan JS
                'resources/js/pelanggan/layout.js',
                'resources/js/pelanggan/beranda.js',
                'resources/js/pelanggan/booking.js',
                'resources/js/pelanggan/riwayat.js',
                'resources/js/pelanggan/profil.js',

                // Manajer CSS
                'resources/css/Manajer/layout.css',
                'resources/css/Manajer/dashboard.css',
                'resources/css/Manajer/karyawan.css',
                'resources/css/Manajer/layanan.css',
                'resources/css/Manajer/produk.css',
                'resources/css/Manajer/promo.css',
                'resources/css/Manajer/laporan.css',
                'resources/css/Manajer/metode.css',
                'resources/css/Manajer/struk.css',
                'resources/css/Manajer/kebijakan.css',

                // Manajer JS
                'resources/js/Manajer/layout.js',
                'resources/js/Manajer/dashboard.js',
                'resources/js/Manajer/karyawan.js',
                'resources/js/Manajer/layanan.js',
                'resources/js/Manajer/produk.js',
                'resources/js/Manajer/promo.js',
                'resources/js/Manajer/laporan.js',
                'resources/js/Manajer/metode.js',
                'resources/js/Manajer/struk.js',
                'resources/js/Manajer/kebijakan.js',

                // Karyawan - Kasir CSS
                'resources/css/Karyawan/Kasir/layout.css',
                'resources/css/Karyawan/Kasir/dashboard.css',
                'resources/css/Karyawan/Kasir/permintaan.css',
                'resources/css/Karyawan/Kasir/antrean.css',
                'resources/css/Karyawan/Kasir/layanan.css',
                'resources/css/Karyawan/Kasir/produk.css',
                'resources/css/Karyawan/Kasir/laporan.css',
                'resources/css/Karyawan/Kasir/metode.css',
                'resources/css/Karyawan/Kasir/struk.css',

                // Karyawan - Kasir JS
                'resources/js/Karyawan/Kasir/layout.js',
                'resources/js/Karyawan/Kasir/dashboard.js',
                'resources/js/Karyawan/Kasir/permintaan.js',
                'resources/js/Karyawan/Kasir/antrean.js',
                'resources/js/Karyawan/Kasir/layanan.js',
                'resources/js/Karyawan/Kasir/produk.js',
                'resources/js/Karyawan/Kasir/laporan.js',
                'resources/js/Karyawan/Kasir/metode.js',
                'resources/js/Karyawan/Kasir/struk.js',

                // Karyawan - Stylist & Therapist
                'resources/css/Karyawan/Stylist/dashboard.css',
                'resources/css/Karyawan/Therapist/dashboard.css',
                'resources/js/Karyawan/Stylist/dashboard.js',
                'resources/js/Karyawan/Therapist/dashboard.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
