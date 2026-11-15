import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',

                // Auth
                'resources/css/pages/auth/auth.css',
                'resources/js/loginpage.js',

                // Publik & Error
                'resources/css/pages/publik/beranda.css',
                'resources/css/pages/error.css',
                'resources/js/Publik/beranda.js',

                // Pelanggan CSS
                'resources/css/pages/pelanggan/booking.css',
                'resources/css/pages/pelanggan/akun.css',

                // Pelanggan JS
                'resources/js/Pelanggan/layout.js',
                'resources/js/Pelanggan/beranda.js',
                'resources/js/Pelanggan/booking.js',
                'resources/js/Pelanggan/riwayat.js',
                'resources/js/Pelanggan/profil.js',

                // Staf CSS Modular
                'resources/css/components/struk.css',
                'resources/css/pages/staf/dashboard.css',
                'resources/css/pages/staf/katalog.css',
                'resources/css/pages/staf/laporan.css',
                'resources/css/pages/staf/kasir-pos.css',
                'resources/css/pages/staf/manajer-admin.css',

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
                'resources/js/Manajer/persetujuan.js',

                // Karyawan - Kasir JS
                'resources/js/Karyawan/Kasir/layout.js',
                'resources/js/Karyawan/Kasir/dashboard.js',
                'resources/js/Karyawan/Kasir/permintaan.js',
                'resources/js/Karyawan/Kasir/antrean.js',
                'resources/js/Karyawan/Kasir/layanan.js',
                'resources/js/Karyawan/Kasir/produk.js',
                'resources/js/Karyawan/Kasir/laporan.js',
                'resources/js/Karyawan/Kasir/struk.js',
                'resources/js/Karyawan/Kasir/struk-cetak.js',

                // Komponen Bersama JS
                'resources/js/components/anti-klik-ganda.js',
                'resources/js/components/confirm-modal.js'
            ],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
