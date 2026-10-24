@extends('pelanggan.layout')

@section('judul', 'Booking - Salon Rengganis')

@push('styles')
    @vite(['resources/css/pelanggan/booking.css'])
@endpush

@section('isi')
    <h1 class="judul-halaman">Booking Perawatan</h1>
    <p class="sub-halaman">Tentukan tanggal kedatangan, pilih layanan, lalu pilih petugas yang akan menangani Anda.</p>

    @if ($errors->any())
        <div class="error-msg">
            @foreach ($errors->all() as $error)
                ⚠️ {{ $error }}<br>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('pelanggan.booking.simpan') }}" id="formBooking" class="form-box">
        @csrf

        <!-- Langkah 1: Tanggal -->
        <div class="langkah-judul"><span class="langkah-no">1</span> Pilih Tanggal Kedatangan</div>
        <input type="date" id="tgl_booking" name="tgl_booking"
               value="{{ old('tgl_booking') }}" min="{{ now()->toDateString() }}" required>

        <!-- Langkah 2: Layanan & Petugas -->
        <div class="langkah-judul"><span class="langkah-no">2</span> Pilih Layanan &amp; Petugas</div>
        <p class="langkah-ket">
            Layanan rambut ditangani oleh <strong>Stylist</strong>, sedangkan layanan perawatan wajah dan kuku ditangani oleh <strong>Therapist</strong>.
            Daftar petugas akan muncul otomatis sesuai layanan yang Anda pilih.
        </p>

        <!-- Fitur Pencarian Langsung -->
        <input type="text" id="searchInput" class="search-input" placeholder="Ketik nama perawatan (contoh: Creambath, Facial...)">

        <!-- Area Gulir Internal -->
        <div class="daftar-opsi">
            @foreach ($layanan as $item)
                @php
                    $jabatan      = $item->jabatan_petugas;
                    $daftarPetugas = $petugas->get($jabatan, collect());
                    $dipilih      = in_array($item->id_layanan, old('layanan', []));
                    $petugasLama  = old('petugas.' . $item->id_layanan);
                @endphp

                <div class="opsi-blok {{ $errors->has('petugas.' . $item->id_layanan) ? 'galat' : '' }}"
                     data-nama="{{ $item->nama_layanan }}">
                    <label class="opsi">
                        <input type="checkbox" name="layanan[]" value="{{ $item->id_layanan }}"
                               data-harga="{{ $item->harga_layanan }}"
                               @checked($dipilih)
                               @disabled($daftarPetugas->isEmpty())>

                        <div class="opsi-info">
                            <span class="opsi-nama">{{ $item->nama_layanan }}</span>
                            <span class="opsi-harga">Rp {{ number_format($item->harga_layanan, 0, ',', '.') }}</span>
                        </div>

                        <span class="lencana-jabatan lencana-{{ strtolower($jabatan) }}">{{ $jabatan }}</span>
                    </label>

                    <div class="pilih-petugas">
                        @if ($daftarPetugas->isEmpty())
                            <small class="petugas-kosong">Saat ini belum ada {{ $jabatan }} yang tersedia.</small>
                        @else
                            <label for="petugas_{{ $item->id_layanan }}" class="petugas-label">Pilih {{ $jabatan }}</label>
                            <select id="petugas_{{ $item->id_layanan }}"
                                    name="petugas[{{ $item->id_layanan }}]"
                                    class="select-petugas"
                                    @disabled(! $dipilih)>
                                <option value="">-- Pilih {{ $jabatan }} --</option>
                                @foreach ($daftarPetugas as $staf)
                                    <option value="{{ $staf->id_karyawan }}" @selected($petugasLama == $staf->id_karyawan)>
                                        {{ $staf->nama_staff }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <p class="tidak-ditemukan" id="tidakDitemukan">Layanan yang Anda cari tidak ditemukan.</p>

        <!-- Ringkasan Total Harga -->
        <div class="summary-box">
            <div class="summary-text" id="jumlahPilihan">0 layanan dipilih</div>
            <div class="summary-total" id="totalHarga">Rp 0</div>
        </div>
        <small class="info-validasi" id="infoValidasi"></small>

        <button type="submit" id="submitBtn" class="btn btn-penuh">KONFIRMASI BOOKING</button>
    </form>
@endsection

@push('scripts')
    @vite(['resources/js/pelanggan/booking.js'])
@endpush
