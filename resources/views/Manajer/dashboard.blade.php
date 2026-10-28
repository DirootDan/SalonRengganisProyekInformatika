@extends('Manajer.layout')

@section('judul', 'Beranda Manajer - Salon Rengganis')
@section('header', 'Ringkasan Operasional')
@section('sub_header', 'Pantau kinerja salon, data master, dan pengaturan kasir dalam satu halaman.')

@push('styles')
    @vite(['resources/css/Manajer/dashboard.css'])
@endpush

@section('isi')
    <!-- Kartu Statistik Utama -->
    <div class="stat-grid">
        <div class="stat-kartu">
            <div class="stat-ikon stat-pink">👥</div>
            <div class="stat-info">
                <span class="stat-label">Total Karyawan</span>
                <span class="stat-nilai">{{ $totalKaryawan }} Staf</span>
            </div>
        </div>

        <div class="stat-kartu">
            <div class="stat-ikon stat-ungu">💇‍♀️</div>
            <div class="stat-info">
                <span class="stat-label">Jenis Layanan</span>
                <span class="stat-nilai">{{ $totalLayanan }} Layanan</span>
            </div>
        </div>

        <div class="stat-kartu">
            <div class="stat-ikon stat-hijau">🧴</div>
            <div class="stat-info">
                <span class="stat-label">Katalog Produk</span>
                <span class="stat-nilai">{{ $totalProduk }} Produk</span>
            </div>
        </div>

        <div class="stat-kartu">
            <div class="stat-ikon stat-kuning">📅</div>
            <div class="stat-info">
                <span class="stat-label">Total Reservasi</span>
                <span class="stat-nilai">{{ $totalReservasi }} Booking</span>
            </div>
        </div>
    </div>

    <!-- Pintasan Menu Manajer -->
    <h2 class="seksi-judul">Akses Cepat Menu Manajer</h2>
    <div class="pintas-grid">
        <a href="{{ route('manajer.karyawan') }}" class="pintas-item">
            <span class="pintas-ikon">👥</span>
            <div>
                <h3>Kelola Karyawan</h3>
                <p>Tambah, ubah, atur persentase komisi, atau nonaktifkan akun staf.</p>
            </div>
        </a>

        <a href="{{ route('manajer.layanan') }}" class="pintas-item">
            <span class="pintas-ikon">💇‍♀️</span>
            <div>
                <h3>Kelola Layanan</h3>
                <p>Atur kategori layanan, harga perawatan, dan pemetaan jabatan petugas.</p>
            </div>
        </a>

        <a href="{{ route('manajer.produk') }}" class="pintas-item">
            <span class="pintas-ikon">🧴</span>
            <div>
                <h3>Kelola Produk</h3>
                <p>Pantau stok produk kecantikan salon dan perbarui harga jual.</p>
            </div>
        </a>

        <a href="{{ route('manajer.promo') }}" class="pintas-item">
            <span class="pintas-ikon">🏷️</span>
            <div>
                <h3>Program Promo</h3>
                <p>Buat kode voucer, potongan harga, dan periode promosi pelanggan.</p>
            </div>
        </a>

        <a href="{{ route('manajer.laporan') }}" class="pintas-item">
            <span class="pintas-ikon">📈</span>
            <div>
                <h3>Cek Laporan</h3>
                <p>Lihat rekap pendapatan, layanan terlaris, dan kinerja komisi karyawan.</p>
            </div>
        </a>

        <a href="{{ route('manajer.metode') }}" class="pintas-item">
            <span class="pintas-ikon">💳</span>
            <div>
                <h3>Metode Pembayaran</h3>
                <p>Atur rekening bank, QRIS, tunai, dan mesin EDC untuk transaksi kasir.</p>
            </div>
        </a>

        <a href="{{ route('manajer.struk') }}" class="pintas-item">
            <span class="pintas-ikon">🧾</span>
            <div>
                <h3>Format Struk Nota</h3>
                <p>Sesuaikan nama cabang, alamat, logo, dan pesan kaki pada struk belanja.</p>
            </div>
        </a>

        <a href="{{ route('manajer.kebijakan') }}" class="pintas-item">
            <span class="pintas-ikon">🛡️</span>
            <div>
                <h3>Kebijakan Booking &amp; Pinalti</h3>
                <p>Atur batas pelanggaran, lama blokir, dan buka blokir pelanggan secara manual.</p>
            </div>
        </a>
    </div>

    <!-- Ringkasan Reservasi Terbaru -->
    <div class="panel-kartu spasi-atas">
        <div class="toolbar-atas">
            <h3 class="tanpa-margin">Reservasi Pelanggan Terbaru</h3>
            <a href="{{ route('manajer.laporan') }}" class="btn-sekunder">Lihat Semua Laporan &rarr;</a>
        </div>

        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>ID Reservasi</th>
                        <th>Nama Pelanggan</th>
                        <th>Jadwal Kedatangan</th>
                        <th>Rincian Layanan</th>
                        <th>Total Estimasi</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservasiBaru as $r)
                        <tr>
                            <td><strong>#{{ $r->id_reservasi }}</strong></td>
                            <td>{{ $r->pelanggan?->nama_cust ?? 'Pelanggan' }}</td>
                            <td>{{ $r->tgl_booking->format('d/m/Y') }} ({{ $r->jam_booking }})</td>
                            <td>{{ $r->layanan->pluck('nama_layanan')->join(', ') ?: '-' }}</td>
                            <td><strong>Rp {{ number_format($r->layanan->sum('harga_layanan'), 0, ',', '.') }}</strong></td>
                            <td>
                                <span class="badge {{ $r->statusTernormalisasi() === 'Selesai' ? 'badge-aktif' : 'badge-manajer' }}">
                                    {{ $r->statusTernormalisasi() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="teks-tengah-abu">Belum ada data reservasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/dashboard.js'])
@endpush
