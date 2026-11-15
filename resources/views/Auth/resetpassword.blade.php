<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Salon Rengganis</title>
    <x-meta-situs />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Login/resetpassword.css', 'resources/js/loginpage.js'])
</head>
<body>

    <main class="login-card">
        <div class="logo" aria-hidden="true"><x-ikon name="gembok" /></div>
        <h1>Password Baru</h1>
        <p class="subtitle">Silakan buat password baru untuk akun Anda</p>

        @if ($errors->any())
            <div class="error-msg">
                @foreach ($errors->all() as $error)
                    <x-ikon name="peringatan" /> {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" id="loginForm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                   required readonly autocomplete="email">

            <label for="password" class="field-label">Password Baru</label>
            <input type="password" id="password" name="password"
                   placeholder="Minimal 6 karakter" required autocomplete="new-password" autofocus>

            <label for="password_confirmation" class="field-label">Ulangi Password Baru</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   placeholder="••••••••" required autocomplete="new-password">

            <button type="submit" id="submitBtn">Ganti Password</button>
        </form>

        <div class="link-row center">
            <a href="{{ route('login') }}">Kembali ke Login</a>
        </div>
    </main>

<x-anti-klik-ganda />

</body>
</html>