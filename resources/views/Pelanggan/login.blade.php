<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Pelanggan - Salon Rengganis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Login/loginpage.css', 'resources/loginpage.js'])
</head>
<body>

    <div class="login-card">
        <div class="logo">🌸</div>
        <h2>Salon Rengganis</h2>
        <p class="subtitle">Masuk untuk booking perawatan Anda</p>

        @if (session('status'))
            <div class="success-msg">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-msg">⚠️ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('pelanggan.login.proses') }}" id="loginForm">
            @csrf

            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="nama@email.com" required autofocus>

            <label for="password" class="field-label">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="••••••••" required>

            <button type="submit" id="submitBtn">MASUK</button>
        </form>

        <div class="link-row">
            <a href="{{ route('pelanggan.password.lupa') }}">Lupa password?</a>
            <a href="{{ route('pelanggan.register') }}">Buat akun baru</a>
        </div>

        <div class="link-row center">
            <a href="{{ route('login') }}">Login karyawan</a>
        </div>

        <p class="footer-note">Salon Rengganis &bull; Web Version</p>
    </div>

</body>
</html>