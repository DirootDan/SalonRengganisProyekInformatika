import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
           input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/css/Login/loginpage.css',
    'resources/css/Login/registerpage.css',
    'resources/css/Login/lupapassword.css',
    'resources/css/Login/resetpassword.css',
    'resources/js/loginpage.js',
    'resources/css/pelanggan/dashboard.css',
    'resources/css/pelanggan/layanan.css',
    'resources/css/pelanggan/riwayat.css'

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
