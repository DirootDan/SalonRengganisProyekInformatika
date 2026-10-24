<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $userLogin = auth('karyawan')->user();
        $isManajer = $userLogin && $userLogin->role_jabatan === 'Manajer';
    @endphp
    <title>@yield('judul', ($isManajer ? 'Panel Manajer' : 'Panel Kasir') . ' - Salon Rengganis')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Manajer/layout.css'])
    @stack('styles')
</head>
<body>

    <div class="admin-shell">
        <!-- Sidebar Kiri Terpadu (Otomatis menyesuaikan Manajer vs Kasir) -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-brand">
                <span class="brand-ikon">🌸</span>
                <div class="brand-teks">
                    <span class="brand-nama">Salon Rengganis</span>
                    <span class="brand-sub">{{ $isManajer ? 'Panel Manajer' : 'Panel Operasional Kasir' }}</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-grup">
                    <span class="nav-label">UTAMA &amp; TRANSAKSI</span>
                    @if ($isManajer)
                        <a href="{{ route('manajer.dashboard') }}" class="nav-item {{ request()->routeIs('manajer.dashboard') ? 'aktif' : '' }}">
                            <span class="nav-ikon">📊</span>
                            <span>Beranda Manajer</span>
                        </a>
                    @endif
                    <a href="{{ route('kasir.dashboard') }}" class="nav-item {{ request()->routeIs('kasir.dashboard') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🖥️</span>
                        <span>Dashboard Transaksi</span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">KELOLA DATA</span>
                    @if ($isManajer)
                        <a href="{{ route('manajer.karyawan') }}" class="nav-item {{ request()->routeIs('manajer.karyawan') ? 'aktif' : '' }}">
                            <span class="nav-ikon">👥</span>
                            <span>Data Karyawan</span>
                        </a>
                    @endif

                    <a href="{{ route('manajer.layanan') }}" class="nav-item {{ request()->routeIs('manajer.layanan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">💇‍♀️</span>
                        <span>Daftar Layanan</span>
                    </a>
                    <a href="{{ route('manajer.produk') }}" class="nav-item {{ request()->routeIs('manajer.produk') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🧴</span>
                        <span>Stok Produk</span>
                    </a>

                    @if ($isManajer)
                        <a href="{{ route('manajer.promo') }}" class="nav-item {{ request()->routeIs('manajer.promo') ? 'aktif' : '' }}">
                            <span class="nav-ikon">🏷️</span>
                            <span>Program Promo</span>
                        </a>
                    @endif
                </div>

                <div class="nav-grup">
                    <span class="nav-label">KEUANGAN &amp; LAPORAN</span>
                    <a href="{{ route('manajer.laporan') }}" class="nav-item {{ request()->routeIs('manajer.laporan') ? 'aktif' : '' }}">
                        <span class="nav-ikon">📈</span>
                        <span>{{ $isManajer ? 'Cek Laporan Lengkap' : 'Laporan Transaksi' }}</span>
                    </a>
                </div>

                <div class="nav-grup">
                    <span class="nav-label">PENGATURAN KASIR</span>
                    <a href="{{ route('manajer.metode') }}" class="nav-item {{ request()->routeIs('manajer.metode') ? 'aktif' : '' }}">
                        <span class="nav-ikon">💳</span>
                        <span>Metode Pembayaran</span>
                    </a>
                    <a href="{{ route('manajer.struk') }}" class="nav-item {{ request()->routeIs('manajer.struk') ? 'aktif' : '' }}">
                        <span class="nav-ikon">🧾</span>
                        <span>Format Struk Nota</span>
                    </a>
                </div>
            </nav>

            <div class="sidebar-kaki">
                <div class="info-hak-akses">
                    <span>🔒 Mode Akses: <strong>{{ $userLogin->role_jabatan }}</strong></span>
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
                        <h1 class="topbar-judul">@yield('header', 'Panel Operasional')</h1>
                        <p class="topbar-sub">@yield('sub_header', 'Kelola operasional Salon Rengganis secara terpusat.')</p>
                    </div>
                </div>

                <div class="topbar-kanan">
                    <div class="profil-staf">
                        <span class="staf-avatar">{{ strtoupper(substr($userLogin->nama_staff, 0, 1)) }}</span>
                        <div class="staf-info">
                            <span class="staf-nama">{{ $userLogin->nama_staff }}</span>
                            <span class="staf-jabatan">{{ $userLogin->role_jabatan }}</span>
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
                @yield('isi')
            </main>

            <footer class="footer-admin">
                Salon Rengganis &bull; Sistem Manajemen Operasional &amp; Kasir
            </footer>
        </div>
    </div>

    <!-- Notifikasi Toast Interaktif -->
    <div id="toastNotif" class="toast-notif"></div>

    @vite(['resources/js/Manajer/layout.js'])
    @stack('scripts')
</body>
</html>
