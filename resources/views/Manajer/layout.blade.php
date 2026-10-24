<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', 'Panel Manajer - Salon Rengganis')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Manajer/layout.css'])
    @stack('styles')
</head>
<body>

    <div class="admin-shell">
        <!-- Sidebar Kiri Khusus Manajer -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-ikon">🌸</span>
                <div class="brand-teks">
                    <span class="brand-nama">Salon Rengganis</span>
                    <span class="brand-sub">Panel Manajer</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-grup">
                    <span class="nav-label">RINGKASAN</span>
                    <a href="{{ route('manajer.dashboard') }}" class="nav-item {{ request()->routeIs('manajer.dashboard') ? 'aktif' : '' }}">
                        <span class="nav-ikon">📊</span>
                        <span>Beranda Manajer</span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">KELOLA DATA (MASTER)</span>
                    <a href="{{ route('manajer.karyawan') }}" class="nav-item {{ request()->routeIs('manajer.karyawan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">👥</span>
                        <span>Data Karyawan</span>
                    </a>
                    <a href="{{ route('manajer.layanan') }}" class="nav-item {{ request()->routeIs('manajer.layanan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">💇‍♀️</span>
                        <span>Daftar Layanan</span>
                    </a>
                    <a href="{{ route('manajer.produk') }}" class="nav-item {{ request()->routeIs('manajer.produk') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🧴</span>
                        <span>Stok Produk</span>
                    </a>
                    <a href="{{ route('manajer.promo') }}" class="nav-item {{ request()->routeIs('manajer.promo') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🏷️</span>
                        <span>Program Promo</span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">KEUANGAN &amp; ANALITIK</span>
                    <a href="{{ route('manajer.laporan') }}" class="nav-item {{ request()->routeIs('manajer.laporan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">📈</span>
                        <span>Cek Laporan</span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">PENGATURAN NOTA &amp; BAYAR</span>
                    <a href="{{ route('manajer.metode') }}" class="nav-item {{ request()->routeIs('manajer.metode') ? 'aktif' : '' }}">
                        <span class="nav-ikon">💳</span>
                        <span>Metode Pembayaran</span>
                    </a>
                    <a href="{{ route('manajer.struk') }}" class="nav-item {{ request()->routeIs('manajer.struk') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🧾</span>
                        <span>Format Struk Nota</span>
                    </a>
                    <a href="{{ route('manajer.kebijakan') }}" class="nav-item {{ request()->routeIs('manajer.kebijakan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🛡️</span>
                        <span>Kebijakan &amp; Blokir</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-kaki">
                <div class="info-hak-akses">
                    <span>🔒 Hak Akses: <strong>Manajer</strong></span>
                </div>
            </div>
        </aside>

        <!-- Area Utama Kanan -->
        <div class="main-area">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-kiri">
                    <button type="button" class="btn-menu-mobile" id="btnToggleSidebar" aria-label="Buka Menu">☰</button>
                    <div>
                        <h1 class="topbar-judul">@yield('header', 'Dashboard Manajer')</h1>
                        <p class="topbar-sub">@yield('sub_header', 'Kelola operasional Salon Rengganis secara terpusat.')</p>
                    </div>
                </div>

                <div class="topbar-kanan">
                    <div class="profil-staf">
                        <span class="staf-avatar">{{ strtoupper(substr(auth('karyawan')->user()->nama_staff, 0, 1)) }}</span>
                        <div class="staf-info">
                            <span class="staf-nama">{{ auth('karyawan')->user()->nama_staff }}</span>
                            <span class="staf-jabatan">{{ auth('karyawan')->user()->role_jabatan }}</span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-keluar">Keluar</button>
                    </form>
                </div>
            </header>

            <!-- Konten Halaman -->
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

            <footer class="footer-admin">
                Salon Rengganis &bull; Panel Manajer
            </footer>
        </div>
    </div>

    <!-- Notifikasi Toast Interaktif -->
    <div id="toastNotif" class="toast-notif"></div>

    @vite(['resources/js/Manajer/layout.js'])
    @stack('scripts')
</body>
</html>
