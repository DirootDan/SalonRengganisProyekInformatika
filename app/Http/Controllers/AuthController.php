<?php

namespace App\Http\Controllers;

use App\Models\Karyawan;
use App\Models\Pelanggan;
use App\Services\OtorisasiPosService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public const COOKIE_PERANGKAT_STAF = 'perangkat_staf_salon';

    private const DURASI_COOKIE_STAF_MENIT = 60 * 24 * 30;

    /* ================= TAMPILAN HALAMAN ================= */

    public function portalStaf()
    {
        if (Auth::guard('karyawan')->check()) {
            $user = Auth::guard('karyawan')->user();

            return redirect()->route($user->rutePeran() ?? 'login');
        }

        return redirect()->route('login', ['mode' => 'staf']);
    }

    public function showLogin(Request $request)
    {
        if (Auth::guard('karyawan')->check()) {
            $user = Auth::guard('karyawan')->user();

            return redirect()->route($user->rutePeran() ?? 'login');
        }

        if (Auth::guard('pelanggan')->check()) {
            return redirect()->route('pelanggan.beranda');
        }

        $modeQuery = $request->query('mode');
        $perangkatStaf = $request->cookie(self::COOKIE_PERANGKAT_STAF) === '1';
        $modeStaf = $modeQuery === 'staf' || ($modeQuery !== 'pelanggan' && $perangkatStaf);

        // Disesuaikan dengan folder Auth dan file loginpage.blade.php
        $respons = response()->view('Auth.loginpage', [
            'modeStaf' => $modeStaf,
        ]);

        if ($modeQuery === 'staf') {
            $respons->withCookie(cookie(self::COOKIE_PERANGKAT_STAF, '1', self::DURASI_COOKIE_STAF_MENIT));
        }

        return $respons;
    }

    public function showRegister(Request $request)
    {
        if (Auth::guard('pelanggan')->check() || Auth::guard('karyawan')->check()) {
            return redirect('/');
        }

        if ($request->integer('layanan') > 0) {
            session(['url.intended' => route('pelanggan.booking', ['layanan' => $request->integer('layanan')])]);
        }

        // Disesuaikan dengan folder Auth dan file registerpage.blade.php
        return view('Auth.registerpage');
    }

    public function showLupaPassword()
    {
        // Disesuaikan dengan folder Auth dan file lupapassword.blade.php
        return view('Auth.lupapassword');
    }

    public function showResetPassword(Request $request, string $token)
    {
        // Disesuaikan dengan folder Auth dan file resetpassword.blade.php
        return view('Auth.resetpassword', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /* ================= LOGIN GABUNGAN ================= */

    public function login(Request $request)
    {
        $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], $this->pesan(), $this->atribut());

        $loginId = $request->input('login_id');
        $password = $request->input('password');

        if (Auth::guard('karyawan')->attempt(['username' => $loginId, 'password' => $password])) {
            $user = Auth::guard('karyawan')->user();

            if (! $user->rutePeran()) {
                Auth::guard('karyawan')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['login_id' => 'Jabatan akun ini belum dikenali. Hubungi manajer.'])
                    ->onlyInput('login_id');
            }

            $request->session()->regenerate();

            return redirect()->intended(route($user->rutePeran()))
                ->withCookie(cookie(self::COOKIE_PERANGKAT_STAF, '1', self::DURASI_COOKIE_STAF_MENIT));
        }

        if (Auth::guard('pelanggan')->attempt(['email' => $loginId, 'password' => $password])) {
            $request->session()->regenerate();

            return redirect()->intended(route('pelanggan.beranda'));
        }

        return back()
            ->withErrors(['login_id' => 'Username/Email atau password salah.'])
            ->onlyInput('login_id');
    }

    /* ================= REGISTER PELANGGAN ================= */

    public function register(Request $request)
    {
        $data = $request->validate([
            'nama_cust' => ['required', 'string', 'max:100'],
            'no_telp' => ['required', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'email' => ['required', 'email', 'max:100', 'unique:pelanggan,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], $this->pesan(), $this->atribut());

        $pelanggan = Pelanggan::create($data);

        Auth::guard('pelanggan')->login($pelanggan);
        $request->session()->regenerate();

        return redirect()->intended(route('pelanggan.booking'))
            ->with('status', 'Akun berhasil dibuat. Silakan pilih jadwal perawatan Anda!');
    }

    /* ================= LOGOUT GABUNGAN ================= */

    public function logout(Request $request, OtorisasiPosService $otorisasiPosService)
    {
        if (Auth::guard('karyawan')->check()) {
            $idKaryawan = (int) Auth::guard('karyawan')->id();
            $otorisasiPosService->hanguskanIzinKatalogSaatLogout($idKaryawan);
            Auth::guard('karyawan')->logout();
        }

        if (Auth::guard('pelanggan')->check()) {
            Auth::guard('pelanggan')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /* ================= LUPA & RESET PASSWORD GABUNGAN ================= */

    public function kirimLinkReset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], $this->pesan(), $this->atribut());

        $email = $request->input('email');

        $broker = null;
        if (Karyawan::where('email', $email)->exists()) {
            $broker = 'karyawan';
        } elseif (Pelanggan::where('email', $email)->exists()) {
            $broker = 'pelanggan';
        } else {
            return back()->withErrors(['email' => 'Email tidak terdaftar.'])->onlyInput('email');
        }

        $status = Password::broker($broker)->sendResetLink(['email' => $email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', 'Link reset password sudah dikirim ke email Anda.');
        }

        return back()->withErrors(['email' => 'Terlalu sering mencoba. Tunggu sebentar.'])->onlyInput('email');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], $this->pesan(), $this->atribut());

        $email = $request->input('email');

        $broker = null;
        if (Karyawan::where('email', $email)->exists()) {
            $broker = 'karyawan';
        } elseif (Pelanggan::where('email', $email)->exists()) {
            $broker = 'pelanggan';
        } else {
            return back()->withErrors(['email' => 'Email tidak terdaftar.'])->onlyInput('email');
        }

        $status = Password::broker($broker)->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill(['password' => $password]);
                $user->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diganti. Silakan login.');
        }

        return back()->withErrors(['email' => 'Link reset tidak valid atau sudah kedaluwarsa.'])->onlyInput('email');
    }

    /* ================= HELPER PESAN & ATRIBUT ================= */

    private function pesan(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'email' => 'Format email tidak valid.',
            'min' => ':attribute minimal :min karakter.',
            'max' => ':attribute maksimal :max karakter.',
            'unique' => ':attribute sudah terdaftar.',
            'confirmed' => 'Konfirmasi password tidak sama.',
            'regex' => 'Format :attribute tidak valid.',
            'alpha_dash' => 'Hanya boleh huruf, angka, strip, dan underscore.',
        ];
    }

    private function atribut(): array
    {
        return [
            'login_id' => 'Username / Email',
            'nama_cust' => 'Nama lengkap',
            'no_telp' => 'No. telepon',
            'email' => 'Email',
            'password' => 'Password',
        ];
    }
}
