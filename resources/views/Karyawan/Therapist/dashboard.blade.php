@extends('Karyawan.Therapist.layout')

@section('judul', 'Jadwal Reservasi Therapist - Salon Rengganis')

@section('isi')
    <div class="kepala-halaman">
        <div>
            <h1>Jadwal Reservasi Pribadi</h1>
            <p>Menampilkan daftar perawatan wajah &amp; tubuh yang ditugaskan khusus kepada <strong>{{ $petugas->nama_staff }}</strong>.</p>
        </div>

        <form method="GET" action="{{ route('therapist.dashboard') }}" class="filter-bar">
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
                                @php
                                    $bisaDitandaiSelesai = in_array(strtolower((string) $item->status_reservasi), ['diterima', 'selesai'], true);
                                @endphp
                                @if ($bisaDitandaiSelesai)
                                    <form method="POST" action="{{ route('therapist.layanan.selesai', [$item->id_reservasi, $item->id_layanan]) }}"
                                          class="form-selesai"
                                          data-confirm-title="Konfirmasi Penyelesaian Layanan"
                                          data-confirm-item="{{ $item->nama_layanan }} (#{{ $item->id_reservasi }})">
                                        @csrf
                                        <button type="submit" class="btn-selesai">✓ Tandai Selesai</button>
                                    </form>
                                @else
                                    <button type="button"
                                            class="btn-selesai btn-selesai-nonaktif"
                                            disabled
                                            title="Menunggu konfirmasi Kasir terlebih dahulu (Status Booking: {{ $item->status_reservasi }})">
                                        ✓ Tandai Selesai
                                    </button>
                                @endif
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
