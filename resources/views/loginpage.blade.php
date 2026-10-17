<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Salon</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Memanggil CSS dan JS dari folder resources menggunakan Vite -->
    @vite(['resources/css/loginpage.css', 'resources/js/loginpage.js'])
</head>
<body>
    <!-- (Isi form HTML login sama seperti sebelumnya) -->
    
    <div class="login-card">
        <div style="font-size: 40px; margin-bottom: 10px;">🌸</div>
        <h2>Salon Rengganis</h2>
        <p class="subtitle">Silakan masuk untuk memulai kasir</p>

        @if($errors->any())
            <div class="error-msg">⚠️ {{ $errors->first() }}</div>
        @endif

        <form method="POST" id="loginForm">
            @csrf 
            
            <div style="text-align: left; font-size: 12px; margin-bottom: 5px; color: #6b7280; font-weight: 600;">Username</div>
            <input type="text" name="username" placeholder="Masukan username..." required autocomplete="off">
            
            <div style="text-align: left; font-size: 12px; margin-bottom: 5px; color: #6b7280; font-weight: 600;">Password</div>
            <input type="password" name="password" placeholder="••••••••" required>
            
            <button type="submit" name="login" id="submitBtn">MASUK SEKARANG</button>
        </form>

        <p style="margin-top: 20px; font-size: 11px; color: #9ca3af;">
            Aplikasi Kasir v1.0 &bull; Web Version
        </p>
    </div>
</body>
</html>