<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KasirController;
use App\Http\Controllers\ManajerController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PetugasController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

/* ===== AUTENTIKASI GABUNGAN (Login, Register, Logout) ===== */
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.proses');

/* ===== LUPA & RESET PASSWORD GABUNGAN ===== */
Route::get('/lupa-password', [AuthController::class, 'showLupaPassword'])->name('password.lupa');
Route::post('/lupa-password', [AuthController::class, 'kirimLinkReset'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

/* ===== AREA KARYAWAN ===== */
Route::middleware('auth:karyawan')->get('/dashboard', function () {
    $rute = Auth::guard('karyawan')->user()->rutePeran();
    return redirect()->route($rute ?? 'login');
})->name('dashboard');

// MODUL 2: Area Manajer (Murni Manajer, tanpa mode kasir)
Route::middleware(['auth:karyawan', 'peran:Manajer'])
    ->prefix('manajer')->name('manajer.')->group(function () {
        Route::get('/dashboard', [ManajerController::class, 'dashboard'])->name('dashboard');

        // CRUD Karyawan
        Route::get('/karyawan', [ManajerController::class, 'karyawan'])->name('karyawan');
        Route::post('/karyawan', [ManajerController::class, 'simpanKaryawan'])->name('karyawan.simpan');
        Route::put('/karyawan/{id}', [ManajerController::class, 'updateKaryawan'])->name('karyawan.update');
        Route::delete('/karyawan/{id}', [ManajerController::class, 'hapusKaryawan'])->name('karyawan.hapus');

        // CRUD Layanan
        Route::get('/layanan', [ManajerController::class, 'layanan'])->name('layanan');
        Route::post('/layanan', [ManajerController::class, 'simpanLayanan'])->name('layanan.simpan');
        Route::put('/layanan/{id}', [ManajerController::class, 'updateLayanan'])->name('layanan.update');
        Route::delete('/layanan/{id}', [ManajerController::class, 'hapusLayanan'])->name('layanan.hapus');

        // CRUD Produk
        Route::get('/produk', [ManajerController::class, 'produk'])->name('produk');
        Route::post('/produk', [ManajerController::class, 'simpanProduk'])->name('produk.simpan');
        Route::put('/produk/{id}', [ManajerController::class, 'updateProduk'])->name('produk.update');
        Route::delete('/produk/{id}', [ManajerController::class, 'hapusProduk'])->name('produk.hapus');

        // CRUD Promo
        Route::get('/promo', [ManajerController::class, 'promo'])->name('promo');
        Route::post('/promo', [ManajerController::class, 'simpanPromo'])->name('promo.simpan');
        Route::put('/promo/{id}', [ManajerController::class, 'updatePromo'])->name('promo.update');
        Route::delete('/promo/{id}', [ManajerController::class, 'hapusPromo'])->name('promo.hapus');

        // Laporan & Ekspor
        Route::get('/laporan', [ManajerController::class, 'laporan'])->name('laporan');
        Route::get('/laporan/ekspor', [ManajerController::class, 'eksporLaporanCsv'])->name('laporan.ekspor');

        // Metode Pembayaran & Struk Nota
        Route::get('/metode-pembayaran', [ManajerController::class, 'metodePembayaran'])->name('metode');
        Route::post('/metode-pembayaran', [ManajerController::class, 'simpanMetode'])->name('metode.simpan');
        Route::put('/metode-pembayaran/{id}', [ManajerController::class, 'updateMetode'])->name('metode.update');
        Route::patch('/metode-pembayaran/{id}/toggle', [ManajerController::class, 'toggleMetode'])->name('metode.toggle');

        Route::get('/struk', [ManajerController::class, 'struk'])->name('struk');
        Route::put('/struk', [ManajerController::class, 'simpanStruk'])->name('struk.simpan');

        // Pengaturan Kebijakan Booking & Buka Blokir Pelanggan
        Route::get('/kebijakan-booking', [ManajerController::class, 'kebijakan'])->name('kebijakan');
        Route::put('/kebijakan-booking', [ManajerController::class, 'simpanKebijakan'])->name('kebijakan.simpan');
        Route::post('/kebijakan-booking/buka-blokir/{idPelanggan}', [ManajerController::class, 'bukaBlokirPelanggan'])->name('kebijakan.buka');
    });

// MODUL 3: Area Kasir 
Route::middleware(['auth:karyawan', 'peran:Kasir'])
    ->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/dashboard', [KasirController::class, 'dashboard'])->name('dashboard');
        Route::post('/transaksi/bayar', [KasirController::class, 'prosesBayar'])->name('bayar');

        // Menu terpisah: Permintaan Booking & Antrean Hari Ini
        Route::get('/permintaan-booking', [KasirController::class, 'permintaanBooking'])->name('permintaan');
        Route::post('/permintaan-booking/{idReservasi}/terima', [KasirController::class, 'terimaBooking'])->name('permintaan.terima');
        Route::post('/permintaan-booking/{idReservasi}/tolak', [KasirController::class, 'tolakBooking'])->name('permintaan.tolak');

        Route::get('/antrean-hari-ini', [KasirController::class, 'antreanHariIni'])->name('antrean');

        Route::get('/layanan', [KasirController::class, 'layanan'])->name('layanan');
        Route::post('/layanan', [KasirController::class, 'storeLayanan'])->name('layanan.store');
        Route::put('/layanan/{id}', [KasirController::class, 'updateLayanan'])->name('layanan.update');
        Route::delete('/layanan/{id}', [KasirController::class, 'destroyLayanan'])->name('layanan.destroy');

        Route::get('/produk', [KasirController::class, 'produk'])->name('produk');
        Route::post('/produk', [KasirController::class, 'storeProduk'])->name('produk.store');
        Route::put('/produk/{id}', [KasirController::class, 'updateProduk'])->name('produk.update');
        Route::delete('/produk/{id}', [KasirController::class, 'destroyProduk'])->name('produk.destroy');

        Route::get('/laporan', [KasirController::class, 'laporan'])->name('laporan');
        Route::get('/metode-pembayaran', [KasirController::class, 'metodePembayaran'])->name('metode');
        Route::get('/struk', [KasirController::class, 'struk'])->name('struk');
    });

// MODUL 4A: Area Stylist
Route::middleware(['auth:karyawan', 'peran:Stylist'])
    ->prefix('stylist')->name('stylist.')->group(function () {
        Route::get('/dashboard', [PetugasController::class, 'jadwalStylist'])->name('dashboard');
        Route::post('/jadwal/{idReservasi}/layanan/{idLayanan}/selesai', [PetugasController::class, 'tandaiLayananSelesai'])->name('layanan.selesai');
        Route::get('/komisi', [PetugasController::class, 'komisiStylist'])->name('komisi');
    });

// MODUL 4B: Area Therapist
Route::middleware(['auth:karyawan', 'peran:Therapist'])
    ->prefix('therapist')->name('therapist.')->group(function () {
        Route::get('/dashboard', [PetugasController::class, 'jadwalTherapist'])->name('dashboard');
        Route::post('/jadwal/{idReservasi}/layanan/{idLayanan}/selesai', [PetugasController::class, 'tandaiLayananSelesai'])->name('layanan.selesai');
        Route::get('/komisi', [PetugasController::class, 'komisiTherapist'])->name('komisi');
    });

/* ===== MODUL 1: AREA PELANGGAN ===== */
Route::prefix('pelanggan')->name('pelanggan.')->middleware('auth:pelanggan')->group(function () {
    Route::get('/beranda', [PelangganController::class, 'beranda'])->name('beranda');
    Route::get('/layanan', [PelangganController::class, 'layanan'])->name('layanan');

    // Rute booking dilindungi middleware cek.blokir (sisi server)
    Route::middleware('cek.blokir')->group(function () {
        Route::get('/booking', [PelangganController::class, 'booking'])->name('booking');
        Route::get('/booking/petugas-tersedia', [PelangganController::class, 'petugasTersedia'])->name('booking.petugas');
        Route::post('/booking', [PelangganController::class, 'simpanBooking'])->name('booking.simpan');
    });

    Route::post('/booking/{idReservasi}/batal', [PelangganController::class, 'batalkanBooking'])->name('booking.batal');
    Route::get('/riwayat', [PelangganController::class, 'riwayat'])->name('riwayat');
    Route::post('/notifikasi/baca', [PelangganController::class, 'bacaNotifikasi'])->name('notifikasi.baca');

    Route::get('/profil', [PelangganController::class, 'profil'])->name('profil');
    Route::put('/profil', [PelangganController::class, 'updateProfil'])->name('profil.update');
    Route::put('/profil/password', [PelangganController::class, 'updatePassword'])->name('profil.password');

    // Foto profil
    Route::post('/profil/foto', [PelangganController::class, 'updateFoto'])->name('profil.foto');
    Route::delete('/profil/foto', [PelangganController::class, 'hapusFoto'])->name('profil.foto.hapus');
});
