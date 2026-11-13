<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', "Panel {$peran} - Salon Rengganis")</title>
    <x-meta-situs />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Karyawan/Petugas/dashboard.css'])
</head>
<body>
    <div class="petugas-shell">
        <header class="topbar-petugas">
            <div class="topbar-inner">
                <div class="brand-grup">
                    <span class="brand-ikon" aria-hidden="true">{{ $tampilan['ikon'] }}</span>
                    <div>
                        <span class="brand-nama">Salon Rengganis</span>
                        <span class="brand-sub">Portal Kerja {{ $peran }}</span>
                    </div>
                </div>

                <nav class="menu-petugas" aria-label="Menu {{ $peran }}">
                    <a href="{{ route($tampilan['prefix_rute'].'.dashboard') }}"
                       class="{{ request()->routeIs($tampilan['prefix_rute'].'.dashboard') ? 'aktif' : '' }}">📅 Jadwal Reservasi Pribadi</a>
                    <a href="{{ route($tampilan['prefix_rute'].'.komisi') }}"
                       class="{{ request()->routeIs($tampilan['prefix_rute'].'.komisi') ? 'aktif' : '' }}">💰 Laporan Komisi Saya</a>
                </nav>

                <div class="akun-petugas">
                    <span class="nama-staf">{{ $petugas->nama_staff }} ({{ $peran }})</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-keluar">Keluar</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="wadah-petugas">
            @if (session('status'))
                <div class="alert-sukses" role="status">✅ {{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert-galat" role="alert">
                    @foreach ($errors->all() as $pesan)
                        <div>⚠️ {{ $pesan }}</div>
                    @endforeach
                </div>
            @endif

            @yield('isi')
        </main>
    </div>

    <x-confirm-modal />
    <x-anti-klik-ganda />
</body>
</html>
