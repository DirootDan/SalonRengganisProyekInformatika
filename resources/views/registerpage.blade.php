<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Akun - Salon Rengganis</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/registerpage.css', 'resources/js/loginpage.js'])
</head>
<body>

    <div class="login-card">
        <div class="logo">🌸</div>
        <h2>Buat Akun Baru</h2>
        <p class="subtitle">Daftarkan akun staff Salon Rengganis</p>

        @if ($errors->any())
            <div class="error-msg">
                @foreach ($errors->all() as $error)
                    ⚠️ {{ $error }}<br>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register.proses') }}" id="loginForm">
            @csrf

            <label for="nama_staff" class="field-label">Nama Lengkap</label>
            <input type="text" id="nama_staff" name="nama_staff" value="{{ old('nama_staff') }}"
                   placeholder="Masukan nama lengkap..." required autofocus>

            <label for="email" class="field-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   placeholder="nama@email.com" required>

            <label for="username" class="field-label">Username</label>
            <input type="text" id="username" name="username" value="{{ old('username') }}"
                   placeholder="Minimal 4 karakter..." required autocomplete="off">

            <label for="password" class="field-label">Password</label>
            <input type="password" id="password" name="password"
                   placeholder="Minimal 6 karakter" required>

            <label for="password_confirmation" class="field-label">Ulangi Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   placeholder="••••••••" required>

            <button type="submit" id="submitBtn">DAFTAR SEKARANG</button>
        </form>

        <div class="link-row center">
            <a href="{{ route('login') }}">Sudah punya akun? Login</a>
        </div>
    </div>

</body>
</html>