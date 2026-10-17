import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
           input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/css/loginpage.css',
    'resources/css/registerpage.css',
    'resources/css/lupapassword.css',
    'resources/css/resetpassword.css',
    'resources/js/loginpage.js',
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
