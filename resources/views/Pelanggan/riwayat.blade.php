@extends('pelanggan.layout')

@section('judul', 'Riwayat - Salon Rengganis')

@section('isi')
    <h1 class="judul-halaman">Riwayat Reservasi</h1>
    <p class="sub-halaman">Semua booking yang pernah Anda buat.</p>

    @forelse ($reservasi as $r)
        <div class="reservasi-item">
            <div class="reservasi-atas">
                <div>
                    <div class="reservasi-tgl">{{ $r->tgl_booking->format('d/m/Y') }}</div>
                    <div class="reservasi-no">Reservasi #{{ $r->id_reservasi }}</div>
                </div>
                <span class="status status-{{ strtolower($r->status) }}">{{ ucfirst($r->status) }}</span>
            </div>

            <ul class="reservasi-layanan">
                @foreach ($r->layanan as $l)
                    <li>
                        <span>{{ $l->nama_layanan }}</span>
                        <span>Rp {{ number_format($l->harga_layanan, 0, ',', '.') }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="reservasi-total">
                Total: Rp {{ number_format($r->layanan->sum('harga_layanan'), 0, ',', '.') }}
            </div>
        </div>
    @empty
        <div class="kosong">
            Belum ada reservasi.
            <a href="{{ route('pelanggan.booking') }}">Buat booking pertama Anda</a>
        </div>
    @endforelse
@endsection