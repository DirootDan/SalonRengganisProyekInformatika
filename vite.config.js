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

                // Manajer JS
                'resources/js/Manajer/layout.js',
                'resources/js/Manajer/dashboard.js',
                'resources/js/Manajer/karyawan.js',
                'resources/js/Manajer/layanan.js',
                'resources/js/Manajer/produk.js',
                'resources/js/Manajer/promo.js',
                'resources/js/Manajer/laporan.js',
                'resources/js/Manajer/metode.js',
                'resources/js/Manajer/struk.js'
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
