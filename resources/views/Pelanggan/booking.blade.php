@extends('pelanggan.layout')

@section('judul', 'Booking - Salon Rengganis')

@section('isi')
    <h1 class="judul-halaman">Booking Perawatan</h1>
    <p class="sub-halaman">Pilih tanggal dan satu atau lebih layanan.</p>

    @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                ⚠️ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('pelanggan.booking.simpan') }}" id="loginForm" class="form-box">
        @csrf

        <label for="tgl_booking" class="label">Tanggal Booking</label>
        <input type="date" id="tgl_booking" name="tgl_booking"
               value="{{ old('tgl_booking') }}" min="{{ now()->toDateString() }}" required>

        <div class="label">Pilih Layanan</div>
        <div class="daftar-opsi">
            @foreach ($layanan as $item)
                <label class="opsi">
                    <input type="checkbox" name="layanan[]" value="{{ $item->id_layanan }}"
                           @checked(in_array($item->id_layanan, old('layanan', [])))>
                    <span class="opsi-nama">{{ $item->nama_layanan }}</span>
                    <span class="opsi-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</span>
                </label>
            @endforeach
        </div>

        <button type="submit" id="submitBtn" class="btn btn-penuh">KIRIM BOOKING</button>
    </form>
@endsection