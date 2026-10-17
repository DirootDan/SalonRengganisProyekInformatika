<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth:karyawan')->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::guard('karyawan')->user();

        return 'Login berhasil! Halo, ' . $user->nama_staff . ' (' . $user->role_jabatan . ')'
            . '<form method="POST" action="' . route('logout') . '">'
            . csrf_field()
            . '<button type="submit">Logout</button></form>';
    })->name('dashboard');
});