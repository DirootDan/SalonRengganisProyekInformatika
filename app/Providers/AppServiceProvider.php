<?php

namespace App\Providers;

use App\Models\Pelanggan;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Link reset password gabungan untuk pelanggan maupun karyawan
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return route('password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}