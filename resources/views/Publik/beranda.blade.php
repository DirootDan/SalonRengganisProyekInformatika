<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salon Rengganis - Perawatan Rambut, Wajah &amp; Tubuh</title>
    <x-meta-situs
        judul="Salon Rengganis - Perawatan Rambut, Wajah & Tubuh"
        deskripsi="Lihat layanan dan harga resmi Salon Rengganis, pilih jam dan petugas favoritmu, lalu booking online tanpa antre."
        :indeks="true" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/Publik/beranda.css', 'resources/js/Publik/beranda.js'])
</head>
<body>
    <header class="pb-topbar">
        <div class="pb-wadah pb-topbar-isi">
            <a href="{{ route('beranda.publik') }}" class="pb-brand"><x-ikon name="bunga" /> Salon Rengganis</a>
            <button type="button" id="btnMenuPublik" class="pb-menu-btn" aria-expanded="false" aria-controls="pbMenuNav" aria-label="Buka menu navigasi">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
            </button>
            <nav id="pbMenuNav" class="pb-menu" aria-label="Navigasi Publik">
                <a href="#layanan">Layanan</a>
                <a href="#info">Jam Buka</a>
                <a href="#cara">Cara Booking</a>
            </nav>
            <div class="pb-akun">
                <a href="{{ route('login') }}" class="pb-tautan-masuk">Masuk</a>
                <a href="{{ route('register') }}" class="pb-tombol pb-tombol-kecil">Daftar</a>
            </div>
        </div>
    </header>

    <main>
        <section class="pb-hero">
            <div class="pb-wadah">
                <h1>Cantik &amp; Nyaman,<br>Tanpa Antre Panjang</h1>
                <p>Lihat layanan dan harga resmi kami, pilih jam dan petugas favoritmu, lalu booking langsung dari ponsel.</p>
                <div class="pb-hero-aksi">
                    <a href="{{ route('register') }}" class="pb-tombol">Booking Jadwal Sekarang</a>
                    <a href="#layanan" class="pb-tombol pb-tombol-garis">Lihat Layanan &amp; Harga</a>
                </div>
            </div>
        </section>

        <section class="pb-seksi" id="layanan">
            <div class="pb-wadah">
                <h2>Layanan Unggulan</h2>
                <p class="pb-sub">Harga resmi, tanpa biaya tersembunyi.</p>

                @if ($layananUnggulan->isEmpty())
                    <p class="pb-kosong">Daftar layanan akan segera tersedia.</p>
                @else
                    <div class="pb-grid">
                        @foreach ($layananUnggulan as $item)
                            <article class="pb-kartu">
                                <span class="pb-label">{{ $item->kategori }}</span>
                                <h3>{{ $item->nama_layanan }}</h3>
                                <p class="pb-meta">⏱ {{ $item->durasi_menit }} menit &bull; {{ $item->jabatan_petugas }}</p>
                                <div class="pb-kartu-bawah">
                                    <strong class="pb-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</strong>
                                    <a href="{{ route('pelanggan.booking', ['layanan' => $item->id_layanan]) }}" class="pb-tautan-pilih">
                                        Pilih Layanan Ini &rarr;
                                    </a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if ($totalLayanan > $layananUnggulan->count())
                        <p class="pb-lainnya">
                            +{{ $totalLayanan - $layananUnggulan->count() }} layanan lainnya.
                            <a href="{{ route('login') }}">Masuk untuk melihat semua</a>
                        </p>
                    @endif
                @endif
            </div>
        </section>

        <section class="pb-seksi pb-seksi-lembut" id="cara">
            <div class="pb-wadah">
                <h2>Cara Booking</h2>
                <ol class="pb-langkah">
                    <li><strong>1. Daftar / Masuk</strong><span>Buat akun gratis dalam satu menit.</span></li>
                    <li><strong>2. Pilih Layanan &amp; Jam</strong><span>Pilih perawatan, tanggal, jam, dan petugas.</span></li>
                    <li><strong>3. Tunggu Konfirmasi</strong><span>Kasir akan menyetujui jadwalmu, lalu tinggal datang.</span></li>
                </ol>
            </div>
        </section>

        <section class="pb-seksi" id="info">
            <div class="pb-wadah pb-info">
                <h2>Jam Operasional</h2>
                <p class="pb-jam">Setiap hari, {{ $jamBuka }} &ndash; {{ $jamTutup }} WIB</p>
                <a href="{{ route('register') }}" class="pb-tombol">Mulai Booking</a>
            </div>
        </section>
    </main>

    <footer class="pb-footer">
        Salon Rengganis &bull; Sistem Reservasi Online
    </footer>
<x-tombol-whatsapp />
</body>
</html>
