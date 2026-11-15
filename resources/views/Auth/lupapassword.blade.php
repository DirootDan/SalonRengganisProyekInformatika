<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Salon Rengganis</title>
    <x-meta-situs />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/pages/auth/auth.css', 'resources/js/loginpage.js'])
</head>
<body class="auth-reset">

    <main class="login-card">
        <div class="logo" aria-hidden="true"><x-ikon name="kunci" /></div>
        <h1>Lupa Password</h1>
        <p class="subtitle">Masukkan email akun Anda (Karyawan/Pelanggan) untuk menerima link reset password</p>

        @if (session('status'))
            <div class="success-msg"><x-ikon name="cek" /> {{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-msg"><x-ikon name="peringatan" /> {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" id="loginForm">
            @csrf

            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="nama@email.com" required autocomplete="email" autofocus>

            <button type="submit" id="submitBtn">Kirim Link Reset</button>
        </form>

        <div class="link-row center">
            <a href="{{ route('login') }}">Kembali ke Login</a>
        </div>

        <p class="footer-note">
            <a href="{{ route('beranda.publik') }}">&larr; Kembali ke Beranda Salon</a>
        </p>
    </main>

<x-anti-klik-ganda />

</body>
</html>