<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', 'Panel Kasir - Salon Rengganis')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Karyawan/Kasir/layout.css'])
    @stack('styles')
</head>
<body>
    @php
        $notifMenunggu = \App\Models\Reservasi::whereIn('status', [\App\Models\Reservasi::STATUS_MENUNGGU, 'pending'])->count();
        $notifAntrean  = \App\Models\Reservasi::where('status', \App\Models\Reservasi::STATUS_DITERIMA)
            ->doesntHave('transaksi')
            ->count();
    @endphp

    <div class="kasir-shell">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-ikon">🌸</span>
                <div class="brand-teks">
                    <span class="brand-nama">Salon Rengganis</span>
                    <span class="brand-sub">Meja Operasional Kasir</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-grup">
                    <span class="nav-label">TRANSAKSI &amp; RESERVASI</span>
                    <a href="{{ route('kasir.dashboard') }}" class="nav-item {{ request()->routeIs('kasir.dashboard') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🖥️</span>
                        <span class="nav-item-teks">Dashboard Transaksi</span>
                    </a>
                    <a href="{{ route('kasir.permintaan') }}" class="nav-item {{ request()->routeIs('kasir.permintaan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">📩</span>
                        <span class="nav-item-teks">
                            Permintaan Booking
                            <sup class="nav-kuadrat-notif {{ $notifMenunggu > 0 ? 'ada-acc' : 'kosong' }}"
                                 title="{{ $notifMenunggu }} permintaan booking menunggu ACC">
                                {{ $notifMenunggu > 99 ? '99+' : $notifMenunggu }}
                            </sup>
                        </span>
                    </a>
                    <a href="{{ route('kasir.antrean') }}" class="nav-item {{ request()->routeIs('kasir.antrean') ? 'aktif' : '' }}">
                        <span class="nav-ikon">⏳</span>
                        <span class="nav-item-teks">
                            Antrean Hari Ini
                            <sup class="nav-kuadrat-notif {{ $notifAntrean > 0 ? 'ada-antrean' : 'kosong' }}"
                                 title="{{ $notifAntrean }} antrean siap diproses di kasir">
                                {{ $notifAntrean > 99 ? '99+' : $notifAntrean }}
                            </sup>
                        </span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">KATALOG &amp; INVENTARIS</span>
                    <a href="{{ route('kasir.layanan') }}" class="nav-item {{ request()->routeIs('kasir.layanan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">💇‍♀️</span>
                        <span>Kelola Layanan</span>
                    </a>
                    <a href="{{ route('kasir.produk') }}" class="nav-item {{ request()->routeIs('kasir.produk') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🧴</span>
                        <span>Kelola Produk</span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">REKAP HARIAN</span>
                    <a href="{{ route('kasir.laporan') }}" class="nav-item {{ request()->routeIs('kasir.laporan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">📈</span>
                        <span>Cek Laporan Kasir</span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">PENGATURAN KASIR</span>
                    <a href="{{ route('kasir.metode') }}" class="nav-item {{ request()->routeIs('kasir.metode') ? 'aktif' : '' }}">
                        <span class="nav-ikon">💳</span>
                        <span>Metode Pembayaran</span>
                    </a>
                    <a href="{{ route('kasir.struk') }}" class="nav-item {{ request()->routeIs('kasir.struk') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🧾</span>
                        <span>Format Struk Nota</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-kaki">
                <div class="info-shift">
                    <span>🟢 Shift Kasir Aktif</span>
                    <small>{{ now()->format('d/m/Y') }}</small>
                </div>
            </div>
        </aside>

        <div class="main-area">
            <header class="topbar">
                <div class="topbar-kiri">
                    <button type="button" class="btn-menu-mobile" id="btnToggleSidebar" aria-label="Buka Menu">☰</button>
                    <div>
                        <h1 class="topbar-judul">@yield('header', 'Dashboard Transaksi')</h1>
                        <p class="topbar-sub">@yield('sub_header', 'Layani pembayaran pelanggan dan kelola antrean harian.')</p>
                    </div>
                </div>

                <div class="topbar-kanan">
                    <div class="profil-staf">
                        <span class="staf-avatar">{{ strtoupper(substr(auth('karyawan')->user()->nama_staff, 0, 1)) }}</span>
                        <div class="staf-info">
                            <span class="staf-nama">{{ auth('karyawan')->user()->nama_staff }}</span>
                            <span class="staf-jabatan">Petugas {{ auth('karyawan')->user()->role_jabatan }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-keluar">Keluar</button>
                    </form>
                </div>
            </header>

            <main class="konten">
                @if (session('status'))
                    <div class="alert-sukses">✅ {{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert-galat">
                        @foreach ($errors->all() as $err)
                            <div>⚠️ {{ $err }}</div>
                        @endforeach
                    </div>
                @endif

                @yield('isi')
            </main>

            <footer class="footer-kasir">
                Salon Rengganis &bull; Sistem Kasir &amp; Pelayanan Pelanggan
            </footer>
        </div>
    </div>

    <div id="toastNotif" class="toast-notif"></div>

    <x-confirm-modal />

    @vite(['resources/js/Karyawan/Kasir/layout.js'])
    @stack('scripts')
</body>
</html>
