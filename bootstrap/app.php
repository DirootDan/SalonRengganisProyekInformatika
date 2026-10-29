<?php

use App\Http\Middleware\CekBlokirBooking;
use App\Http\Middleware\CekPeran;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'peran' => CekPeran::class,
            'cek.blokir' => CekBlokirBooking::class,
        ]);

        // Kalau belum login: pelanggan ke login pelanggan, sisanya ke login karyawan
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('pelanggan/*') ? '/pelanggan/login' : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
