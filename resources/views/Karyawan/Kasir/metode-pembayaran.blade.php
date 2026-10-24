@extends('Karyawan.Kasir.layout')

@section('judul', 'Metode Pembayaran - Kasir Salon Rengganis')
@section('header', 'Metode Pembayaran Kasir')
@section('sub_header', 'Daftar kanal pembayaran yang aktif dan siap digunakan saat melayani pelanggan.')

@push('styles')
    @vite(['resources/css/Karyawan/Kasir/metode.css'])
@endpush

@section('isi')
    <div class="toolbar-atas">
        <p class="tanpa-margin">
            Berikut adalah metode pembayaran yang telah dikonfigurasi untuk meja Kasir.
        </p>
    </div>

    <div class="metode-grid">
        @foreach ($metode as $m)
            <div class="kartu-metode {{ $m->aktif ? 'aktif' : 'nonaktif' }}">
                <div class="metode-atas">
                    <div class="metode-identitas">
                        <span class="metode-ikon">{{ $m->ikon }}</span>
                        <div>
                            <span class="metode-kategori">{{ $m->kategori }}</span>
                            <h3 class="metode-nama">{{ $m->nama_metode }}</h3>
                        </div>
                    </div>

                    <label class="sakelar" title="Aktifkan / Nonaktifkan">
                        <input type="checkbox" class="toggle-metode"
                               data-nama="{{ $m->nama_metode }}"
                               @checked($m->aktif)>
                        <span class="slider"></span>
                    </label>
                </div>

                <div class="metode-detail">
                    <div class="baris-info">
                        <span>Nomor / Keterangan</span>
                        <strong>{{ $m->nomor_rekening }}</strong>
                    </div>
                    <div class="baris-info">
                        <span>Atas Nama</span>
                        <strong>{{ $m->atas_nama }}</strong>
                    </div>
                </div>

                <div class="metode-kaki">
                    <span class="status-teks">{{ $m->aktif ? '● Aktif di Kasir' : '○ Nonaktif' }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    @vite(['resources/js/Karyawan/Kasir/metode.js'])
@endpush
