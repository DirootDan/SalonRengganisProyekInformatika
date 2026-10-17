<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

// Login & logout
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Buat akun baru
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.proses');

// Lupa password (kirim link ke email)
Route::get('/lupa-password', [AuthController::class, 'showLupaPassword'])->name('password.lupa');
Route::post('/lupa-password', [AuthController::class, 'kirimLinkReset'])
    ->middleware('throttle:5,1')
    ->name('password.email');

// Reset password (dari link di email)
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Halaman setelah login
Route::middleware('auth:karyawan')->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::guard('karyawan')->user();

        return 'Login berhasil! Halo, ' . $user->nama_staff . ' (' . $user->role_jabatan . ')'
            . '<form method="POST" action="' . route('logout') . '">'
            . csrf_field()
            . '<button type="submit">Logout</button></form>';
    })->name('dashboard');
});