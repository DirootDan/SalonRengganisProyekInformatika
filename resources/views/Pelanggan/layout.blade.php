```html
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', 'Salon Rengganis')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/pelanggan/dashboard.css', 'resources/css/pelanggan/layanan.css', 'resources/css/pelanggan/riwayat.css'])
</head>
<body>

    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ route('pelanggan.beranda') }}" class="brand">🌸 Salon Rengganis</a>

            <nav class="menu">
                <!-- Tambahan Menu Beranda -->
                <a href="{{ route('pelanggan.beranda') }}" class="{{ request()->routeIs('pelanggan.beranda') ? 'aktif' : '' }}">Beranda</a>

                <!-- Menu Layanan Baru -->
                <a href="{{ route('pelanggan.layanan') }}" class="{{ request()->routeIs('pelanggan.layanan') ? 'aktif' : '' }}">Layanan</a>

                <a href="{{ route('pelanggan.booking') }}" class="{{ request()->routeIs('pelanggan.booking') ? 'aktif' : '' }}">Booking</a>

                <a href="{{ route('pelanggan.riwayat') }}" class="{{ request()->routeIs('pelanggan.riwayat') ? 'aktif' : '' }}">Riwayat</a>
            </nav>

            <div class="akun">
                <span>Halo, {{ auth('pelanggan')->user()->nama_cust }}</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-keluar">Keluar</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="success-msg">✅ {{ session('status') }}</div>
        @endif

        @yield('isi')
    </main>

    <footer class="footer">Salon Rengganis &bull; Terima kasih sudah mempercayakan kecantikan Anda</footer>

    <!-- Tempat untuk memuat JS spesifik dari tiap halaman -->
    @stack('scripts')
</body>
</html>
```