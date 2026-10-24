@extends('pelanggan.layout')

@section('judul', 'Layanan - Salon Rengganis')

@section('isi')
    <h1 class="judul-halaman">Layanan Kami</h1>
    <p class="sub-halaman">Pilih perawatan favorit Anda, lalu booking dalam satu langkah.</p>

    @if ($layanan->isEmpty())
        <div class="kosong">Belum ada layanan yang tersedia.</div>
    @else
        <div class="grid-layanan">
            @foreach ($layanan as $item)
                <div class="kartu">
                    <div class="kartu-ikon">✨</div>
                    <h3>{{ $item->nama_layanan }}</h3>
                    <div class="harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>

        <div class="aksi-tengah">
            <a href="{{ route('pelanggan.booking') }}" class="btn">Booking Sekarang</a>
        </div>
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/beranda.js'])
@endpush