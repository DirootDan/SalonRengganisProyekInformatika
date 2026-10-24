@extends('Karyawan.Stylist.layout')

@section('judul', 'Jadwal Reservasi Stylist - Salon Rengganis')

@section('isi')
    <div class="kepala-halaman">
        <div>
            <h1>Jadwal Reservasi Pribadi</h1>
            <p>Menampilkan daftar perawatan rambut yang ditugaskan khusus kepada <strong>{{ $petugas->nama_staff }}</strong>.</p>
        </div>

        <form method="GET" action="{{ route('stylist.dashboard') }}" class="filter-bar">
            <select name="mode" class="input-filter">
                <option value="semua" @selected($mode === 'semua')>Semua Jadwal</option>
                <option value="harian" @selected($mode === 'harian')>Tampilan Harian</option>
                <option value="mingguan" @selected($mode === 'mingguan')>Tampilan Mingguan</option>
            </select>
            <input type="date" name="tanggal" class="input-filter" value="{{ $tanggal }}">
            <button type="submit" class="btn-filter">Tampilkan</button>
        </form>
    </div>

    <div class="kartu-panel">
        <table class="tabel-jadwal">
            <thead>
                <tr>
                    <th>No. Reservasi</th>
                    <th>Tanggal &amp; Jam</th>
                    <th>Nama Pelanggan</th>
                    <th>Layanan Perawatan</th>
                    <th>Status Booking</th>
                    <th>Status Pengerjaan</th>
                    <th>Aksi Petugas</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($jadwal as $item)
                    <tr>
                        <td><strong>#{{ $item->id_reservasi }}</strong></td>
                        <td>{{ \Carbon\Carbon::parse($item->tgl_booking)->format('d/m/Y') }} &bull; {{ $item->jam_booking }} WIB</td>
                        <td>{{ $item->nama_cust }}<br><small>{{ $item->no_telp }}</small></td>
                        <td><strong>{{ $item->nama_layanan }}</strong></td>
                        <td><span class="lencana">{{ $item->status_reservasi }}</span></td>
                        <td>
                            <span class="lencana {{ $item->status_layanan === 'Selesai' ? 'lencana-hijau' : 'lencana-kuning' }}">
                                {{ $item->status_layanan }}
                            </span>
                        </td>
                        <td>
                            @if ($item->status_layanan !== 'Selesai')
                                <form method="POST" action="{{ route('stylist.layanan.selesai', [$item->id_reservasi, $item->id_layanan]) }}" class="form-selesai">
                                    @csrf
                                    <button type="submit" class="btn-selesai">✓ Tandai Selesai</button>
                                </form>
                            @else
                                <span class="teks-sudah">Sudah Selesai</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="kosong-tengah">Tidak ada jadwal reservasi untuk periode yang dipilih.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
