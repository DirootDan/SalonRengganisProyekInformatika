@extends('Karyawan.Therapist.layout')

@section('judul', 'Laporan Komisi Therapist - Salon Rengganis')

@section('isi')
    <div class="kepala-halaman">
        <div>
            <h1>Laporan Komisi Therapist</h1>
            <p>Komisi dihitung sebesar <strong>{{ number_format($petugas->persen_komisi, 0) }}%</strong> dari layanan yang telah selesai dan dibayar lunas.</p>
        </div>

        <form method="GET" action="{{ route('therapist.komisi') }}" class="filter-bar">
            <input type="date" name="mulai" class="input-filter" value="{{ $tglMulai }}">
            <input type="date" name="selesai" class="input-filter" value="{{ $tglSelesai }}">
            <button type="submit" class="btn-filter">Filter Periode</button>
        </form>
    </div>

    <div class="ringkasan-komisi">
        <div class="kartu-metrik">
            <span>Total Layanan Lunas</span>
            <strong>{{ $daftarKomisi->count() }} Perawatan</strong>
        </div>
        <div class="kartu-metrik">
            <span>Total Nilai Layanan</span>
            <strong>Rp {{ number_format($totalOmzet, 0, ',', '.') }}</strong>
        </div>
        <div class="kartu-metrik unggulan">
            <span>Total Komisi Diterima</span>
            <strong>Rp {{ number_format($totalKomisi, 0, ',', '.') }}</strong>
        </div>
    </div>

    <div class="kartu-panel">
        <table class="tabel-jadwal">
            <thead>
                <tr>
                    <th>ID Reservasi</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Layanan</th>
                    <th>Harga Layanan (Snapshot)</th>
                    <th>Persentase</th>
                    <th>Nominal Komisi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($daftarKomisi as $k)
                    <tr>
                        <td><strong>#{{ $k->id_reservasi }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($k->tgl_booking)->format('d/m/Y') }}</td>
                        <td>{{ $k->nama_cust }}</td>
                        <td><strong>{{ $k->nama_layanan }}</strong></td>
                        <td>Rp {{ number_format($k->harga_saat_ini, 0, ',', '.') }}</td>
                        <td>{{ number_format($k->persen_komisi, 0) }}%</td>
                        <td class="teks-komisi">Rp {{ number_format($k->nominal_komisi, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="kosong-tengah">Belum ada komisi dari layanan yang selesai dan dibayar pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
