<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /* ================= LOGIN KARYAWAN ================= */

    public function showLogin()
    {
        $user = Auth::guard('karyawan')->user();

        if ($user && $user->rutePeran()) {
            return redirect()->route($user->rutePeran());
        }

        return view('karyawan.loginpage');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], $this->pesan(), $this->atribut());

        if (Auth::guard('karyawan')->attempt($credentials)) {
            $user = Auth::guard('karyawan')->user();

            // Jabatan tidak dikenali -> tolak
            if (! $user->rutePeran()) {
                Auth::guard('karyawan')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['username' => 'Jabatan akun ini belum dikenali. Hubungi manajer.'])
                    ->onlyInput('username');
            }

            $request->session()->regenerate();

            return redirect()->intended(route($user->rutePeran()));
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::guard('karyawan')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /* ================= LUPA PASSWORD ================= */

    public function showLupaPassword()
    {
        return view('karyawan.lupapassword');
    }

    public function kirimLinkReset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], $this->pesan(), $this->atribut());

        $status = Password::broker('karyawan')->sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password sudah dikirim ke email Anda.');
        }

        $pesan = match ($status) {
            Password::RESET_THROTTLED => 'Terlalu sering mencoba. Tunggu sebentar lalu coba lagi.',
            default                   => 'Email tidak terdaftar.',
        };

        return back()->withErrors(['email' => $pesan])->onlyInput('email');
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('karyawan.resetpassword', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], $this->pesan(), $this->atribut());

        $status = Password::broker('karyawan')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Karyawan $karyawan, string $password) {
                $karyawan->forceFill(['password' => $password]);
                $karyawan->setRememberToken(Str::random(60));
                $karyawan->save();

                event(new PasswordReset($karyawan));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Password berhasil diganti. Silakan login.');
        }

        return back()
            ->withErrors(['email' => 'Link reset tidak valid atau sudah kedaluwarsa.'])
            ->onlyInput('email');
    }

    /* ================= BANTUAN ================= */

    private function pesan(): array
    {
        return [
            'required'   => ':attribute wajib diisi.',
            'email'      => 'Format email tidak valid.',
            'min'        => ':attribute minimal :min karakter.',
            'max'        => ':attribute maksimal :max karakter.',
            'unique'     => ':attribute sudah dipakai.',
            'confirmed'  => 'Konfirmasi password tidak sama.',
            'alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan underscore.',
        ];
    }

    private function atribut(): array
    {
        return [
            'email'    => 'Email',
            'username' => 'Username',
            'password' => 'Password',
        ];
    }
}