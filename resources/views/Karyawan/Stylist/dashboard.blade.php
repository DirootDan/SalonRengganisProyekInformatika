
@extends('Karyawan.Stylist.layout')

@section('judul', 'Jadwal Reservasi Stylist - Salon Rengganis')

@section('isi')

<style>
    .ringkasan-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
        margin: 25px 0;
    }

    .ringkasan-card {
        background: #ffffff;
        border: 1px solid #eeeeee;
        border-radius: 14px;
        padding: 22px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
        position: relative;
        overflow: hidden;
    }

    .ringkasan-card h3 {
        font-size: 14px;
        font-weight: 500;
        color: #777777;
        margin: 0 0 12px;
    }

    .ringkasan-card .angka {
        font-size: 30px;
        font-weight: 700;
        color: #292929;
        margin: 0;
    }

    .ringkasan-card .keterangan {
        font-size: 12px;
        color: #999999;
        margin-top: 8px;
    }

    .ringkasan-card::after {
        content: '';
        position: absolute;
        width: 5px;
        height: 100%;
        left: 0;
        top: 0;
        background: #b78b61;
    }

    .ringkasan-card.selesai::after {
        background: #279b65;
    }

    .ringkasan-card.belum::after {
        background: #e6a23c;
    }

    .ringkasan-card.hari-ini::after {
        background: #5688d8;
    }

    @media (max-width: 900px) {
        .ringkasan-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 500px) {
        .ringkasan-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="kepala-halaman">
    <div>
        <h1>Jadwal Reservasi Pribadi</h1>
        <p>
            Menampilkan daftar perawatan rambut yang ditugaskan khusus kepada
            <strong>{{ $petugas->nama_staff }}</strong>.
        </p>
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

{{-- RINGKASAN DASHBOARD --}}
<div class="ringkasan-grid">

    <div class="ringkasan-card">
        <h3>Total Layanan</h3>
        <p class="angka">{{ $totalJadwal }}</p>
        <p class="keterangan">Total layanan sesuai filter</p>
    </div>

    <div class="ringkasan-card belum">
        <h3>Layanan Belum Selesai</h3>
        <p class="angka">{{ $layananBelumSelesai }}</p>
        <p class="keterangan">Layanan yang masih dikerjakan</p>
    </div>

    <div class="ringkasan-card selesai">
        <h3>Layanan Selesai</h3>
        <p class="angka">{{ $layananSelesai }}</p>
        <p class="keterangan">Layanan yang telah diselesaikan</p>
    </div>

    <div class="ringkasan-card hari-ini">
        <h3>Layanan Hari Ini</h3>
        <p class="angka">{{ $jadwalHariIni }}</p>
        <p class="keterangan">Jadwal khusus hari ini</p>
    </div>

</div>

{{-- TABEL JADWAL RESERVASI --}}
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

                    <td>
                        {{ \Carbon\Carbon::parse($item->tgl_booking)->format('d/m/Y') }}
                        &bull; {{ $item->jam_booking }} WIB
                    </td>

                    <td>
                        {{ $item->nama_cust }}<br>
                        <small>{{ $item->no_telp }}</small>
                    </td>

                    <td><strong>{{ $item->nama_layanan }}</strong></td>

                    <td>
                        <span class="lencana">{{ $item->status_reservasi }}</span>
                    </td>

                    <td>
                        <span class="lencana {{ $item->status_layanan === 'Selesai' ? 'lencana-hijau' : 'lencana-kuning' }}">
                            {{ $item->status_layanan }}
                        </span>
                    </td>

                    <td>
                        @if ($item->status_layanan !== 'Selesai')

                            @php
                                $bisaDitandaiSelesai = in_array(
                                    strtolower((string) $item->status_reservasi),
                                    ['diterima', 'selesai'],
                                    true
                                );
                            @endphp

                            @if ($bisaDitandaiSelesai)
                                <form
                                    method="POST"
                                    action="{{ route('stylist.layanan.selesai', [$item->id_reservasi, $item->id_layanan]) }}"
                                    class="form-selesai"
                                    data-confirm-title="Konfirmasi Penyelesaian Layanan"
                                    data-confirm-item="{{ $item->nama_layanan }} (#{{ $item->id_reservasi }})"
                                >
                                    @csrf
                                    <button type="submit" class="btn-selesai">
                                        ✓ Tandai Selesai
                                    </button>
                                </form>
                            @else
                                <button
                                    type="button"
                                    class="btn-selesai btn-selesai-nonaktif"
                                    disabled
                                    title="Menunggu konfirmasi Kasir terlebih dahulu (Status Booking: {{ $item->status_reservasi }})"
                                >
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
                    <td colspan="7" class="kosong-tengah">
                        Tidak ada jadwal reservasi untuk periode yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
