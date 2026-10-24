@extends('Manajer.layout')

@section('judul', 'Cek Laporan - Salon Rengganis')
@section('header', 'Laporan Keuangan & Kinerja')
@section('sub_header', 'Rekap pendapatan salon, riwayat transaksi, layanan terlaris, dan kinerja komisi karyawan.')

@push('styles')
    @vite(['resources/css/Manajer/laporan.css'])
@endpush

@section('isi')
    <!-- Filter Periode & Tombol Cetak / Ekspor -->
    <div class="panel-kartu panel-filter-laporan">
        <form method="GET" action="{{ route('manajer.laporan') }}" class="toolbar-atas tanpa-margin">
            <div class="toolbar-kiri">
                <div>
                    <label class="label-kecil">Dari Tanggal</label>
                    <input type="date" name="mulai" id="tglMulai" class="select-filter" value="{{ $tglMulai }}">
                </div>
                <div>
                    <label class="label-kecil">Sampai Tanggal</label>
                    <input type="date" name="selesai" id="tglSelesai" class="select-filter" value="{{ $tglSelesai }}">
                </div>
                <button type="submit" class="btn-sekunder btn-turun">
                    🔄 Terapkan Filter
                </button>
            </div>

            <div class="aksi-grup btn-turun">
                <a href="{{ route('manajer.laporan.ekspor', ['mulai' => $tglMulai, 'selesai' => $tglSelesai]) }}" class="btn-sekunder">
                    📥 Ekspor CSV / Excel
                </a>
                <button type="button" class="btn-utama" id="btnCetakLaporan">🖨️ Cetak PDF / Kertas</button>
            </div>
        </form>
    </div>

    <!-- Ringkasan Metrik Keuangan -->
    <div class="ringkasan-laporan">
        <div class="kartu-metrik">
            <span class="metrik-judul">Total Pendapatan Lunas</span>
            <span class="metrik-angka">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
            <span class="metrik-tren positif">Periode {{ $tglMulai }} s/d {{ $tglSelesai }}</span>
        </div>

        <div class="kartu-metrik">
            <span class="metrik-judul">Total Reservasi Periode Ini</span>
            <span class="metrik-angka">{{ $reservasi->count() }} Reservasi</span>
            <span class="metrik-tren positif">{{ $reservasi->where('status', 'Selesai')->count() }} Selesai &bull; {{ $reservasi->where('status', 'Hangus')->count() }} Hangus</span>
        </div>

        <div class="kartu-metrik">
            <span class="metrik-judul">Total Komisi Karyawan</span>
            <span class="metrik-angka">Rp {{ number_format($totalKomisi, 0, ',', '.') }}</span>
            <span class="metrik-tren">Dibagikan ke Stylist &amp; Therapist</span>
        </div>

        <div class="kartu-metrik">
            <span class="metrik-judul">Layanan Terlaris</span>
            <span class="metrik-angka teks-sedang">{{ $layananTerlaris->nama_layanan ?? 'Belum Ada' }}</span>
            <span class="metrik-tren">{{ $layananTerlaris ? $layananTerlaris->total_pesan . ' kali dipesan' : '-' }}</span>
        </div>
    </div>

    <!-- Tabel Kinerja Karyawan -->
    <div class="panel-kartu panel-filter-laporan">
        <h3 class="judul-sub-panel">Kinerja Stylist &amp; Therapist (Layanan Selesai)</h3>
        <div class="tabel-Responsif">
            <table class="tabel-data">
                <thead>
                    <tr>
                        <th>Nama Karyawan</th>
                        <th>Jabatan</th>
                        <th>Jumlah Layanan Diselesaikan</th>
                        <th>Total Komisi Diterima</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kinerjaKaryawan as $k)
                        <tr>
                            <td><strong>{{ $k->nama_staff }}</strong></td>
                            <td><span class="badge badge-{{ strtolower($k->role_jabatan) }}">{{ $k->role_jabatan }}</span></td>
                            <td>{{ $k->jumlah_layanan }} Layanan</td>
                            <td class="nominal-tebal">Rp {{ number_format($k->total_komisi, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="teks-tengah-abu">Belum ada layanan selesai pada periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabel Rincian Transaksi & Reservasi -->
    <div class="panel-kartu">
        <h3 class="judul-sub-panel">Rincian Transaksi &amp; Reservasi Periode Ini</h3>

        <div class="tabel-Responsif">
            <table class="tabel-data" id="tabelLaporan">
                <thead>
                    <tr>
                        <th>No. Nota / ID</th>
                        <th>Tanggal &amp; Jam</th>
                        <th>Pelanggan</th>
                        <th>Rincian Perawatan</th>
                        <th>Metode Bayar</th>
                        <th>Total Tagihan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservasi as $r)
                        @php
                            $totalBaris = $r->transaksi ? $r->transaksi->total_bayar : $r->layanan->sum('harga_layanan');
                        @endphp
                        <tr>
                            <td><strong>#INV-{{ str_pad($r->id_reservasi, 4, '0', STR_PAD_LEFT) }}</strong></td>
                            <td>{{ $r->tgl_booking->format('d/m/Y') }} ({{ $r->jam_booking }})</td>
                            <td>{{ $pelangganMap[$r->id_pelanggan] ?? 'Pelanggan' }}</td>
                            <td>{{ $r->layanan->pluck('nama_layanan')->join(', ') ?: '-' }}</td>
                            <td>{{ $r->transaksi->metode_bayar ?? '-' }}</td>
                            <td class="nominal-tebal">Rp {{ number_format($totalBaris, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge {{ $r->statusTernormalisasi() === 'Selesai' ? 'badge-aktif' : 'badge-manajer' }}">
                                    {{ $r->statusTernormalisasi() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="teks-tengah-abu">Tidak ada data reservasi pada rentang tanggal ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Manajer/laporan.js'])
@endpush
