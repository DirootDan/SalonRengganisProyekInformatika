<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'peran' => \App\Http\Middleware\CekPeran::class,
    ]);

    // Kalau belum login: pelanggan ke login pelanggan, sisanya ke login karyawan
    $middleware->redirectGuestsTo(fn (\Illuminate\Http\Request $request) =>
        $request->is('pelanggan/*') ? '/pelanggan/login' : route('login')
    );
})
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
