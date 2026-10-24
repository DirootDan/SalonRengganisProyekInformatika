<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PelangganController;
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
// Otomatis ke halaman sesuai jabatan
Route::middleware('auth:karyawan')->get('/dashboard', function () {
    $rute = Auth::guard('karyawan')->user()->rutePeran();
    return redirect()->route($rute ?? 'login');
})->name('dashboard');

// Area Manajer
Route::middleware(['auth:karyawan', 'peran:Manajer'])
    ->prefix('manajer')->name('manajer.')->group(function () {
        Route::get('/dashboard', fn () => view('Karyawan.PlaceHolder', [
            'judul' => 'Dashboard Manajer',
            'user'  => Auth::guard('karyawan')->user(),
        ]))->name('dashboard');
    });

// Area Kasir (Manajer juga boleh masuk)
Route::middleware(['auth:karyawan', 'peran:Kasir,Manajer'])
    ->prefix('kasir')->name('kasir.')->group(function () {
        // PERBAIKAN: Mengarah ke Karyawan.PlaceHolder
        Route::get('/dashboard', fn () => view('Karyawan.PlaceHolder', [
            'judul' => 'Dashboard Kasir',
            'user'  => Auth::guard('karyawan')->user(),
        ]))->name('dashboard');
    });

// Area Stylist
Route::middleware(['auth:karyawan', 'peran:Stylist'])
    ->prefix('stylist')->name('stylist.')->group(function () {
        // PERBAIKAN: Mengarah ke Karyawan.PlaceHolder
        Route::get('/dashboard', fn () => view('Karyawan.PlaceHolder', [
            'judul' => 'Dashboard Stylist',
            'user'  => Auth::guard('karyawan')->user(),
        ]))->name('dashboard');
    });

/* ===== AREA PELANGGAN ===== */
// Hanya berisi rute yang membutuhkan login pelanggan
Route::prefix('pelanggan')->name('pelanggan.')->middleware('auth:pelanggan')->group(function () {
    Route::get('/beranda', [PelangganController::class, 'beranda'])->name('beranda');
    Route::get('/layanan', [PelangganController::class, 'layanan'])->name('layanan'); // Rute Baru!
    Route::get('/booking', [PelangganController::class, 'booking'])->name('booking');
    Route::post('/booking', [PelangganController::class, 'simpanBooking'])->name('booking.simpan');
    Route::get('/riwayat', [PelangganController::class, 'riwayat'])->name('riwayat');
    Route::get('/profil', [PelangganController::class, 'profil'])->name('profil');
    Route::put('/profil', [PelangganController::class, 'updateProfil'])->name('profil.update');
    Route::put('/profil/password', [PelangganController::class, 'updatePassword'])->name('profil.password');
});