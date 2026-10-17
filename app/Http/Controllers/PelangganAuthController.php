<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PelangganAuthController extends Controller
{
    /* ================= LOGIN ================= */

    public function showLogin()
    {
        if (Auth::guard('pelanggan')->check()) {
            return redirect()->route('pelanggan.beranda');
        }

        return view('pelanggan.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], $this->pesan(), $this->atribut());

        if (Auth::guard('pelanggan')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('pelanggan.beranda'));
        }

        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->onlyInput('email');
    }

    /* ================= DAFTAR ================= */

    public function showRegister()
    {
        if (Auth::guard('pelanggan')->check()) {
            return redirect()->route('pelanggan.beranda');
        }

        return view('pelanggan.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'nama_cust' => ['required', 'string', 'max:100'],
            'no_telp'   => ['required', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email'     => ['required', 'email', 'max:100', 'unique:pelanggan,email'],
            'password'  => ['required', 'string', 'min:6', 'confirmed'],
        ], $this->pesan(), $this->atribut());

        $pelanggan = Pelanggan::create($data); // password di-hash otomatis oleh model

        Auth::guard('pelanggan')->login($pelanggan);
        $request->session()->regenerate();

        return redirect()->route('pelanggan.beranda')
            ->with('status', 'Akun berhasil dibuat. Selamat datang!');
    }

    /* ================= LOGOUT ================= */

    public function logout(Request $request)
    {
        Auth::guard('pelanggan')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pelanggan.login');
    }

    /* ================= LUPA PASSWORD ================= */

    public function showLupaPassword()
    {
        return view('pelanggan.lupapassword');
    }

    public function kirimLinkReset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], $this->pesan(), $this->atribut());

        $status = Password::broker('pelanggan')->sendResetLink(
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
        return view('pelanggan.resetpassword', [
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

        $status = Password::broker('pelanggan')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Pelanggan $pelanggan, string $password) {
                $pelanggan->forceFill(['password' => $password]);
                $pelanggan->setRememberToken(Str::random(60));
                $pelanggan->save();

                event(new PasswordReset($pelanggan));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('pelanggan.login')
                ->with('status', 'Password berhasil diganti. Silakan login.');
        }

        return back()
            ->withErrors(['email' => 'Link reset tidak valid atau sudah kedaluwarsa.'])
            ->onlyInput('email');
    }

   

    private function pesan(): array
    {
        return [
            'required'  => ':attribute wajib diisi.',
            'email'     => 'Format email tidak valid.',
            'min'       => ':attribute minimal :min karakter.',
            'max'       => ':attribute maksimal :max karakter.',
            'unique'    => ':attribute sudah terdaftar.',
            'confirmed' => 'Konfirmasi password tidak sama.',
            'regex'     => 'Format :attribute tidak valid.',
        ];
    }

    private function atribut(): array
    {
        return [
            'nama_cust' => 'Nama lengkap',
            'no_telp'   => 'No. telepon',
            'email'     => 'Email',
            'password'  => 'Password',
        ];
    }
}