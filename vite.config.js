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
                
                // Pelanggan JS (Baru Ditambahkan)
                'resources/js/pelanggan/beranda.js',
                'resources/js/pelanggan/booking.js',
                'resources/js/pelanggan/riwayat.js',
                'resources/js/pelanggan/profil.js'
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