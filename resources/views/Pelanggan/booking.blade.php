@extends('pelanggan.layout')

@section('judul', 'Booking - Salon Rengganis')

@section('isi')
    <h1 class="judul-halaman">Booking Perawatan</h1>
    <p class="sub-halaman">Pilih tanggal dan layanan. Cari layanan dengan cepat menggunakan kotak pencarian.</p>

    @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                ⚠️ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('pelanggan.booking.simpan') }}" id="loginForm" class="form-box">
        @csrf

        <label for="tgl_booking" class="label">Pilih Tanggal Booking</label>
        <input type="date" id="tgl_booking" name="tgl_booking"
               value="{{ old('tgl_booking') }}" min="{{ now()->toDateString() }}" required>

        <label for="searchInput" class="label">Pilih Layanan</label>
        <!-- Fitur Live Search -->
        <input type="text" id="searchInput" class="search-input" placeholder="Ketik nama perawatan (contoh: Creambath, Spa...)">

        <!-- Area Scroll Internal -->
        <div class="daftar-opsi">
            @foreach ($layanan as $item)
                <label class="opsi" data-nama="{{ $item->nama_layanan }}">
                    <input type="checkbox" name="layanan[]" value="{{ $item->id_layanan }}" 
                           data-harga="{{ $item->harga_layanan }}"
                           @checked(in_array($item->id_layanan, old('layanan', [])))>
                    
                    <div class="opsi-info">
                        <span class="opsi-nama">{{ $item->nama_layanan }}</span>
                        <span class="opsi-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</span>
                    </div>
                </label>
            @endforeach
        </div>

        <!-- Ringkasan Total Harga -->
        <div class="summary-box">
            <div class="summary-text" id="jumlahPilihan">0 Layanan Dipilih</div>
            <div class="summary-total" id="totalHarga">Rp 0</div>
        </div>

        <button type="submit" id="submitBtn" class="btn btn-penuh">KONFIRMASI BOOKING</button>
    </form>
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/booking.js'])
@endpush