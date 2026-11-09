# LOG BOOK - IMPLEMENTASI SISTEM (DATA FAKTUAL RIWAYAT GITHUB)

**Judul Topik** : Implementasi Sistem Reservasi Online, Point of Sale (POS) Kasir, Otorisasi Bertingkat, serta Perbaikan Bug Struk & Walk-In (Prinsip SOLID & KISS) pada Aplikasi Web "Salon Rengganis"  
**Tanggal** : 26 Oktober – 9 November 2026  

---

## Statistik Kontribusi Riil di GitHub (`git shortlog -sne --all`)

| No | Nama Anggota | NIM | Akun GitHub / Email Commit | Jumlah Commit Total | Commit Periode 25 Okt – 9 Nov 2026 |
| :-: | :--- | :--- | :--- | :-: | :-: |
| 1 | **Dimas Rizky Andyanto** | 245314004 | `Dimas Rizky` / `DirootDan` (`dimasrzky251204@gmail.com`) | **52 commit** | **27 commit** (Arsitektur Inti, Backend, Frontend Vanilla CSS, Testing, Refactor SOLID) |
| 2 | **Antonio Eka Wellis Davidson** | 245314027 | `Antonio Eka` (`antonioekawellisdavidson1410@gmail.com`) | **9 commit** | **6 commit** (Foto Profil, Background Login, Fitur Awal Cetak Struk Kasir) |
| 3 | **Kalistus Nadi Sutrisno** | 245314022 | `Kanadis` (`geludngajak39@gmail.com`) | **4 commit** | **4 commit** (Ukuran Kertas Struk, Tombol Selesai Stylist/Therapist, Minor Layout Pelanggan) |
| 4 | **Juan Bastian Mambraku** | 245314030 | `JuanBastian` (`mamrakujuan@gmai.com`) | **3 commit** | **1 commit** (Validasi Anti-Duplikasi Nama Produk; 2 commit lainnya di awal Oktober: First Commit & Migrasi Dasar) |
| 5 | **Abraham Elbar Fernandes** | 245314017 | *(Belum ada commit di GitHub)* | **0 commit** | **0 commit** (Seluruh modul Kasir/POS yang menjadi bagiannya diambil alih & diselesaikan oleh Dimas Rizky) |

---

## Progress Kelompok :

Hal-hal yang nyata dikerjakan dalam periode ini berdasarkan riwayat commit GitHub dan pengerjaan bersama:

1. **Pembangunan Modul Kasir (POS), Otorisasi Bertingkat Manajer, & Refactor SOLID (`Dimas Rizky`)**:
   - Memecah `KasirController` monolitik menjadi *resource controllers* terpisah sesuai prinsip *Single Responsibility Principle (SRP)* (`PosController`, `PermintaanBookingController`, `AntreanController`, `LayananKasirController`, `ProdukKasirController`, `LaporanKasirController`, `MetodePembayaranKasirController`, `StrukController`, `OtorisasiKasirController`) — commit `133a517`, `e31d7fa`.
   - Membangun sistem **Otorisasi POS & Katalog** lengkap dengan *Service Layer* (`OtorisasiPosService`, `PembayaranKasirService`, `KalkulatorKeranjangPosService`): fitur **Hold Transaksi** otomatis jika diskon manual ≥ Rp 150.000, **Direct Override Manajer** di kasir, **Akses Katalog 1x Pakai** (kedaluwarsa 1 jam / saat logout), halaman Persetujuan Manajer, dan tabel `log_audit_perubahan` permanen — commit `2b324ed`, `dc4ef16`, `f90ae65`, `2605dc5`.

2. **Perbaikan Bug Transaksi Walk-In Tanpa Reservasi di Kasir POS (`Dimas Rizky`)**:
   - Mengatasi bug di mana transaksi pelanggan *Walk-In* sebelumnya otomatis memakai nama pelanggan pertama di database (`Pelanggan::first()`).
   - Membuat migrasi `add_nama_pelanggan_walkin_to_reservasi_table` (mengubah `id_pelanggan` menjadi *nullable* dan menambah kolom `nama_pelanggan_walkin`), mewajibkan input nama pelanggan saat *Walk-In* (`required_without:id_reservasi`), dan membuat method `Reservasi::apakahWalkIn()` serta `Reservasi::namaPelangganTampil()` agar struk cetak, laporan kasir/manajer, ekspor CSV, dan komisi petugas menampilkan nama pelanggan *Walk-In* secara akurat — commit `e92bccb`.

3. **Pembuatan & Perbaikan Fitur Cetak Struk Nota Thermal 58mm / 76mm / 80mm (`Kalistus`, `Antonio`, `Dimas Rizky`)**:
   - Penambahan kolom `ukuran_kertas` pada tabel `pengaturan_struk` dan fitur pratinjau kertas struk (`d16005b`, `4326890` oleh Kalistus; disempurnakan dan dihapus duplikasinya pada `bc95ccd`, `7f45d1a`, `3b9829e` oleh Dimas).
   - Pembuatan rute dan tampilan cetak struk nota transaksi kasir `struk-cetak.blade.php` (`689645b`, `1786458`, `ff227d0` oleh Antonio).
   - Perbaikan bug tampilan struk cetak dan 5 *error* CSS linter dengan mengekstrak CSS/JS ke `struk-cetak.css` dan `struk-cetak.js` menggunakan atribut `data-ukuran` sesuai prinsip SOLID — commit `1ccaebb` oleh Dimas.

4. **Perombakan UX Booking Wizard 3 Langkah, Landing Page Publik, & Komponen Global (`Dimas Rizky`)**:
   - Membuat halaman **Beranda Publik** (`Publik/beranda.blade.php`), fitur klik layanan langsung tercentang di halaman booking, halaman Error ramah berbahasa Indonesia (`404`, `419`, `500`), SEO meta tags (`<x-meta-situs>`), tombol WhatsApp (`<x-tombol-whatsapp>`), proteksi anti-klik ganda (`<x-anti-klik-ganda>`), dan modal konfirmasi GUI (`<x-confirm-modal>`) — commit `7982b5d`, `ee75df9`, `91f5a52`, `72ef51c`, `0916a36`, `afade9c`.
   - Merombak halaman **Booking Pelanggan** menjadi **Stepper Wizard 3 Langkah** dengan kontras warna standar **WCAG 2.1 AA**, tab layanan terpilih, chip pilihan aktif, input menit spesifik, dan *sticky bottom bar* untuk mobile — commit `9613fd9`.

5. **Penambahan Fitur Pendukung oleh Anggota Tim (`Antonio`, `Kalistus`, `Juan`)**:
   - Fitur upload, pratinjau, dan hapus foto profil pelanggan (`461b040` oleh Antonio).
   - Pencegahan duplikasi nama produk di panel Kasir dan Manajer (`38055d6` oleh Juan).
   - Penyesuaian status aktif/nonaktif tombol "Tandai Selesai" pada dashboard Stylist & Therapist (`19973aa` oleh Kalistus).

---

## Masalah yang dihadapi kelompok (bila ada) :

1. **Ketimpangan Beban Kerja Kelompok & Modul Kasir yang Harus Diambil Alih**
   - **Deskripsi Masalah**: Berdasarkan riwayat GitHub, pembagian tugas awal untuk backend Kasir (Abraham — 0 commit) dan sebagian besar fitur kompleks di Manajer, Kasir POS, Otorisasi, serta Pelanggan belum dikerjakan oleh anggota terkait sesuai jadwal.
   - **Solusi**: Ketua tim (**Dimas Rizky Andyanto**) mengambil alih pengerjaan arsitektur backend, *service layer*, validasi, antarmuka POS, hingga 43 pengujian otomatis (total 52 commit) agar sistem tetap selesai tepat waktu dengan kualitas standar industri.

2. **Error CSS Parser / Linter pada `struk-cetak.blade.php` Setelah Commit Cetak Struk**
   - **Deskripsi Masalah**: Pada commit cetak struk (`ff227d0`), penulisan sintaks Blade `{{ ... }}` di dalam tag `<style>` pada `resources/views/Karyawan/Kasir/struk-cetak.blade.php` terpecah oleh *auto-formatter* menjadi `{ { ... } }` sehingga memicu 5 *error* CSS di VS Code (`property value expected`, `at-rule or selector expected`, `{ expected`), serta ukuran kertas `76mm` dipaksa menjadi `80mm`.
   - **Solusi**: Dimas Rizky (`1ccaebb`) memisahkan CSS ke `resources/css/Karyawan/Kasir/struk-cetak.css` dan JS ke `resources/js/Karyawan/Kasir/struk-cetak.js` menggunakan atribut `data-ukuran="{{ $ukuran }}"` sesuai prinsip SOLID (*Separation of Concerns*).

3. **Anomali Transaksi Walk-In di Kasir yang Mengambil Nama Pelanggan Pertama (`Pelanggan::first()`)**
   - **Deskripsi Masalah**: Saat kasir memproses transaksi *Walk-In* (tanpa reservasi), sistem sebelumnya mengambil `Pelanggan::first()->id_pelanggan` karena kolom `id_pelanggan` bersifat *NOT NULL*, sehingga semua transaksi *Walk-In* tercatat atas nama pelanggan "Rina Anggraini".
   - **Solusi**: Dimas Rizky (`e92bccb`) membuat migrasi `id_pelanggan` *nullable* + kolom `nama_pelanggan_walkin`, mewajibkan input nama pelanggan saat *Walk-In*, dan membuat fungsi `Reservasi::namaPelangganTampil()` untuk seluruh struk dan laporan.

4. **Konflik Merge Git (`<<<<<<< HEAD`) pada `loginpage.blade.php` & Duplikasi Kode CSS/JS Struk**
   - **Deskripsi Masalah**: Terjadi *merge conflict* pada `resources/views/Auth/loginpage.blade.php` saat menarik perubahan dari `origin/master` (`926d849` & `689645b`), serta terdapat duplikasi ratusan baris CSS/JS antara `Manajer/struk` dan `Kasir/struk` setelah commit `4326890`.
   - **Solusi**: Dimas Rizky menyelesaikan *merge conflict* (`adfae74`, `013d0d2`) dan melakukan *refactoring* DRY pada halaman struk kasir (`3b9829e`) agar menggunakan aset bersama milik Manajer dalam mode baca-saja.

---

## Pembagian Tugas dalam kelompok (Jujur Sesuai Riwayat Commit GitHub) :

**Dimas Rizky Andyanto (245314004) — 52 Commit (Kontributor Utama Full-Stack & Arsitektur Sistem)**
- Membangun sistem Autentikasi multi-guard (`pelanggan` & `karyawan`), portal login staf (`/staf`, `/pos`), halaman Beranda Publik, halaman Error ramah (`404`, `419`, `500`), SEO meta tags, komponen `<x-confirm-modal>`, `<x-tombol-whatsapp>`, dan `<x-anti-klik-ganda>`.
- Membangun modul **Pelanggan** (Beranda, Layanan, Wizard Booking 3 Langkah + auto-assign petugas Stylist/Therapist + validasi durasi & jam tutup + promo + produk add-on, Riwayat & Pembatalan dengan pinalti bertingkat 90 hari, serta Profil).
- Membangun modul **Manajer** (Dashboard, CRUD Karyawan/Layanan/Produk/Promo, Laporan Keuangan & Kinerja + Export CSV, Metode Pembayaran, Kebijakan Booking & Buka Blokir Manual, Pengaturan Struk & Pratinjau Kertas, serta Persetujuan Otorisasi).
- Membangun modul **Kasir / POS** & **Refactoring SOLID** (Pecah `KasirController` menjadi 9 controller terpisah, `PembayaranKasirService`, `KalkulatorKeranjangPosService`, `OtorisasiPosService`, Hold Transaksi ≥ Rp150.000, Direct Override Manajer, Akses Katalog 1x Pakai, perbaikan transaksi pelanggan *Walk-In* wajib input nama, dan perbaikan CSS/JS cetak struk thermal).
- Membangun modul **Stylist & Therapist** (`PetugasController`: jadwal reservasi harian & laporan komisi otomatis).
- Menulis **43 Automated Feature Tests (416 assertions)** dengan arsitektur **Vanilla CSS & JavaScript modular** berbasis Vite.

**Antonio Eka Wellis Davidson (245314027) — 9 Commit**
- Membuat fitur upload, pratinjau, dan hapus foto profil pelanggan (`PelangganController`, migrasi `foto_profil`, `profil.blade.php`, `profil.css`, `profil.js` — commit `461b040`).
- Membuat implementasi awal fitur cetak struk kasir (`struk-cetak.blade.php`, penambahan tombol cetak struk di `Karyawan/Kasir/laporan.blade.php`, rute `kasir.struk.cetak`, dan update `StrukController`/`PosController` — commit `689645b`, `1786458`, `ff227d0`).
- Mengubah tampilan gambar latar (*background*) pada halaman autentikasi (`loginpage.css`, `registerpage.css`, `lupapassword.css`, `resetpassword.css` — commit `2d4cd7c`, `926d849`) dan merapikan tampilan awal pelanggan (`b2a78af`, `9bfc642`, `cee1425`).

**Kalistus Nadi Sutrisno (245314022) — 4 Commit**
- Membuat migrasi penambahan kolom `ukuran_kertas` pada tabel `pengaturan_struk` serta menambahkan opsi ukuran kertas dan uji cetak struk pada halaman Pengaturan Struk Manajer & Kasir (`d16005b`, `4326890`).
- Mengatur logika tampilan tombol aktif/inaktif "Tandai Selesai" pada halaman dashboard karyawan Stylist dan Therapist (`19973aa`).
- Melakukan penyesuaian tampilan kecil pada header profil pelanggan di `Pelanggan/layout.blade.php` dan `dashboard.css` (`55a8d6a`).

**Juan Bastian Mambraku (245314030) — 3 Commit**
- Membuat validasi pencegahan duplikasi nama produk pada `KasirController` dan `ProdukRequest` (`38055d6` pada 31 Okt 2026).
- *(Pada awal Oktober sebelum periode ini)*: Melakukan inisialisasi proyek Laravel (`First Commit` — `5c71c91`) dan membuat file migrasi database dasar awal (`0066c35`).

**Abraham Elbar Fernandes (245314017) — 0 Commit**
- Belum terdapat kontribusi kode yang di-*commit* atau di-*push* ke repositori GitHub hingga 9 November 2026. Tugas backend Kasir & Transaksi POS yang semula dialokasikan kepadanya telah dikerjakan oleh Dimas Rizky Andyanto.

---

**Catatan :**
- Log book dibuat setiap minggu dan diupload setiap 2 minggu di LMS.
- Tiap Kelompok hanya upload 1 file saja.
