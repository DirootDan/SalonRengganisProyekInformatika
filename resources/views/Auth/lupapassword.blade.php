<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Salon Rengganis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Login/lupapassword.css', 'resources/js/loginpage.js'])
</head>
<body>

    <div class="login-card">
        <div class="logo">🔑</div>
        <h2>Lupa Password</h2>
        <p class="subtitle">Masukkan email akun Anda (Karyawan/Pelanggan) untuk menerima link reset password</p>

        @if (session('status'))
            <div class="success-msg">✅ {{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-msg">⚠️ {{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" id="loginForm">
            @csrf

            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="nama@email.com" required autofocus>

            <button type="submit" id="submitBtn">KIRIM LINK RESET</button>
        </form>

        <div class="link-row center">
            <a href="{{ route('login') }}">Kembali ke Login</a>
        </div>
    </div>

</body>
</html>