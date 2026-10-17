<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PelangganAuthController;
use App\Http\Controllers\PelangganController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

/* ===== Login karyawan ===== */
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/* ===== Lupa password karyawan ===== */
Route::get('/lupa-password', [AuthController::class, 'showLupaPassword'])->name('password.lupa');
Route::post('/lupa-password', [AuthController::class, 'kirimLinkReset'])
    ->middleware('throttle:5,1')
    ->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

/* ===== /dashboard: otomatis ke halaman sesuai jabatan ===== */
Route::middleware('auth:karyawan')->get('/dashboard', function () {
    $rute = Auth::guard('karyawan')->user()->rutePeran();

    return redirect()->route($rute ?? 'login');
})->name('dashboard');

/* ===== Area Manajer ===== */
Route::middleware(['auth:karyawan', 'peran:Manajer'])
    ->prefix('manajer')->name('manajer.')->group(function () {
        Route::get('/dashboard', fn () => view('placeholder', [
            'judul' => 'Dashboard Manajer',
            'user'  => Auth::guard('karyawan')->user(),
        ]))->name('dashboard');
    });

/* ===== Area Kasir (Manajer juga boleh masuk) ===== */
Route::middleware(['auth:karyawan', 'peran:Kasir,Manajer'])
    ->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('/dashboard', fn () => view('placeholder', [
            'judul' => 'Dashboard Kasir',
            'user'  => Auth::guard('karyawan')->user(),
        ]))->name('dashboard');
    });

/* ===== Area Stylist ===== */
Route::middleware(['auth:karyawan', 'peran:Stylist'])
    ->prefix('stylist')->name('stylist.')->group(function () {
        Route::get('/dashboard', fn () => view('placeholder', [
            'judul' => 'Dashboard Stylist',
            'user'  => Auth::guard('karyawan')->user(),
        ]))->name('dashboard');
    });

/* ===== Area Pelanggan ===== */
Route::prefix('pelanggan')->name('pelanggan.')->group(function () {

    // Belum login
    Route::get('/login', [PelangganAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PelangganAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.proses');
    Route::get('/register', [PelangganAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [PelangganAuthController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('register.proses');

    // Lupa password
    Route::get('/lupa-password', [PelangganAuthController::class, 'showLupaPassword'])->name('password.lupa');
    Route::post('/lupa-password', [PelangganAuthController::class, 'kirimLinkReset'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PelangganAuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [PelangganAuthController::class, 'resetPassword'])->name('password.update');

    // Sudah login
    Route::middleware('auth:pelanggan')->group(function () {
        Route::post('/logout', [PelangganAuthController::class, 'logout'])->name('logout');

        Route::get('/beranda', [PelangganController::class, 'beranda'])->name('beranda');
        Route::get('/booking', [PelangganController::class, 'booking'])->name('booking');
        Route::post('/booking', [PelangganController::class, 'simpanBooking'])->name('booking.simpan');
        Route::get('/riwayat', [PelangganController::class, 'riwayat'])->name('riwayat');
    });
});
