@extends('pelanggan.layout')

@section('judul', 'Riwayat - Salon Rengganis')

@section('isi')
    <h1 class="judul-halaman">Riwayat Reservasi</h1>
    <p class="sub-halaman">Semua booking yang pernah Anda buat. Klik pada reservasi untuk melihat rincian.</p>

    @forelse ($reservasi as $r)
        <div class="reservasi-item">
            <!-- Header (Selalu Muncul & Bisa Diklik) -->
            <div class="reservasi-atas">
                <div class="reservasi-info-utama">
                    <div class="reservasi-tgl">{{ $r->tgl_booking->format('d/m/Y') }}</div>
                    <div class="reservasi-no">Reservasi #{{ $r->id_reservasi }}</div>
                </div>
                <div class="reservasi-kanan">
                    <span class="status status-{{ strtolower($r->status) }}">{{ ucfirst($r->status) }}</span>
                    <!-- Ikon Panah untuk UI interaktif -->
                    <svg class="chevron-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                </div>
            </div>

            <!-- Body (Disembunyikan secara bawaan) -->
            <div class="reservasi-body">
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
        </div>
    @empty
        <div class="kosong">
            Belum ada reservasi.<br>
            <a href="{{ route('pelanggan.booking') }}">Buat booking pertama Anda</a>
        </div>
    @endforelse
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/riwayat.js'])
@endpush