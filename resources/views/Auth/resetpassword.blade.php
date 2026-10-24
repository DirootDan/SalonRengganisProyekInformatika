<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Salon Rengganis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Login/resetpassword.css', 'resources/js/loginpage.js'])
</head>
<body>

    <div class="login-card">
        <div class="logo">🔑</div>
        <h2>Password Baru</h2>
        <p class="subtitle">Silakan buat password baru untuk akun Anda</p>

        @if ($errors->any())
            <div class="error-msg">
                @foreach ($errors->all() as $error)
                    ⚠️ {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" id="loginForm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                   required readonly>

            <label for="password" class="field-label">Password Baru</label>
            <input type="password" id="password" name="password"
                   placeholder="Minimal 6 karakter" required autofocus>

            <label for="password_confirmation" class="field-label">Ulangi Password Baru</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   placeholder="••••••••" required>

            <button type="submit" id="submitBtn">GANTI PASSWORD</button>
        </form>

        <div class="link-row center">
            <a href="{{ route('login') }}">Kembali ke Login</a>
        </div>
    </div>

</body>
</html>