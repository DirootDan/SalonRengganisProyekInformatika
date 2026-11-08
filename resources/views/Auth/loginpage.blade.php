<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - Salon Rengganis</title>
    <x-meta-situs />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Login/loginpage.css', 'resources/js/loginpage.js'])
</head>
<body>

    @php($aktifStaf = ! empty($modeStaf))

    <div class="login-card {{ $aktifStaf ? 'login-card-staf' : '' }}">
        <div class="tab-mode-login" role="tablist" aria-label="Pilih Peran Masuk">
            <a href="{{ route('login', ['mode' => 'pelanggan']) }}"
               class="tab-mode-item {{ ! $aktifStaf ? 'aktif' : '' }}">
                🌸 Pelanggan
            </a>
            <a href="{{ route('login', ['mode' => 'staf']) }}"
               class="tab-mode-item {{ $aktifStaf ? 'aktif' : '' }}">
                🔑 Portal Staf
            </a>
        </div>

        <div class="logo">{{ $aktifStaf ? '💼' : '🌸' }}</div>
        <h2>Salon Rengganis</h2>

        @if ($aktifStaf)
            <div class="badge-mode-staf">Mode Login Karyawan (Kasir &bull; Manajer &bull; Petugas)</div>
            <p class="subtitle">Silakan masuk menggunakan Username Karyawan Anda</p>
        @else
            <p class="subtitle">Silakan masuk ke akun Anda</p>
        @endif

        @if (session('status'))
            <div class="success-msg">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-msg">⚠️ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.proses') }}" id="loginForm">
            @csrf

            <label for="login_id" class="field-label">
                {{ $aktifStaf ? 'Username Karyawan' : 'Username (Karyawan) / Email (Pelanggan)' }}
            </label>
            <input type="text" id="login_id" name="login_id" value="{{ old('login_id') }}"
                   placeholder="{{ $aktifStaf ? 'Masukkan username karyawan...' : 'Masukkan username atau email...' }}"
                   required autocomplete="off" autofocus>

            <label for="password" class="field-label">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>

            <button type="submit" id="submitBtn">
                {{ $aktifStaf ? 'MASUK PORTAL STAF' : 'MASUK SEKARANG' }}
            </button>
        </form>

        <div class="link-row">
            <a href="{{ route('password.lupa') }}">Lupa password?</a>
            @if ($aktifStaf)
                <a href="{{ route('login', ['mode' => 'pelanggan']) }}">Login Pelanggan</a>
            @else
                <a href="{{ route('register') }}">Belum punya akun? Daftar</a>
            @endif
        </div>

        <p class="footer-note">
            <a href="{{ route('beranda.publik') }}">&larr; Kembali ke Beranda Salon</a>
        </p>
    </div>

</body>
</html>