<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Salon</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Login/loginpage.css', 'resources/js/loginpage.js'])
</head>
<body>

    <div class="login-card">
        <div class="logo">🌸</div>
        <h2>Salon Rengganis</h2>
        <!-- Teks disesuaikan agar relevan untuk karyawan dan pelanggan -->
        <p class="subtitle">Silakan masuk ke akun Anda</p>

        @if (session('status'))
            <div class="success-msg">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-msg">⚠️ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.proses') }}" id="loginForm">
            @csrf

            <!-- Mengubah name, id, old, dan label menjadi login_id -->
            <label for="login_id" class="field-label">Username (Karyawan) / Email (Pelanggan)</label>
            <input type="text" id="login_id" name="login_id" value="{{ old('login_id') }}"
                   placeholder="Masukkan username atau email..." required autocomplete="off" autofocus>

            <label for="password" class="field-label">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>

            <button type="submit" id="submitBtn">MASUK SEKARANG</button>
        </form>

        <div class="link-row">
            <a href="{{ route('password.lupa') }}">Lupa password?</a>
            <!-- Mengganti link login pelanggan menjadi pendaftaran pelanggan -->
            <a href="{{ route('register') }}">Belum punya akun? Daftar</a>
        </div>

        <p class="footer-note">Aplikasi Salon v1.0 &bull; Web Version</p>
    </div>

</body>
</html>