@extends('Manajer.layout')

@section('judul', 'Cek Laporan - Salon Rengganis')
@section('header', 'Laporan Keuangan & Kinerja')
@section('sub_header', 'Rekap pendapatan salon, riwayat transaksi, dan komisi petugas berdasarkan periode.')

@push('styles')
    @vite(['resources/css/Manajer/laporan.css'])
@endpush

@section('isi')
    <!-- Filter Periode & Tombol Cetak -->
    <div class="panel-kartu" style="margin-bottom: 24px;">
        <div class="toolbar-atas" style="margin-bottom: 0;">
            <div class="toolbar-kiri">
                <div>
                    <label class="label-kecil">Dari Tanggal</label>
                    <input type="date" id="tglMulai" class="select-filter" value="{{ now()->startOfMonth()->toDateString() }}">
                </div>
                <div>
                    <label class="label-kecil">Sampai Tanggal</label>
                    <input type="date" id="tglSelesai" class="select-filter" value="{{ now()->toDateString() }}">
                </div>
                <div>
                    <label class="label-kecil">Kategori Laporan</label>
                    <select id="jenisLaporan" class="select-filter">
                        <option value="semua">Semua Transaksi &amp; Layanan</option>
                        <option value="layanan">Pendapatan Layanan</option>
                        <option value="produk">Penjualan Produk</option>
                        <option value="komisi">Komisi Stylist &amp; Therapist</option>
                    </select>
                </div>
                <button type="button" class="btn-sekunder" id="btnTerapkanFilter" style="margin-top: 18px;">
                    🔄 Terapkan Filter
                </button>
            </div>

            <div class="aksi-grup" style="margin-top: 18px;">
                <button type="button" class="btn-sekunder" id="btnEksporCsv">📥 Ekspor CSV</button>
                <button type="button" class="btn-utama" id="btnCetakLaporan">🖨️ Cetak Laporan</button>
            </div>
        </div>
    </div>

    <!-- Ringkasan Metrik Keuangan -->
    <div class="ringkasan-laporan">
        <div class="kartu-metrik">
            <span class="metrik-judul">Total Pendapatan Kotor</span>
            <span class="metrik-angka" id="metrikPendapatan">Rp 4.850.000</span>
            <span class="metrik-tren positif">▲ +14,2% dibanding bulan lalu</span>
        </div>

        <div class="kartu-metrik">
            <span class="metrik-judul">Total Reservasi Selesai</span>
            <span class="metrik-angka" id="metrikTransaksi">28 Transaksi</span>
            <span class="metrik-tren positif">▲ Rata-rata Rp 173.000 / kunjungan</span>
        </div>

        <div class="kartu-metrik">
            <span class="metrik-judul">Total Komisi Petugas (15%)</span>
            <span class="metrik-angka" id="metrikKomisi">Rp 727.500</span>
            <span class="metrik-tren">Dibagikan ke Stylist &amp; Therapist</span>
        </div>

        <div class="kartu-metrik">
            <span class="metrik-judul">Layanan Terpopuler</span>
            <span class="metrik-angka" style="font-size: 20px;">Creambath &amp; Facial</span>
            <span class="metrik-tren">42% dari total pemesanan</span>
        </div>
    </div>

    <!-- Tabel Rincian Transaksi & Reservasi -->
    <div class="panel-kartu">
        <h3 style="margin: 0 0 16px; font-size: 17px;">Rincian Transaksi &amp; Reservasi Periode Ini</h3>

        <div class="tabel-Responsif">
            <table class="tabel-data" id="tabelLaporan">
                <thead>
                    <tr>
                        <th>No. Nota / ID</th>
                        <th>Tanggal</th>
                        <th>Pelanggan</th>
                        <th>Rincian Perawatan</th>
                        <th>Metode Bayar</th>
                        <th>Total Bayar</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reservasi as $r)
                        <tr>
                            <td><strong>#INV-{{ str_pad($r->id_reservasi, 4, '0', STR_PAD_LEFT) }}</strong></td>
                            <td>{{ $r->tgl_booking->format('d/m/Y') }}</td>
                            <td>{{ $pelangganMap[$r->id_pelanggan] ?? 'Pelanggan' }}</td>
                            <td>{{ $r->layanan->pluck('nama_layanan')->join(', ') ?: '-' }}</td>
                            <td>QRIS / Tunai</td>
                            <td class="nominal-tebal">Rp {{ number_format($r->layanan->sum('harga_layanan'), 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $r->status === 'selesai' ? 'badge-aktif' : 'badge-manajer' }}">
                                    {{ ucfirst($r->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach

                    <!-- Contoh Baris Transaksi Historis untuk Kebutuhan Tampilan Laporan -->
                    <tr>
                        <td><strong>#INV-0018</strong></td>
                        <td>{{ now()->subDays(1)->format('d/m/Y') }}</td>
                        <td>Siti Nurhaliza</td>
                        <td>Hair Coloring, Creambath</td>
                        <td>Transfer BCA</td>
                        <td class="nominal-tebal">Rp 330.000</td>
                        <td><span class="badge badge-aktif">Selesai</span></td>
                    </tr>
                    <tr>
                        <td><strong>#INV-0017</strong></td>
                        <td>{{ now()->subDays(2)->format('d/m/Y') }}</td>
                        <td>Nadia Vega</td>
                        <td>Facial, Manicure &amp; Pedicure</td>
                        <td>QRIS All Payment</td>
                        <td class="nominal-tebal">Rp 220.000</td>
                        <td><span class="badge badge-aktif">Selesai</span></td>
                    </tr>
                    <tr>
                        <td><strong>#INV-0016</strong></td>
                        <td>{{ now()->subDays(3)->format('d/m/Y') }}</td>
                        <td>Putri Marino</td>
                        <td>Potong Rambut, Creambath</td>
                        <td>Tunai (Cash)</td>
                        <td class="nominal-tebal">Rp 130.000</td>
                        <td><span class="badge badge-aktif">Selesai</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/laporan.js'])
@endpush
