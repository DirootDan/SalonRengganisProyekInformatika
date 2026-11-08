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

    <div class="login-card">
        <div class="logo">🌸</div>
        <h2>Salon Rengganis</h2>
        <p class="subtitle">Silakan masuk ke akun Anda</p>

        @if (session('status'))
            <div class="success-msg">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-msg">⚠️ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.proses') }}" id="loginForm">
            @csrf

            <label for="login_id" class="field-label">Email atau Username</label>
            <input type="text" id="login_id" name="login_id" value="{{ old('login_id') }}"
                   placeholder="Masukkan email atau username..." required autocomplete="off" autofocus>

            <label for="password" class="field-label">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>

            <button type="submit" id="submitBtn">MASUK SEKARANG</button>
        </form>

        <div class="link-row">
            <a href="{{ route('password.lupa') }}">Lupa password?</a>
            <a href="{{ route('register') }}">Belum punya akun? Daftar</a>
        </div>

        <p class="footer-note">
            <a href="{{ route('beranda.publik') }}">&larr; Kembali ke Beranda Salon</a>
        </p>
    </div>

</body>
</html>