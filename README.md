# Aplikasi Web Manajemen & Reservasi "Salon Rengganis"

Aplikasi web berbasis **Laravel 12 + Vite + Blade** untuk operasional menyeluruh **Salon Rengganis**, mencakup pemesanan perawatan (*booking*) tanpa pembayaran di muka oleh pelanggan, pengelolaan antrean dan pembayaran (*Point of Sale*) oleh Kasir, jadwal perawatan & laporan komisi untuk Stylist/Therapist, serta panel kendali Manajer lengkap dengan **Sistem Kebijakan Pinalti & Pemblokiran Bertingkat**.

---

## 1. Persyaratan Sistem & Instalasi

### Prasyarat
- **Laragon** (PHP $\ge$ 8.2, MySQL / MariaDB, Apache / Nginx)
- **Composer**
- **Node.js & NPM**

### Langkah Instalasi
1. Pastikan proyek berada di direktori `C:\laragon\www\SalonRengganisProyekInformatika`.
2. Salin atau pastikan berkas `.env` telah terkonfigurasi dengan basis data MySQL Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=salonrengganisdb
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Pasang dependensi PHP dan JavaScript:
   ```powershell
   composer install
   npm install
   ```

---

## 2. Perintah yang Perlu Dijalankan

Jalankan perintah berikut secara berurutan di terminal proyek:

```powershell
# 1. Jalankan seluruh migrasi basis data
php artisan migrate

# 2. Isi data contoh lengkap (akun tiap peran, layanan, produk, promo, booking berbagai status, & pelanggan hampir terblokir)
php artisan db:seed

# 3. Jalankan server kompilasi aset CSS & JS (Vite)
npm run dev

# 4. Jalankan pengujian otomatis (PHPUnit Feature Test)
php artisan test
```

### Perintah Penjadwalan Evaluasi Booking Hangus (Scheduler)
- **Uji Coba Manual di Terminal**:
  ```powershell
  php artisan booking:evaluasi-hangus
  ```
- **Menjalankan Scheduler di Lingkungan Lokal (Laragon)**:
  ```powershell
  php artisan schedule:work
  ```
- **Mengaktifkan Otomatis di Windows Task Scheduler**:
  1. Buka aplikasi **Task Scheduler** di Windows $\rightarrow$ klik **Create Basic Task...**
  2. Isi nama tugas: `Salon Rengganis Booking Scheduler`, pilih pemicu **Daily**, lalu pada pengaturan lanjutan centang **Repeat task every: 1 hour** dengan durasi **Indefinitely**.
  3. Pilih aksi **Start a program**:
     - **Program/script**: `C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe`
     - **Add arguments**: `artisan schedule:run`
     - **Start in**: `C:\laragon\www\SalonRengganisProyekInformatika`

---

## 3. Daftar Akun Contoh (Hasil Seeder)

Seluruh akun di bawah ini menggunakan kata sandi bawaan: **`password123`**

### A. Akun Staf & Manajemen (`guard: karyawan`)
| Peran / Jabatan | Nama Lengkap | Email Login | Username Login | Komisi |
| :--- | :--- | :--- | :--- | :--- |
| **Manajer** | Budi Santoso | `manajer@rengganis.com` | `manajer_budi` | 0% |
| **Kasir** | Ayu Lestari | `kasir@rengganis.com` | `kasir_ayu` | 0% |
| **Stylist** | Sinta Maharani | `sinta@rengganis.com` | `stylist_sinta` | 15% |
| **Stylist** | Nadia Putri | `nadia@rengganis.com` | `stylist_nadia` | 15% |
| **Therapist** | Larasati Dewi | `laras@rengganis.com` | `therapist_laras` | 20% |
| **Therapist** | Citra Kirana | `citra@rengganis.com` | `therapist_citra` | 20% |

### B. Akun Pelanggan (`guard: pelanggan`)
| Status Simulasi | Nama Pelanggan | Email Login | Catatan Demo |
| :--- | :--- | :--- | :--- |
| **Pelanggan Aktif** | Rina Anggraini | `rina@gmail.com` | Memiliki riwayat reservasi `Selesai`, `Diterima` (hari ini), dan `Menunggu` (besok) |
| **Pelanggan Aktif** | Dewi Lestari | `dewi.lestari@gmail.com` | Memiliki reservasi `Menunggu` dan riwayat `Ditolak` beserta alasan |
| **Pelanggan Aktif** | Siti Nurhaliza | `siti.nurhaliza@yahoo.com` | Memiliki riwayat reservasi `Dibatalkan` secara mandiri |
| **Hampir Terblokir (4/5)** | Maya Sari | `maya.sari@gmail.com` | Memiliki **4 dari 5 pelanggaran** dalam 90 hari terakhir (tersisa 1 kesempatan sebelum blokir) |
| **Pelanggan Baru** | Indah Permata | `indah.permata@gmail.com` | Akun bersih tanpa riwayat |

---

## 4. Panduan Singkat Penggunaan Per Peran

### 1. Pelanggan (`/pelanggan/*`)
- **Navigasi Atas**: Menu utama terdiri atas **Beranda**, **Layanan**, **Booking**, dan **Riwayat**. Di sebelah kanan terdapat ikon **Lonceng Notifikasi (🔔)** dan dropdown **Halo, [Nama] ▾** yang berisi **Profil Saya**, **Ganti Kata Sandi**, dan **Keluar**.
- **Alur Booking**: Pilih tanggal & jam operasional $\rightarrow$ centang layanan yang diinginkan $\rightarrow$ sistem otomatis memuat daftar **Stylist** (untuk layanan kategori *Rambut*) atau **Therapist** (untuk kategori *Perawatan*) yang tersedia dan tidak bentrok pada jam tersebut.
- **Pembatalan Gratis**: Pelanggan dapat membatalkan booking berstatus `Menunggu` atau `Diterima` melalui menu **Riwayat** tanpa pinalti selama masih minimal **3 jam** sebelum jadwal kedatangan.

### 2. Manajer (`/manajer/*`)
- **Manajemen Master Data (CRUD)**: Mengelola data **Karyawan** (beserta persentase komisi & status aktif), **Layanan** (beserta kategori `Rambut`/`Perawatan`), **Produk** (beserta stok), dan **Promo** (`Persentase`/`Nominal`).
- **Laporan & Ekspor CSV**: Melihat rekap pendapatan, layanan terlaris, kinerja komisi karyawan dengan filter rentang tanggal, serta mengekspor laporan ke berkas CSV.
- **Pengaturan Struk & Metode Pembayaran**: Mengatur identitas salon pada struk thermal (dengan pratinjau langsung) serta mengaktifkan/menonaktifkan metode pembayaran.
- **Kebijakan Booking & Buka Blokir Manual**: Mengatur rentang kumulatif pelanggaran (bawaan **90 hari**), batas pelanggaran (**5 kali**), durasi **Blokir ke-1 (7 hari)**, **Blokir ke-2 (30 hari)**, **Blokir ke-3 (tetap diblokir sampai dibuka Manajer)**, serta membuka blokir pelanggan lebih awal dengan alasan wajib.

### 3. Kasir (`/kasir/*`)
- **Permintaan Booking**: Melihat daftar permintaan reservasi per tab status dengan paginasi, memantau indikator pelanggaran pelanggan, serta **Menerima** atau **Menolak** (wajib mengisi alasan penolakan yang otomatis dikirim ke notifikasi pelanggan).
- **Antrean Hari Ini**: Memantau daftar antrean aktif yang sedang berjalan hari ini.
- **Dashboard Transaksi (POS)**: Memilih reservasi aktif atau transaksi langsung (*walk-in*), menambahkan produk, menerapkan kode promo (`CANTIK15` / `HEMAT25`), memilih metode pembayaran aktif, memproses pelunasan, dan mencetak struk.

### 4. Stylist (`/stylist/*`) & Therapist (`/therapist/*`)
- **Jadwal Reservasi Pribadi**: Melihat daftar perawatan yang ditugaskan khusus kepada dirinya (filter Harian, Mingguan, atau Semua) dan menekan tombol **Tandai Selesai** setelah perawatan selesai dikerjakan.
- **Laporan Komisi Saya**: Memantau rincian komisi pribadi berdasarkan *snapshot* harga dan persentase komisi dari layanan yang berstatus `Selesai` dan telah dibayar lunas di kasir.
