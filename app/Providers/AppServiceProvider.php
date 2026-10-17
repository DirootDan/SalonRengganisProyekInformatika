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
        // Link reset password: pelanggan dan karyawan punya halaman berbeda
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $rute = $user instanceof Pelanggan
                ? 'pelanggan.password.reset'
                : 'password.reset';

            return route($rute, [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}