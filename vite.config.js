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
                'resources/css/Login/loginpage.css',
                'resources/css/Login/registerpage.css',
                'resources/css/Login/lupapassword.css',
                'resources/css/Login/resetpassword.css',
                'resources/js/loginpage.js',

                // Publik
                'resources/css/pages/publik/beranda.css',
                'resources/css/pages/error.css',
                'resources/css/Publik/beranda.css',
                'resources/js/Publik/beranda.js',
                'resources/css/errors/halaman-error.css',
                
                // Pelanggan CSS
                'resources/css/pages/pelanggan/booking.css',
                'resources/css/pages/pelanggan/akun.css',
                'resources/css/Pelanggan/dashboard.css',
                'resources/css/Pelanggan/layanan.css',
                'resources/css/Pelanggan/riwayat.css',
                'resources/css/Pelanggan/profil.css',
                'resources/css/Pelanggan/booking.css',
                
                // Pelanggan JS
                'resources/js/Pelanggan/layout.js',
                'resources/js/Pelanggan/beranda.js',
                'resources/js/Pelanggan/booking.js',
                'resources/js/Pelanggan/riwayat.js',
                'resources/js/Pelanggan/profil.js',

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
                'resources/css/Manajer/persetujuan.css',

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
                'resources/css/Karyawan/Kasir/struk-cetak.css',

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

                // Karyawan - Petugas (Stylist & Therapist)
                'resources/css/Karyawan/Petugas/dashboard.css',

                // Komponen & Halaman Staf Modular Baru
                'resources/css/components/struk.css',
                'resources/css/pages/staf/dashboard.css',
                'resources/css/pages/staf/katalog.css',
                'resources/css/pages/staf/laporan.css',
                'resources/css/pages/staf/kasir-pos.css',
                'resources/css/pages/staf/manajer-admin.css',

                // Komponen Bersama
                'resources/css/components/confirm-modal.css',
                'resources/css/components/tombol-whatsapp.css',
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
