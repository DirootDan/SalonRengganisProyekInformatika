<?php

namespace App\Http\Middleware;

use App\Services\OtorisasiPosService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CekPeran
{
    public function handle(Request $request, Closure $next, string ...$peran): Response
    {
        // 1. Cek apakah yang akses sama sekali bukan karyawan
        if (! Auth::guard('karyawan')->check()) {
            return redirect()->route('login')->withErrors(['login_id' => 'Silakan login sebagai karyawan untuk mengakses halaman ini.']);
        }

        $user = Auth::guard('karyawan')->user();

        // 2. Pastikan akun staf masih aktif dan tidak diarsipkan (soft deleted)
        if (! $user || ! $user->sedangAktifBertugas()) {
            if ($user) {
                app(OtorisasiPosService::class)->hanguskanIzinKatalogSaatLogout((int) $user->id_karyawan);
            }

            Auth::guard('karyawan')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['login_id' => 'Akun staf Anda sudah dinonaktifkan. Silakan hubungi manajer.']);
        }

        // 3. Cek apakah jabatannya sesuai dengan peran yang diizinkan
        if (! in_array($user->role_jabatan, $peran, true)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
