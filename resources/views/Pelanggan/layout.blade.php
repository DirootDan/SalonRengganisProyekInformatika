<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('judul', 'Salon Rengganis')</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/pelanggan/dashboard.css', 'resources/css/pelanggan/layanan.css', 'resources/css/pelanggan/riwayat.css'])
    @stack('styles')
</head>
<body>
    @php
        $pelangganAktif = auth('pelanggan')->user();
        $jmlNotifBelumDibaca = $pelangganAktif
            ? \App\Models\NotifikasiPelanggan::where('id_pelanggan', $pelangganAktif->id_pelanggan)->where('dibaca', false)->count()
            : 0;
        $sedangDiProfil = request()->routeIs('pelanggan.profil*');
    @endphp

    <header class="topbar">
        <div class="topbar-inner">
            <a href="{{ route('pelanggan.beranda') }}" class="brand">🌸 Salon Rengganis</a>

            <nav class="menu" aria-label="Navigasi Utama Pelanggan">
                <a href="{{ route('pelanggan.beranda') }}" class="{{ request()->routeIs('pelanggan.beranda') ? 'aktif' : '' }}">Beranda</a>
                <a href="{{ route('pelanggan.layanan') }}" class="{{ request()->routeIs('pelanggan.layanan') ? 'aktif' : '' }}">Layanan</a>
                <a href="{{ route('pelanggan.booking') }}" class="{{ request()->routeIs('pelanggan.booking*') ? 'aktif' : '' }}">Booking</a>
                <a href="{{ route('pelanggan.riwayat') }}" class="{{ request()->routeIs('pelanggan.riwayat') ? 'aktif' : '' }}">Riwayat</a>
            </nav>

            <div class="akun">
                <!-- Tempat Ikon Notifikasi (Lonceng) di Sebelah Kiri Nama -->
                <a href="{{ route('pelanggan.riwayat') }}"
                   class="tombol-lonceng {{ $jmlNotifBelumDibaca > 0 ? 'ada-notif' : '' }}"
                   aria-label="Notifikasi ({{ $jmlNotifBelumDibaca }} belum dibaca)"
                   title="{{ $jmlNotifBelumDibaca > 0 ? $jmlNotifBelumDibaca . ' pemberitahuan belum dibaca' : 'Pemberitahuan Akun' }}">
                    <span class="ikon-lonceng" aria-hidden="true">🔔</span>
                    @if ($jmlNotifBelumDibaca > 0)
                        <span class="lencana-lonceng">{{ $jmlNotifBelumDibaca > 99 ? '99+' : $jmlNotifBelumDibaca }}</span>
                    @endif
                </a>

                <!-- Dropdown Akun Pelanggan -->
                <div class="akun-dropdown" id="akunDropdown">
                    <button type="button"
                            class="akun-trigger {{ $sedangDiProfil ? 'aktif' : '' }}"
                            id="akunTrigger"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="akunMenu">
                        <span class="akun-avatar">{{ strtoupper(substr($pelangganAktif->nama_cust, 0, 1)) }}</span>
                        <span class="akun-sapaan">Halo, {{ $pelangganAktif->nama_cust }}</span>
                        <span class="akun-panah" aria-hidden="true">▾</span>
                    </button>

                    <div class="akun-menu" id="akunMenu" role="menu" aria-labelledby="akunTrigger" hidden>
                        <div class="akun-menu-kepala">
                            <strong class="akun-menu-nama">{{ $pelangganAktif->nama_cust }}</strong>
                            <span class="akun-menu-email">{{ $pelangganAktif->email }}</span>
                        </div>

                        <div class="akun-menu-pemisah" role="separator"></div>

                        <a href="{{ route('pelanggan.profil') }}"
                           class="akun-menu-item {{ $sedangDiProfil && request()->query('tab') !== 'password' ? 'item-aktif' : '' }}"
                           role="menuitem">
                            <span class="item-ikon" aria-hidden="true">👤</span>
                            <span>Profil Saya</span>
                        </a>

                        <a href="{{ route('pelanggan.profil', ['tab' => 'password']) }}"
                           class="akun-menu-item {{ $sedangDiProfil && request()->query('tab') === 'password' ? 'item-aktif' : '' }}"
                           role="menuitem">
                            <span class="item-ikon" aria-hidden="true">🔒</span>
                            <span>Ganti Kata Sandi</span>
                        </a>

                        <div class="akun-menu-pemisah" role="separator"></div>

                        <form method="POST" action="{{ route('logout') }}" class="form-menu-keluar" role="none">
                            @csrf
                            <button type="submit" class="akun-menu-item akun-menu-keluar" role="menuitem">
                                <span class="item-ikon" aria-hidden="true">🚪</span>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
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

    <x-confirm-modal />

    @vite(['resources/js/pelanggan/layout.js'])
    @stack('scripts')
</body>
</html>
