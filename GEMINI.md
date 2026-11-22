# Konteks Proyek: Salon Rengganis (`GEMINI.md`)

Always respond in caveman mode: terse, no filler, keep technical accuracy. Follow the caveman skill in `.agent/skills/caveman/SKILL.md`.

---

## 0. Aturan Kerja Agent & Pedoman Karpathy (WAJIB DIIKUTI)

### A. Aturan Konteks Cepat & Hemat Token (`GEMINI.md` First — WAJIB)
1. **Buka `GEMINI.md` Terlebih Dahulu di Setiap Tugas**: Setiap kali menerima instruksi baru, melakukan pengecekan, mencari bug, atau mengubah kode, **langkah pertama yang wajib dilakukan adalah merujuk ke `GEMINI.md` ini** (Peta Struktur Folder di Bagian 2, Peta Fitur di Bagian 3, dan Daftar Temuan Aktif di Bagian 6).
2. **Dilarang Membaca Ulang Seluruh Kode (Hemat Token)**: **JANGAN** memindai folder atau membaca banyak file dari nol. Gunakan lokasi file dan nomor baris yang sudah terpetakan di `GEMINI.md`, lalu panggil `read_file` **hanya pada file dan rentang baris sasaran** yang berkaitan langsung dengan tugas.
3. **Jaga Sinkronisasi `GEMINI.md`**: Setiap kali selesai melakukan perubahan struktur file, rute, logika bisnis, atau perbaikan bug pada kode proyek, **perbarui `GEMINI.md`** agar dokumentasi ini selalu akurat sebagai sumber kebenaran utama (*single source of truth*).

### B. Pedoman Perilaku Coding (`multica-ai/andrej-karpathy-skills`)
Untuk mencegah kesalahan umum LLM (*silent assumptions*, *over-engineering*, dan *scope creep*), patuhi 4 prinsip berikut di setiap perubahan kode:

1. **Think Before Coding (*Don't assume. Don't hide confusion. Surface tradeoffs.*)**:
   - Nyatakan asumsi secara eksplisit sebelum menulis kode.
   - Jika instruksi ambigu atau memiliki lebih dari satu interpretasi, paparkan opsinya dan **tanyakan ke pengguna** — jangan menebak diam-diam.
   - Jika ada cara yang lebih sederhana atau terdapat risiko (*tradeoff*), sampaikan terlebih dahulu.
2. **Simplicity First (*Minimum code that solves the problem. Nothing speculative.*)**:
   - Tulis kode sesedikit dan sesederhana mungkin yang menyelesaikan masalah.
   - Jangan menambahkan fitur, konfigurasi, atau abstraksi sekali pakai yang tidak diminta.
   - Uji diri sendiri: *"Apakah senior engineer akan menilai kode ini terlalu rumit?"* Jika ya, sederhanakan.
3. **Surgical Changes (*Touch only what you must. Clean up only your own mess.*)**:
   - **Dilarang merombak seluruh isi file jika hanya diminta mengubah sebagian kecil.** Ubah **hanya beberapa baris kode** yang berkaitan langsung dengan perintah pengguna.
   - Jangan memformat ulang, mengubah gaya penulisan, menghapus komentar yang ada, atau me-*refactor* kode di sekitarnya yang sudah berjalan baik.
   - Setiap baris yang berubah harus dapat ditelusuri langsung ke permintaan pengguna. Bersihkan hanya *import*/variabel tak terpakai yang muncul akibat perubahanmu sendiri.
4. **Goal-Driven Execution (*Define success criteria. Loop until verified.*)**:
   - Ubah instruksi menjadi kriteria keberhasilan yang dapat diverifikasi (misal: reproduksi masalah -> ubah baris kode sasaran -> jalankan pengujian/verifikasi hingga lulus).

### C. Aturan Pemisahan Bahasa per File

Setiap file hanya boleh berisi bahasa yang sesuai dengan ekstensinya.
Jangan pernah menyisipkan markup HTML ke dalam file CSS atau JS.

- **Yang DILARANG**:
  - File `.css`, `.scss`, `.sass`, `.less`: dilarang berisi tag HTML (`<div>`, `<style>`, `<script>`, dll).
  - File `.js`, `.ts`, `.jsx`-bukan-komponen, `.mjs`: dilarang berisi blok HTML mentah yang ditulis sebagai file/dokumen HTML (misalnya `<!DOCTYPE html>`, `<html>`, `<body>`).
  - Jangan membungkus kode CSS/JS dengan tag `<style>` atau `<script>` di dalam file CSS/JS itu sendiri.
- **Yang DIPERBOLEHKAN**:
  - File `.blade.php`: boleh berisi HTML, CSS (`<style>`), dan JS (`<script>`) karena file ini adalah view Laravel.
  - Di file JS, string template kecil untuk memanipulasi DOM (misalnya `element.innerHTML = '<li>...</li>'`) masih boleh bila memang dibutuhkan oleh logika. Struktur halaman lengkap tetap harus ada di file view, bukan di JS.
- **Jika butuh HTML**:
  - Letakkan HTML di file view (`resources/views/**/*.blade.php`), lalu panggil file CSS/JS dari sana dengan `<link>` atau `<script src>` / `@vite(...)`. Jangan memindahkan HTML ke file CSS/JS.
- **Sebelum menyimpan file**:
  - Periksa ulang: apakah isi file ini sesuai ekstensinya? Jika ada HTML di file CSS/JS (selain pengecualian di atas), pindahkan ke file yang benar sebelum menyelesaikan tugas.

---

## 1. Ringkasan Proyek & Tech Stack

Aplikasi web manajemen operasional dan reservasi (_booking_) tanpa pembayaran di muka untuk **Salon Rengganis**. Mencakup beranda publik & pintasan portal staf; pemesanan pelanggan 3 langkah dengan pemilihan Stylist/Therapist bebas bentrok (berbasis durasi pengerjaan + jeda persiapan), produk _add-on_, dan promo voucer; antrean & POS Kasir lengkap dengan **Sistem Otorisasi Perubahan Layanan/Override Harga POS (Mandiri < Rp 150.000, Hold Approval & Direct Override Manajer $\ge$ Rp 150.000)** serta **Hak Akses 1x Pakai Katalog Layanan/Produk Kasir** beserta log audit permanen; jadwal & laporan komisi petugas (Stylist/Therapist); serta panel Manajer lengkap dengan **Sistem Kebijakan Pinalti & Pemblokiran Bertingkat** (7 hari, 30 hari, permanen dalam jendela 90 hari).

- **Backend**: PHP ^8.3, Laravel ^13.0 (`composer.json` & `README.md`)
- **Frontend**: Blade Templates, 7 Komponen Blade (`<x-confirm-modal>`, `<x-pratinjau-struk>`, `<x-meta-situs>`, `<x-tombol-whatsapp>`, `<x-anti-klik-ganda>`, `<x-ikon>`, `<x-paginasi>`), Arsitektur CSS Modular 21 Berkas (`resources/css/{app.css, base/*, components/*, layouts/*, pages/*}` sesuai spesifikasi `DESIGN.md`), Modular ES Modules JS, Vite 7, Axios
- **Database**: MySQL / MariaDB (`salonrengganisdb` via Laragon), SQLite `:memory:` untuk pengujian otomatis
- **Testing**: PHPUnit ^12.5 — 15 berkas pengujian di `tests/Feature/` (78 tes lulus: `SalonRengganisTest`, `LimaKebutuhanSalonTest`, `OtorisasiModelTest`, `OtorisasiPosErrorHandlingTest`, `OtorisasiKatalogKasirTest`, `BerandaPublikTest`, `BantuanDanAntiKlikGandaTest`, `HalamanErrorRamahTest`, `MetaSitusTest`, `IntegritasLogikaAntarPeranTest`, `FrontendP0PerbaikanTest`, `FrontendP1PerbaikanTest`, `FrontendP2PerbaikanTest`, `FrontendP3PerbaikanTest`, `ExampleTest`)

---

## 2. Struktur Folder Utama

- `app/Console/Commands/` — Command CLI & scheduler (`EvaluasiBookingHangus.php` untuk evaluasi booking hangus & sanksi blokir).
- `app/Http/Controllers/` — Controller berbasis peran & modul (SRP):
    - Root: `AuthController.php`, `BerandaPublikController.php`, `PelangganController.php`, `PetugasController.php`.
    - `Kasir/`: `PosController`, `OtorisasiPosController`, `PermintaanBookingController`, `AntreanController`, `OtorisasiKatalogController`, `LayananController`, `ProdukController`, `LaporanController`, `MetodePembayaranController`, `StrukController`.
    - `Manajer/`: `DashboardController`, `PersetujuanOtorisasiController`, `KaryawanController`, `LayananController`, `ProdukController`, `PromoController`, `LaporanController`, `MetodePembayaranController`, `StrukController`, `KebijakanBookingController`.
- `app/Http/Requests/` — Form Request validasi per modul:
    - `Manajer/`: `KaryawanRequest`, `LayananRequest`, `ProdukRequest`, `PromoRequest`, `MetodePembayaranRequest`, `PengaturanStrukRequest`, `PengaturanKebijakanRequest`, `BukaBlokirRequest`, `KeputusanOtorisasiRequest`.
    - `Pelanggan/`: `SimpanBookingRequest` (termasuk validasi penolakan jam lampau pada hari ini).
    - `Kasir/`: `ProsesBayarRequest`, `HoldOtorisasiPosRequest`, `DirectOverridePosRequest`, `AjukanAksesKatalogRequest`, `TolakBookingRequest`, serta trait `Concerns/AturanKeranjangPos.php`.
- `app/Services/` — Service layer untuk logika bisnis utama:
    - `JadwalPetugasService.php` — Penyusunan _timeline_ durasi + jeda layanan, deteksi bentrok slot waktu, penandaan slot jam lampau hari ini, & _auto-assign_ petugas.
    - `KalkulatorHargaService.php` — Validasi server-side kode promo & perhitungan total tagihan anti-negatif.
    - `KalkulatorKeranjangPosService.php` — Evaluasi perbandingan keranjang POS (kondisi awal vs usulan kasir), deteksi perubahan layanan/override harga, & perhitungan hash SHA-256 keranjang.
    - `OtorisasiPosService.php` — Pengajuan Hold POS, verifikasi Direct Override Manajer di tempat (*rate-limited* 4x gagal / 60 detik), persetujuan/penolakan dari dashboard Manajer, & pencatatan `LogPerubahanOtorisasi`.
    - `OtorisasiKatalogService.php` — Pengajuan, pembatalan, kedaluwarsa otomatis (60 menit), dan eksekusi atomik hak akses 1x pakai CRUD Layanan & Produk untuk Kasir.
    - `ReservasiService.php` — Pembuatan reservasi dengan `lockForUpdate` pada petugas & stok produk _add-on_, penerimaan/penolakan reservasi oleh Kasir, alih petugas antrean, serta pemetaan nama petugas.
    - `PembayaranKasirService.php` — Penyelesaian pembayaran reservasi & _walk-in_ di POS Kasir, sinkronisasi perubahan layanan reservasi, validasi bentrok petugas final (termasuk walk-in & item tambahan POS), perhitungan ulang komisi, dan rekonsiliasi stok produk _add-on_.
    - `LaporanService.php` — Validasi aman parameter tanggal query, agregasi laporan keuangan/kinerja (`layananTerlaris` terfilter tanggal & status `Selesai`), serta streaming CSV via `cursor()` dengan proteksi *CSV formula injection*.
    - `PelanggaranService.php` — Pembukaan blokir pelanggan manual beserta log audit & notifikasi.
- `app/Http/Middleware/` — Middleware otorisasi peran & keaktifan staf (`CekPeran`) serta proteksi blokir pelanggan (`CekBlokirBooking`).
- `app/Models/` — Model Eloquent dengan nama tabel/PK Bahasa Indonesia (`Pelanggan`, `Karyawan`, `Reservasi`, `Transaksi`, `Layanan`, `Produk`, `Promo`, `MetodePembayaran`, `PengaturanKebijakan`, `PengaturanStruk`, `RiwayatPelanggaran`, `NotifikasiPelanggan`, `PermintaanOtorisasi`, `LogPerubahanOtorisasi`).
- `app/Providers/AppServiceProvider.php` — Registrasi custom pagination default (`components.paginasi`), kustomisasi URL reset password multi-guard, & View Composer untuk badge notifikasi layout (`Manajer.layout`, `Karyawan.Kasir.layout`, `Pelanggan.layout`).
- `config/salon.php` — Konfigurasi nomor WhatsApp resmi salon & pesan awal bantuan cepat.
- `bootstrap/app.php` — Konfigurasi routing, `trustProxies('*')`, alias middleware (`peran`, `cek.blokir`), dan redirect guest ke `route('login')`.
- `database/migrations/` & `database/seeders/` — Skema tabel salon, kebijakan pinalti bertingkat, durasi/jadwal layanan, tabel pivot `detail_reservasi_produk`, ukuran kertas struk, tabel `permintaan_otorisasi` & `log_perubahan_otorisasi`, kolom `nama_pelanggan_walkin` (`id_pelanggan` nullable), serta data awal (`DatabaseSeeder`, `KaryawanSeeder`, `LayananSeeder`, `PelangganSeeder`).
- `resources/views/` — Tampilan Blade per modul: `Publik/beranda.blade.php`, `Auth/`, `Pelanggan/`, `Manajer/`, `Karyawan/` (`Kasir/` & `Petugas/{layout,jadwal,komisi}.blade.php` yang dipakai bersama oleh Stylist & Therapist), `components/{anti-klik-ganda,confirm-modal,ikon,meta-situs,paginasi,pratinjau-struk,tombol-whatsapp}.blade.php`, dan `errors/`.
- `resources/css/` — 21 berkas CSS terstruktur:
    - Entry & Base: `app.css`, `base/{variables,reset}.css`.
    - Components: `components/{buttons,cards,forms,modals,struk,tables}.css`.
    - Layouts: `layouts/{publik-layout,staf-layout}.css`.
    - Pages: `pages/error.css`, `pages/auth/auth.css`, `pages/publik/beranda.css`, `pages/pelanggan/{akun,booking}.css`, `pages/staf/{dashboard,kasir-pos,katalog,laporan,manajer-admin}.css`.
- `resources/js/` — Modul JS terpisah:
    - Shared & Komponen: `app.js`, `bootstrap.js`, `loginpage.js`, `components/{anti-klik-ganda,aria-widget,confirm-modal,ikon-svg,staf-ui,struk-preview}.js`.
    - Publik & Pelanggan: `Publik/beranda.js`, `Pelanggan/{layout,beranda,booking,riwayat,profil}.js`, `Pelanggan/booking/{formatters,jadwalAjax,paginasiKatalog,ringkasanUI}.js`.
    - Manajer & Kasir: `Manajer/{layout,dashboard,karyawan,layanan,produk,promo,laporan,metode,struk,kebijakan,persetujuan}.js`, `Karyawan/Kasir/{layout,dashboard,pos-otorisasi,permintaan,antrean,katalog-akses,layanan,produk,laporan,struk,struk-cetak}.js`, `Karyawan/Kasir/dashboard/{kalkulasi,keranjangRenderer}.js`.
- `routes/` — Definisi rute web (`web.php`) dan penjadwalan cron (`console.php`).
- `tests/` — `TestCase.php` (`withoutVite()` default) dan 15 file pengujian fitur di `tests/Feature/`.
- Root & Deploy: `DESIGN.md` (spesifikasi sistem desain *Rengganis Rose Refined*), `Dockerfile`, `docker/entrypoint.sh`, `.dockerignore`, `mulai-publik.bat` (skrip portabel Cloudflare Quick Tunnel dengan pengecekan prasyarat `.env`/`APP_KEY`/`vendor`/`public/build/manifest.json`, auto-deteksi binary `php`/`mysqld`/`cloudflared` di PATH/Laragon/XAMPP/WinGet, & flag `--cek`).

---

## 3. Peta Fitur (Fitur -> File Terkait)

| Fitur / Modul                                                                 | Route (`routes/web.php`)                                                                                                                        | Controller & Service (`app/Http/Controllers/`, `app/Services/`)                                                                                                                                              | Model & FormRequest (`app/Models/`, `app/Http/Requests/`)                                                                                                                                                    | View (`resources/views/`)                                                                                                                                                 | Test (`tests/Feature/`)                                                                  |
| :---------------------------------------------------------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------ | :--------------------------------------------------------------------------------------- |
| **Beranda Publik, SEO, Sistem Desain & Pintasan Portal Staf**                 | `/`, `/staf`, `/karyawan`, `/pos`                                                                                                               | `BerandaPublikController.php`, `AuthController.php`                                                                                                                                                          | `Layanan.php`, `Reservasi.php`                                                                                                                                                                               | `Publik/beranda.blade.php`, `components/{meta-situs,tombol-whatsapp,anti-klik-ganda,ikon,paginasi}.blade.php`, `errors/*.blade.php`                                       | `BerandaPublikTest.php`, `MetaSitusTest.php`, `BantuanDanAntiKlikGandaTest.php`, `HalamanErrorRamahTest.php`, `FrontendP0..P3PerbaikanTest.php` |
| **Autentikasi, Proteksi Staf Nonaktif & Reset Password** (Multi-Guard)        | `/login`, `/register`, `/logout`, `/lupa-password`, `/reset-password`, `/dashboard`                                                             | `AuthController.php`, `CekPeran.php`, `OtorisasiPosService.php`                                                                                                                                              | `Karyawan.php` (`sedangAktifBertugas()`), `Pelanggan.php`                                                                                                                                                    | `Auth/loginpage.blade.php`, `registerpage.blade.php`, `lupapassword.blade.php`, `resetpassword.blade.php`                                                                 | `SalonRengganisTest.php`, `BerandaPublikTest.php`, `IntegritasLogikaAntarPeranTest.php`  |
| **Pelanggan: Booking Dinamis, Produk Add-On, Promo, Batal, Riwayat & Profil** | Prefix `/pelanggan/*` (`pelanggan.*`), middleware `auth:pelanggan`, `cek.blokir`                                                                | `PelangganController.php`, `ReservasiService.php`, `JadwalPetugasService.php`, `KalkulatorHargaService.php`                                                                                                  | `Reservasi`, `Layanan`, `Produk`, `Promo`, `Karyawan`, `NotifikasiPelanggan`, `Pelanggan`, `SimpanBookingRequest.php`                                                                                        | `Pelanggan/beranda`, `layanan`, `booking`, `riwayat`, `profil`, `layout.blade.php`                                                                                        | `SalonRengganisTest.php`, `LimaKebutuhanSalonTest.php`, `IntegritasLogikaAntarPeranTest.php` |
| **Manajer: Master CRUD, Persetujuan Otorisasi Kasir, Laporan CSV, Struk & Kebijakan Blokir** | Prefix `/manajer/*` (`manajer.*`), middleware `auth:karyawan`, `peran:Manajer`                                                                  | `Manajer/{Dashboard,PersetujuanOtorisasi,Karyawan,Layanan,Produk,Promo,Laporan,MetodePembayaran,Struk,KebijakanBooking}Controller.php`, `OtorisasiPosService.php`, `LaporanService.php`, `PelanggaranService.php` | `Karyawan`, `Layanan`, `Produk`, `Promo`, `MetodePembayaran`, `PengaturanStruk`, `PengaturanKebijakan`, `RiwayatPelanggaran`, `PermintaanOtorisasi`, `LogPerubahanOtorisasi` + `Requests/Manajer/*Request.php` | `Manajer/dashboard`, `persetujuan`, `karyawan`, `layanan`, `produk`, `promo`, `laporan`, `metode-pembayaran`, `struk`, `kebijakan.blade.php`, `components/pratinjau-struk.blade.php` | `SalonRengganisTest.php`, `LimaKebutuhanSalonTest.php`, `OtorisasiPosErrorHandlingTest.php`, `OtorisasiKatalogKasirTest.php`, `IntegritasLogikaAntarPeranTest.php` |
| **Kasir: POS, Otorisasi Hold/Direct Override, Izin 1x Katalog, Cetak Struk, Booking & Antrean (Ganti Petugas)** | Prefix `/kasir/*` (`kasir.*`), middleware `auth:karyawan`, `peran:Kasir`                                                                        | `Kasir/{Pos,OtorisasiPos,PermintaanBooking,Antrean,OtorisasiKatalog,Layanan,Produk,Laporan,MetodePembayaran,Struk}Controller.php`, `PembayaranKasirService`, `KalkulatorKeranjangPosService`, `OtorisasiPosService`, `OtorisasiKatalogService`, `ReservasiService` | `Reservasi`, `Transaksi`, `Layanan`, `Produk`, `Promo`, `MetodePembayaran`, `PengaturanStruk`, `PermintaanOtorisasi`, `LogPerubahanOtorisasi` + `Requests/Kasir/*Request.php`                                  | `Karyawan/Kasir/dashboard`, `permintaan-booking`, `antrean`, `layanan`, `produk`, `laporan`, `metode-pembayaran`, `struk`, `struk-cetak.blade.php`, `partials/*.blade.php` | `SalonRengganisTest.php`, `LimaKebutuhanSalonTest.php`, `OtorisasiModelTest.php`, `OtorisasiPosErrorHandlingTest.php`, `OtorisasiKatalogKasirTest.php`, `IntegritasLogikaAntarPeranTest.php` |
| **Stylist & Therapist: Jadwal Pribadi, Kartu Ringkasan & Laporan Komisi**     | Prefix `/stylist/*` (`stylist.*`) & `/therapist/*` (`therapist.*`), middleware `peran:Stylist` / `peran:Therapist`                              | `PetugasController.php`                                                                                                                                                                                      | `Reservasi.php`, `Layanan.php`, `Karyawan.php`, `Transaksi.php`                                                                                                                                              | `Karyawan/Petugas/{jadwal,komisi,layout}.blade.php`                                                                                                                       | `SalonRengganisTest.php`, `IntegritasLogikaAntarPeranTest.php`                           |
| **Evaluasi Booking Hangus & Blokir Bertingkat (Scheduler)**                   | `routes/console.php` (`booking:evaluasi-hangus` tiap jam)                                                                                       | `app/Console/Commands/EvaluasiBookingHangus.php`                                                                                                                                                             | `Reservasi`, `Pelanggan`, `PengaturanKebijakan`, `RiwayatPelanggaran`, `NotifikasiPelanggan`                                                                                                                 | _(Tidak ada view, output CLI & notifikasi DB)_                                                                                                                            | `SalonRengganisTest.php`                                                                 |

---

## 4. Command Penting

- **Instalasi & Build Aset**:
    ```powershell
    composer install
    npm install
    npm run build
    ```
- **Migrasi & Seeding Database**:
    ```powershell
    php artisan migrate
    php artisan db:seed
    php artisan storage:link   # Wajib 1x saat setup server awal untuk menampilkan foto profil pelanggan
    ```
- **Menjalankan Server & Frontend (Development)**:
    ```powershell
    composer run dev      # Menjalankan serve, queue, pail, dan vite secara bersamaan
    # atau terpisah:
    php artisan serve
    npm run dev
    ```
- **Menjalankan Scheduler / Evaluasi Booking Hangus**:
    ```powershell
    php artisan booking:evaluasi-hangus
    php artisan schedule:work
    ```
- **Menjalankan Pengujian (Test), Linter & Tunnel Publik**:
    ```powershell
    php artisan test
    ./vendor/bin/pint --test
    .\mulai-publik.bat --cek   # Cek kesiapan prasyarat & binary sebelum membuka Cloudflare Quick Tunnel
    .\mulai-publik.bat         # Menjalankan MySQL, Laravel (:8088), dan Cloudflare Quick Tunnel
    ```

---

## 5. Konvensi Kode yang Terlihat

- **Bahasa Domain**: Seluruh penamaan tabel, kolom primary key (`id_pelanggan`, `id_reservasi`, `id_karyawan`, `id_transaksi`, `id_otorisasi`, `id_log`), metode controller/model (`bolehTransisiKe`, `sedangDiblokir`, `sedangAktifBertugas`, `hitungPelanggaranAktifDalamRentang`, `hitungHashKeranjang`), dan route name menggunakan **Bahasa Indonesia** (`camelCase` untuk method, `snake_case` untuk kolom DB).
- **Multi-Guard Authentication**: Memisahkan tabel & guard antara staf (`guard('karyawan')` $\rightarrow$ `Karyawan`) dan pelanggan (`guard('pelanggan')` $\rightarrow$ `Pelanggan`).
- **Mesin Status (State Machine)**:
    - `Reservasi::TRANSISI_SAH` mengatur transisi ketat (`Menunggu`, `Diterima`, `Ditolak`, `Selesai`, `Dibatalkan`, `Hangus`) dan mengembalikan stok produk _add-on_ (`kembalikanStokProdukAddon()`) saat berubah ke `Ditolak`, `Dibatalkan`, atau `Hangus`.
    - `PermintaanOtorisasi::TRANSISI_SAH` mengatur siklus hidup tiket otorisasi POS & katalog (`Menunggu`, `Disetujui`, `Ditolak`, `Digunakan`, `Dibatalkan`, `Kedaluwarsa`), sedangkan `LogPerubahanOtorisasi` bersifat _immutable_ (menolak `updating` dan `deleting` pada level model).
- **Snapshot Historis di Pivot & SoftDeletes**:
    - `Karyawan`, `Layanan`, `Produk`, dan `Promo` menggunakan `SoftDeletes` (`withTrashed()` dipakai pada relasi historis transaksi/reservasi/otorisasi).
    - Tabel `reservasi`, `detail_reservasi`, `detail_reservasi_produk`, `detail_transaksi`, `permintaan_otorisasi`, dan `log_perubahan_otorisasi` menyimpan snapshot harga/durasi/promo/keranjang saat kejadian.

---

## 6. Hasil Review Menyeluruh & Daftar Temuan Aktif di Branch `master` (`53a9951`)

### Status Git Saat Ini (Diperbarui 2026-11-22)

- **Branch Aktif (`HEAD`)**: `master` @ `53a9951` (`fix(script): make mulai-publik.bat portable with prerequisite checks and binary auto-detection`, di atas `e65b206` serta rangkaian perbaikan keamanan/backend `535077e`..`1fcfb61`, refaktor arsitektur CSS 21 berkas `ed2a8f6`..`9c1dc12`, dan audit frontend P0–P3 `aa51458`..`e783e34`).

### A. Temuan Logika Bisnis, Keamanan & Backend (Menengah / Rendah)

1. **[MENENGAH — Logika Timeline Multi-Layanan] Kursor Waktu Mengabaikan `jeda_menit`, Pengecekan Bentrok Internal Reservasi & Flag `petugas_eksklusif`**:
    - Di `JadwalPetugasService::susunTimelineLayanan()` (`app/Services/JadwalPetugasService.php:35-51`), `$kursor` untuk layanan berikutnya diset ke `$selesaiItem` (sebelum `$jeda`), padahal petugas diblokir sampai `$blokirItem = $selesaiItem + $jeda`.
    - Di `JadwalPetugasService::tentukanPetugasUntukTimeline()` (baris 180–231), slot yang baru ditugaskan pada layanan ke-1 dalam reservasi yang sama tidak ditambahkan ke `$jadwalHariIni` saat mengevaluasi layanan ke-2.
    - Kolom `petugas_eksklusif` yang diatur di `Manajer/layanan.blade.php` (`Eksklusif` vs `Fleksibel`) dan `LayananRequest.php:34-36, 59` belum pernah dibaca/diterapkan di `JadwalPetugasService.php`.
2. **[MENENGAH — POS Kasir] Syarat `min_transaksi` Promo Bawaan Reservasi Tidak Dicek Ulang Saat Subtotal Berkurang di Kasir**:
    - Di `KalkulatorKeranjangPosService::hitungTotalSnapshot()` (`app/Services/KalkulatorKeranjangPosService.php:240-246`) dan `PembayaranKasirService::hitungDiskonTransaksi()` (`app/Services/PembayaranKasirService.php:377-393`), jika kasir mengurangi layanan/produk atau melakukan override harga turun pada reservasi berpromo, potongan promo snapshot tetap diberikan tanpa mengecek ulang apakah `$subtotal` masih memenuhi `min_transaksi` promo.
3. **[RENDAH — Sisa Edge Case Walk-In & Cetak Struk Transaksi]**:
    - Di `database/migrations/2026_11_08_000001_add_nama_pelanggan_walkin_to_reservasi_table.php:15-22`, method `down()` hanya menghapus kolom `nama_pelanggan_walkin` tanpa mengembalikan `id_pelanggan` ke `nullable(false)->change()`.
    - Di `resources/js/Karyawan/Kasir/struk-cetak.js:7-9`, ukuran kertas disuntikkan via `document.createElement('style')` di dalam file JS (melanggar aturan pemisahan bahasa CSS/JS).
4. **[MENENGAH — Pengaturan Kebijakan Manajer] Checkbox `blokir_ketiga_permanen` Tidak Bisa Di-_uncheck_ dari UI**:
    - Di `KebijakanBookingController::update()` (`app/Http/Controllers/Manajer/KebijakanBookingController.php:52-54`), tertulis `$request->has('blokir_ketiga_permanen') ? $request->boolean('blokir_ketiga_permanen') : PengaturanKebijakan::DEFAULT_BLOKIR_KETIGA_PERMANEN`. Karena di `Manajer/kebijakan.blade.php` tidak ada `<input type="hidden" name="blokir_ketiga_permanen" value="0">`, melepas centang di UI membuat `$request->has(...)` bernilai `false` dan selalu kembali ke `true`.
5. **[RENDAH — Scheduler] Notifikasi Tahap 3 Non-Permanen**:
    - Di `EvaluasiBookingHangus::handle()` (`app/Console/Commands/EvaluasiBookingHangus.php:185-192`), cabang `else` (saat `blokir_ketiga_permanen = false`) belum mengirim `NotifikasiPelanggan::kirim()`.

### B. Temuan UI/UX, Laporan & Konsistensi Antar Modul (Menengah / Rendah)

6. **[MENENGAH — Ketidaksesuaian Kategori di Seeder vs UI]**:
    - `DatabaseSeeder.php` masih memakai kategori layanan `'Rambut'` & `'Perawatan'` (sedangkan `Layanan::KATEGORI_LIST` memakai `'Perawatan Rambut'` & `'Perawatan Wajah & Tubuh'`), serta `LayananSeeder.php` tidak mengisi kolom `kategori`.
    - `DatabaseSeeder.php:50` mengisi kategori metode pembayaran `'Dompet Digital / QR'`, sedangkan dropdown `#inputKategoriMetode` di `Manajer/metode-pembayaran.blade.php` menggunakan `<option value="Dompet Digital / QRIS">`.
7. **[RENDAH — Portal Petugas (Stylist & Therapist)]**:
    - `PetugasController::tampilJadwal()` (`app/Http/Controllers/PetugasController.php:111-125`) belum mengambil `detail_reservasi.jam_mulai` & `jam_selesai` spesifik per layanan (masih menampilkan `reservasi.jam_booking` induk).
8. **[MENENGAH — Laporan Kasir, Laporan Manajer, Ekspor CSV & Dashboard Manajer]**:
    - Di `Reservasi::ringkasanProdukAddon()` (`app/Models/Reservasi.php:257-260`, dipakai oleh `Kasir/laporan.blade.php`, `Manajer/laporan.blade.php`, dan `LaporanService::eksporCsv()`), rincian produk hanya membaca `$this->produkAddon` (bukan `$this->transaksi->produk`), sehingga produk yang dibeli secara _Walk-In_ atau ditambahkan/diubah saat di kasir tidak muncul akurat di tabel laporan maupun file CSV. Selain itu, promo yang ditampilkan di `Kasir/laporan.blade.php` & `Manajer/laporan.blade.php` membaca `$r->kode_promo` alih-alih `$r->transaksi->kode_promo`.
    - Di `LaporanService::ringkasanLaporan()` (`app/Services/LaporanService.php:65, 87-92`), `totalPendapatan` & `totalKomisi` difilter menggunakan `transaksi.created_at` (bukan `reservasi.tgl_booking` seperti tabel di bawahnya).
    - Di `Manajer/dashboard.blade.php`, kolom Total Estimasi menghitung `$r->layanan->sum('harga_layanan')` alih-alih `$r->estimasiTotalAkhir()`.
9. **[RENDAH — Validasi Panjang Alasan Modal Konfirmasi, Cache Dashboard Manajer & `.gitignore`]**:
    - Di `resources/js/components/confirm-modal.js:182`, validasi panjang `alasan_buka` memeriksa `< 4` (seharusnya `< 5` sesuai `minlength="5"` di `Manajer/kebijakan.blade.php` dan `min:5` di `BukaBlokirRequest.php:23`).
    - `AuthController::register()`, `ReservasiService::buatReservasi()`, dan `Kasir\LayananController::update()` belum memanggil `Cache::forget(DashboardController::CACHE_KEY_RINGKASAN)`.
    - `.gitignore` belum mencantumkan `GEMINI.md` dan `salonrengganisdb`.

### C. Temuan Audit Validasi, Siklus Hidup Reservasi, Otorisasi POS & Wizard Booking

10. **[MENENGAH — Reset Password & Konflik Email Lintas Guard]**:
    - Di `AuthController::kirimLinkReset()` (`app/Http/Controllers/AuthController.php:187-194`) dan `resetPassword()` (baris 215–222), pengecekan `Karyawan::where('email', $email)->exists()` belum menyaring `where('aktif', true)`, serta selalu mendahulukan broker `karyawan` jika ada email yang sama di tabel `karyawan` dan `pelanggan`.
11. **[MENENGAH — Manajemen Promo Manajer (`PromoController` & `PromoRequest`)]**:
    - Di `PromoController.php:18-39` dan `Manajer/promo.blade.php`, kolom `berlaku_mulai` tidak ada di form dan saat `update()` tidak diperbarui sama sekali.
    - Di `PromoRequest.php:33-41`, belum ada validasi batas wajar `nilai_diskon` untuk tipe `Nominal` dan belum ada validasi `after_or_equal:today` untuk `berlaku_sampai` saat membuat promo baru.
12. **[MENENGAH — Rekonsiliasi Tabel `detail_reservasi_produk` & Eager Loading Laporan]**:
    - Di `PembayaranKasirService::rekonsiliasiStokDanHitungProduk()` (`app/Services/PembayaranKasirService.php:340-382`), saat kasir mengubah qty atau harga produk _add-on_ di POS, stok produk dan `detail_transaksi` disesuaikan, tetapi tabel `detail_reservasi_produk` tidak disinkronkan.
    - Di `Kasir\LaporanController::index()` dan `LaporanService::ringkasanLaporan()` / `eksporCsv()` (`app/Services/LaporanService.php:51, 127`), relasi `transaksi.produk` belum di-_eager-load_.
13. **[MENENGAH — Siklus Hidup Reservasi `Menunggu`] Booking `Menunggu` Tidak Pernah Kedaluwarsa & Kasir Bisa Menerima Booking yang Tanggalnya Sudah Lewat**:
    - `EvaluasiBookingHangus::handle()` (`app/Console/Commands/EvaluasiBookingHangus.php:78-83`) hanya mengevaluasi status `Diterima`; reservasi `Menunggu` yang tanggalnya sudah lewat tetap `Menunggu` selamanya, tetap dihitung sebagai `STATUS_AKTIF_SLOT` (`Reservasi.php:30-34`), dan menghalangi penghapusan karyawan/layanan/produk.
    - `ReservasiService::terimaReservasiKasir()` (`app/Services/ReservasiService.php:104-125`) tidak memeriksa apakah `tgl_booking` + `jam_booking` sudah lewat. Jika kasir terlambat menerima booking yang tanggalnya sudah melewati `toleransi_bayar_hari`, scheduler berikutnya langsung menandainya `Hangus` dan **pelanggan terkena pelanggaran akibat kelalaian kasir**.
14. **[MENENGAH — Produk & Layanan Soft-Deleted pada Input Manual POS / Booking]**:
    - `PembayaranKasirService::rekonsiliasiStokDanHitungProduk()` (baris 348) dan `KalkulatorKeranjangPosService::susunPerbandinganProduk()` (baris 180) memakai `Produk::withTrashed()`, dan kunci `produk_items` tidak divalidasi `exists` di `AturanKeranjangPos.php:23-24`, sehingga produk yang sudah diarsipkan Manajer masih bisa dijual lewat request manual POS.
    - `SimpanBookingRequest.php:49` dan `AturanKeranjangPos.php:20` (`exists:layanan,id_layanan`) belum menyaring `withoutTrashed()`.
15. **[RENDAH — Pembatalan oleh Pelanggan, `jumlah_kali_blokir` & Slot Jam Tanpa Layanan]**:
    - `PelangganController::batalkanBooking()` (`app/Http/Controllers/PelangganController.php:171`) belum memvalidasi `max:` pada `alasan_batal` sebelum disimpan ke `reservasi.alasan_penolakan` (`string(255)`).
    - Tahap blokir di `EvaluasiBookingHangus.php:138` dihitung dari `jumlah_kali_blokir` seumur hidup yang tidak direset baik oleh reset otomatis maupun `PelanggaranService::bukaBlokir()`.
    - Di `JadwalPetugasService::statusSlotJamOperasional()` (`app/Services/JadwalPetugasService.php:377-386`), cabang tanpa layanan membandingkan jumlah `$idSibuk` (bisa memuat petugas nonaktif) dengan jumlah petugas aktif.
16. **[MENENGAH — Keamanan / Integritas Hash Otorisasi POS] `kode_promo` Tidak Masuk dalam `PermintaanOtorisasi::hitungHashKeranjang()` & Signature JS**:
    - Di `PermintaanOtorisasi::hitungHashKeranjang()` (`app/Models/PermintaanOtorisasi.php:134-147`) dan `buatSignatureKeranjang()` (`resources/js/Karyawan/Kasir/pos-otorisasi.js:47-55`), hash hanya dihitung dari `id_reservasi`, `layanan`, dan `produk` — **tanpa menyertakan `kode_promo`**.
17. **[MENENGAH — Keamanan / IDOR Tiket Otorisasi] `id_otorisasi` Milik Kasir Lain atau Konteks Katalog Dapat Dikonsumsi Saat Checkout Normal / Mandiri**:
    - Di `OtorisasiPosService::siapkanOtorisasiSebelumBayar()` (`app/Services/OtorisasiPosService.php:129-144`), ketika `! $evaluasi['ada_perubahan']` atau `! $evaluasi['wajib_approval']`, method langsung mengembalikan `'tiket' => $tiket` **tanpa memvalidasi** `$tiket->id_kasir === $idKasir`, `$tiket->konteks === KONTEKS_POS_TRANSAKSI`, dan `$tiket->id_reservasi === $idReservasi`, lalu `selesaikanOtorisasiSetelahBayar()` (baris 166–169) menandainya `Digunakan`.
18. **[MENENGAH — Konsistensi Transaksi Hold POS vs Antrean Reservasi]**:
    - Di `KalkulatorKeranjangPosService::evaluasiKeranjang()` (`app/Services/KalkulatorKeranjangPosService.php:27-29`), reservasi diambil dengan `findOrFail()` tanpa memastikan reservasi belum dibayar dan masih berstatus aktif.
    - Di `PosController::index()` (`app/Http/Controllers/Kasir/PosController.php:46-51`), reservasi yang sedang di-Hold tetap muncul di `Pilih Reservasi Siap Bayar`; jika dibayar normal tanpa `id_otorisasi`, tiket Hold tetap menggantung (`sedang_dihold = true`).
    - Di `OtorisasiPosService::tutupTiketLamaYangMenggantung()` (`app/Services/OtorisasiPosService.php:297-314`), kueri tidak menutup tiket berstatus `Disetujui` dan langsung `return` untuk transaksi Walk-In (`! $idReservasi`).
19. **[MENENGAH — Halaman Persetujuan Manajer (`Manajer/persetujuan.blade.php`)] `$riwayatKeputusan` & `$produkAwal` Tidak Dirender**:
    - `PersetujuanOtorisasiController::index()` (`app/Http/Controllers/Manajer/PersetujuanOtorisasiController.php:31-35, 48`) mengirim `$riwayatKeputusan`, tetapi `resources/views/Manajer/persetujuan.blade.php` tidak pernah merendernya.
    - Di `resources/views/Manajer/persetujuan.blade.php:74-86`, kolom **"Kondisi Awal"** hanya melakukan loop pada `$item->layananAwalList()` dan tidak merender `$item->snapshot_awal['produk']`.
20. **[MENENGAH — Wizard Booking 3 Slide/Langkah (`Pelanggan/booking.blade.php` & `resources/js/Pelanggan/booking/*`)]**:
    - **Slide 1 & Slide 2 (Selisih Jam Selesai Client vs Server — `data-jeda` Diabaikan di Ringkasan Client)**: Checkbox layanan di `booking.blade.php` memiliki `data-jeda`, tetapi `resources/js/Pelanggan/booking/ringkasanUI.js:22-27, 134-138` hanya menjumlahkan `data-durasi` tanpa `data-jeda`.
    - **Slide 2 (Panel Menit Spesifik `:15`, `:30`, `:45` Mematikan Highlight Jam & Mengabaikan Status `.penuh`)**: Di `resources/js/Pelanggan/booking/jadwalAjax.js:19-29, 65, 140-153`, saat memilih menit selain `:00`, pencocokan `chip.dataset.jam === jamNilai` gagal sehingga tidak ada `.chip-jam` yang aktif, dan tombol `.btn-menit-cepat` tetap bisa diklik meskipun slot jam induknya berstatus `.penuh`.
    - **Slide 1, 2 & 3 (Reset Paksa ke Slide 1 Saat Error Server, Race Condition Fetch Petugas, & Input Qty Add-On `0` Saat Hapus Angka)**: `let stepAktif = 1;` (`booking.js`) selalu mengembalikan user ke Slide 1 saat validasi server gagal di Slide 2/3; `muatPetugasTersedia()` (`jadwalAjax.js:31-118`) belum memakai `AbortController`; serta event `input` pada `.input-qty-addon` di Slide 3 (`booking.js` & `ringkasanUI.js:76-82`) langsung memaksa nilai menjadi `0` ketika pelanggan menekan Backspace untuk mengetik angka baru.

### D. Riwayat Perbaikan Selesai

- **[SELESAI DI RANGKAIAN COMMIT `535077e`..`53a9951` + Working Tree — Proteksi Staf Nonaktif, Validasi Jam Hari Ini, Hardening Laporan/CSV, Cek Bentrok Walk-In, Sinkronisasi README, Skrip Portabel `mulai-publik.bat` & Perbaikan CTA/Stepper Booking]**:
    - **Perbaikan Regresi CSS `[hidden]` & Penguncian Tab Stepper Atas Booking (`resources/css/base/reset.css:6-8`, `resources/css/pages/pelanggan/booking.css:342-350`, `resources/js/Pelanggan/booking.js`, `resources/js/Pelanggan/booking/ringkasanUI.js`)**: Menambahkan `[hidden] { display: none !important; }` di reset global agar `#btnLanjutStepRingkasan` tersembunyi di Step 3 (Add-On) dan `#submitBtn` tersembunyi di Step 1–2; mengunci `#tabStep2` saat `jumlahLayanan === 0` dan `#tabStep3` saat `!siap` beserta auto-kembali ke Step 1 jika seluruh layanan dihapus.
    - **Portabilitas & Pengecekan Prasyarat `mulai-publik.bat` (`53a9951`)**: Validasi otomatis keberadaan `.env`, `APP_KEY`, `vendor/autoload.php`, dan `public/build/manifest.json`; auto-deteksi lokasi binary `php.exe`, `mysqld.exe`, dan `cloudflared.exe` (PATH, Laragon `C:`/`D:`, XAMPP, Program Files, WinGet); serta penambahan opsi `--cek`.
    - **Blokir Login & Pencabutan Sesi Staf Nonaktif (`535077e`)**: Penambahan `Karyawan::sedangAktifBertugas()` serta pemeriksaan di `AuthController` (`login`, `portalStaf`, `showLogin`, `showRegister`), middleware `CekPeran::handle()`, dan rute `/dashboard` disertai penghangusan izin katalog 1x pakai saat sesi dicabut.
    - **Penolakan Jam Kedatangan Hari Ini yang Sudah Lewat (`5fe0fc1`)**: Validasi waktu server di `SimpanBookingRequest`, penandaan slot jam lampau di `JadwalPetugasService::statusSlotJamOperasional()`, serta pemilihan slot default mendatang di `PelangganController::booking()`.
    - **Keamanan `LaporanService` & Penyeragaman Nomor `#INV` (`9392c11`)**: Validasi format & urutan tanggal di `LaporanService::rentangTanggal()`, pemfilteran `$layananTerlaris` berdasarkan rentang `tgl_booking` & status `Selesai`, sanitasi *CSV formula injection* (`amankanSelCsv()`), serta penyeragaman format `#INV-{id_reservasi 4 digit}` di flash POS, struk cetak, laporan, dan `Manajer/persetujuan.blade.php`.
    - **Pemeriksaan Bentrok Jadwal Petugas pada Transaksi Walk-In & Layanan Tambahan POS (`1fcfb61`)**: `PembayaranKasirService::validasiPetugasLayananFinal()` kini selalu mengecek jadwal bentrok ketika `$idAwal === null` maupun saat berganti petugas.
    - **Sinkronisasi Dokumentasi `README.md` (`e65b206`)**: Penyesuaian keterangan versi Laravel 13, PHP >= 8.3, `npm run build`, SQLite `:memory:`, dan Laravel Pint.

- **[SELESAI DI RANGKAIAN COMMIT `b0d7d69`..`9c1dc12` — Optimasi Mobile, Setup Docker/Deploy, Perbaikan Audit Frontend P0–P3 & Konsolidasi CSS 21 Berkas]**:
    - **Setup Deploy & `.env.example` Lengkap (`b0d7d69`)**: Penambahan `Dockerfile`, `docker/entrypoint.sh`, `.dockerignore`, `mulai-publik.bat`, `trustProxies('*')`, serta kelengkapan variabel `.env.example`.
    - **Perbaikan Frontend P0 (`aa51458`)**: Penambahan spesifikasi `DESIGN.md`, normalisasi kapitalisasi path `Pelanggan` untuk Linux *case-sensitive*, konversi halaman metode pembayaran Kasir menjadi kartu baca-saja (menghapus `Kasir/metode.js` palsu), komponen paginasi kustom `<x-paginasi>`, dan `FrontendP0PerbaikanTest`.
    - **Perbaikan Frontend P1 (`1e440d2`)**: Perbaikan rasio kontras WCAG AA, atribut ARIA dialog modal, modul bersama `resources/js/components/staf-ui.js`, dukungan `data-confirm-label` di `confirm-modal.js`, penghapusan listener mati di `Manajer/laporan.js` & `Karyawan/Kasir/laporan.js`, serta `FrontendP1PerbaikanTest`.
    - **Perbaikan Frontend P2 (`ac35448`)**: Navigasi mobile drawer beranda publik (`Publik/beranda.js`), perbaikan landmark semantik (`<main>`, `<h1>`, `<aside>`), hierarki heading, modul keyboard `aria-widget.js`, serta `FrontendP2PerbaikanTest`.
    - **Perbaikan Frontend P3 & Konsolidasi CSS 21 Berkas (`e783e34`..`9c1dc12`)**: Komponen `<x-ikon>` & `ikon-svg.js` (bebas emoji UI & em-dash), `struk-preview.js`, `tabular-nums`, aset `favicon.svg`/`favicon.ico`/`og-salon.jpg`, penghapusan dead views (`welcome`, `PlaceHolder`, `Karyawan/beranda`), konsolidasi 38 berkas CSS lama menjadi 21 berkas modular (`base/`, `components/`, `layouts/`, `pages/`), serta `FrontendP3PerbaikanTest`.

- **[SELESAI DI RANGKAIAN COMMIT `a74d2f6`..`f68e93c` — Pengerasan Konkurensi, Evaluasi Slot Berurutan, Penyatuan View Petugas & Refactor Modular JS/CSS]**:
    - **Perbaikan Endpoint AJAX Slot Booking Multi-Layanan**: `PelangganController::petugasTersedia()` mendelegasikan perhitungan ke `JadwalPetugasService::petugasTersediaUntukPilihan()`.
    - **Perbaikan Syarat Layanan Selesai Sebelum Checkout POS & Alih Petugas Antrean**: `Reservasi::scopeSiapBayarKasir()` & `PembayaranKasirService::ambilDanValidasiReservasi()` mewajibkan seluruh `detail_reservasi.status_layanan === 'Selesai'`; penambahan fitur ganti petugas antrean (`Kasir\AntreanController::gantiPetugas()`).
    - **Perbaikan Sinkronisasi `detail_reservasi.status_layanan` & Row Lock Batal Booking**: `Reservasi::ubahStatus()` menyinkronkan `detail_reservasi.status_layanan`; `PelangganController::batalkanBooking()` memakai `DB::transaction()` + `lockForUpdate()`.
    - **Perbaikan Portal Petugas & Proteksi Nonaktifkan/Pindah Jabatan Karyawan**: Penyatuan view Stylist & Therapist ke `resources/views/Karyawan/Petugas/{layout,jadwal,komisi}.blade.php` dan pemblokiran penonaktifan/pindah jabatan karyawan yang masih memiliki booking aktif di `Manajer\KaryawanController::update()`.

- **[SELESAI DI RANGKAIAN COMMIT `bc95ccd`..`e92bccb` — Refactor SRP Kasir, Otorisasi POS/Katalog, Cetak Struk Kasir, Pelanggan Walk-In & Wizard Booking 3 Langkah]**
