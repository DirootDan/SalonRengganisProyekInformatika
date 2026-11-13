<?php

namespace App\Providers;

use App\Models\NotifikasiPelanggan;
use App\Models\PermintaanOtorisasi;
use App\Models\Reservasi;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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

        View::composer('Manajer.layout', function ($view): void {
            $view->with('jumlahPendingOtorisasi', PermintaanOtorisasi::menungguPersetujuan()->count());
        });

        View::composer('Karyawan.Kasir.layout', function ($view): void {
            $view->with([
                'notifMenunggu' => Reservasi::menungguKonfirmasi()->count(),
                'notifAntrean' => Reservasi::siapBayarKasir()->count(),
            ]);
        });

        View::composer(['Pelanggan.layout', 'pelanggan.layout'], function ($view): void {
            $pelangganAktif = Auth::guard('pelanggan')->user();
            $jmlNotifBelumDibaca = $pelangganAktif
                ? NotifikasiPelanggan::where('id_pelanggan', $pelangganAktif->id_pelanggan)->where('dibaca', false)->count()
                : 0;

            $view->with([
                'pelangganAktif' => $pelangganAktif,
                'jmlNotifBelumDibaca' => $jmlNotifBelumDibaca,
                'sedangDiProfil' => request()->routeIs('pelanggan.profil*'),
            ]);
        });
    }
}
