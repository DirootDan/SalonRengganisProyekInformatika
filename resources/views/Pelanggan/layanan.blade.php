@extends('Pelanggan.layout')

@section('judul', 'Layanan - Salon Rengganis')

@push('styles')
    @vite(['resources/css/Pelanggan/layanan.css'])
@endpush

@section('isi')
    <h1 class="judul-halaman">Layanan Kami</h1>
    <p class="sub-halaman">Pilih perawatan favorit Anda, lalu klik tombol pada layanan untuk langsung menuju form booking dengan layanan tercentang otomatis.</p>

    @if ($layanan->isEmpty())
        <div class="kosong">Belum ada layanan yang tersedia.</div>
    @else
        <div class="grid-layanan">
            @foreach ($layanan as $item)
                <div class="kartu">
                    <div class="kartu-ikon"><x-ikon :name="$item->jabatan_petugas === 'Stylist' ? 'gunting' : 'daun'" /></div>
                    <span class="lencana-kategori-layanan">{{ $item->kategori }}</span>
                    <h2>{{ $item->nama_layanan }}</h2>
                    <p class="meta-layanan">⏱ {{ (int) ($item->durasi_menit ?: 60) }} menit &bull; Ditangani {{ $item->jabatan_petugas }}</p>
                    <div class="harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</div>
                    <div class="kartu-aksi-booking">
                        <a href="{{ route('pelanggan.booking', ['layanan' => $item->id_layanan]) }}" class="btn-pilih-layanan">
                            Pilih &amp; Booking Layanan Ini &rarr;
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="aksi-tengah">
            <a href="{{ route('pelanggan.booking') }}" class="btn">Booking Beberapa Layanan Sekaligus</a>
        </div>
    @endif
@endsection

@push('scripts')
    @vite(['resources/js/Pelanggan/beranda.js'])
@endpush
