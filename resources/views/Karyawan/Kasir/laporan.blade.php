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
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reservasi as $r)
                <tr>
                    <td><strong>#INV-{{ str_pad($r->id_reservasi, 4, '0', STR_PAD_LEFT) }}</strong></td>
                    <td>{{ $r->tgl_booking->format('d/m/Y') }} ({{ $r->jam_booking }}{{ $r->jam_selesai ? ' - ' . $r->jam_selesai : '' }})</td>
                    <td>{{ $r->namaPelangganTampil() }}</td>
                    <td>
                        <div>{{ $r->ringkasanNamaLayanan() ?: '-' }}</div>
                        @if ($r->ringkasanProdukAddon())
                        <small>+ Produk: {{ $r->ringkasanProdukAddon() }}</small>
                        @endif
                        @if ($r->kode_promo)
                        <div><small class="badge badge-aktif">Promo: {{ $r->kode_promo }} (-Rp {{ number_format((float) $r->nominal_diskon, 0, ',', '.') }})</small></div>
                        @endif
                    </td>
                    <td>{{ $r->transaksi->metode_bayar ?? '-' }}</td>
                    <td class="nominal-tebal">Rp {{ number_format($r->nominalTampilLaporan(), 0, ',', '.') }}</td>
                    <td>
                        <span class="badge {{ $r->statusTernormalisasi() === 'Selesai' ? 'badge-aktif' : 'badge-pending' }}">
                            {{ $r->statusTernormalisasi() }}
                        </span>
                    </td>
                    <td>
                        @if ($r->transaksi)
                        <a href="{{ route('kasir.struk.cetak', $r->transaksi->id_transaksi) }}" target="_blank" class="btn-aksi btn-edit">🖨️ Cetak</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="teks-tengah-abu">Belum ada transaksi tercatat.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if (method_exists($reservasi, 'links'))
    <div class="paginasi-wadah">
        {{ $reservasi->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
@vite(['resources/js/Karyawan/Kasir/laporan.js'])
@endpush