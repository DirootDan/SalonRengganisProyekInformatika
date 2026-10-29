@extends('Karyawan.Kasir.layout')

@section('judul', 'Laporan Transaksi - Kasir Salon Rengganis')
@section('header', 'Laporan Transaksi Kasir')
@section('sub_header', 'Rekap penerimaan pembayaran harian dan riwayat nota transaksi di meja kasir.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/laporan.css'])
@endpush

@section('isi')
    <div class="panel-kartu">
        <div class="toolbar-atas">
            <h3 class="tanpa-margin">Riwayat Nota Pembayaran &amp; Reservasi</h3>
            <button type="button" class="btn-utama" id="btnCetakLaporan">🖨️ Cetak Rekap Shift</button>
        </div>

        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>No. Nota</th>
                        <th>Tanggal &amp; Jam</th>
                        <th>Pelanggan</th>
                        <th>Item Transaksi</th>
                        <th>Metode Bayar</th>
                        <th>Nominal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservasi as $r)
                        @php
                            $nominal = $r->transaksi ? $r->transaksi->total_bayar : $r->estimasiTotalAkhir();
                            $rincianLayanan = $r->layanan->pluck('nama_layanan')->join(', ');
                            $rincianProduk = $r->produkAddon->map(fn ($p) => $p->nama_produk . ' (x' . $p->pivot->jumlah . ')')->join(', ');
                        @endphp
                        <tr>
                            <td><strong>#INV-{{ str_pad($r->id_reservasi, 4, '0', STR_PAD_LEFT) }}</strong></td>
                            <td>{{ $r->tgl_booking->format('d/m/Y') }} ({{ $r->jam_booking }}{{ $r->jam_selesai ? ' - ' . $r->jam_selesai : '' }})</td>
                            <td>{{ $pelangganMap[$r->id_pelanggan] ?? 'Pelanggan' }}</td>
                            <td>
                                <div>{{ $rincianLayanan ?: '-' }}</div>
                                @if ($rincianProduk)
                                    <small>+ Produk: {{ $rincianProduk }}</small>
                                @endif
                                @if ($r->kode_promo)
                                    <div><small class="badge badge-aktif">Promo: {{ $r->kode_promo }} (-Rp {{ number_format((float) $r->nominal_diskon, 0, ',', '.') }})</small></div>
                                @endif
                            </td>
                            <td>{{ $r->transaksi->metode_bayar ?? '-' }}</td>
                            <td class="nominal-tebal">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $r->statusTernormalisasi() === 'Selesai' ? 'badge-aktif' : 'badge-pending' }}">
                                    {{ $r->statusTernormalisasi() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="teks-tengah-abu">Belum ada transaksi tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/laporan.js'])
@endpush
