<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', 'Panel Therapist - Salon Rengganis')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Karyawan/Therapist/dashboard.css'])
    @stack('styles')
</head>
<body>
    <div class="petugas-shell">
        <header class="topbar-petugas">
            <div class="topbar-inner">
                <div class="brand-grup">
                    <span class="brand-ikon">🌿</span>
                    <div>
                        <span class="brand-nama">Salon Rengganis</span>
                        <span class="brand-sub">Portal Kerja Therapist</span>
                    </div>
                </div>

                <nav class="menu-petugas">
                    <a href="{{ route('therapist.dashboard') }}" class="{{ request()->routeIs('therapist.dashboard') ? 'aktif' : '' }}">📅 Jadwal Reservasi Pribadi</a>
                    <a href="{{ route('therapist.komisi') }}" class="{{ request()->routeIs('therapist.komisi') ? 'aktif' : '' }}">💰 Laporan Komisi Saya</a>
                </nav>

                <div class="akun-petugas">
                    <span class="nama-staf">{{ auth('karyawan')->user()->nama_staff }} (Therapist)</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-keluar">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="wadah-petugas">
            @if (session('status'))
                <div class="alert-sukses">✅ {{ session('status') }}</div>
            @endif
            @yield('isi')
        </main>
    </div>

    @vite(['resources/js/Karyawan/Therapist/dashboard.js'])
    @stack('scripts')
</body>
</html>
