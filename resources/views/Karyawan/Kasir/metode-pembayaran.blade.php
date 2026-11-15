@extends('Karyawan.Kasir.layout')

@section('judul', 'Metode Pembayaran - Kasir Salon Rengganis')
@section('header', 'Metode Pembayaran Kasir')
@section('sub_header', 'Daftar kanal pembayaran yang aktif dan siap digunakan saat melayani pelanggan.')

@push('styles')
    @vite(['resources/css/pages/staf/katalog.css'])
@endpush

@section('isi')
    <div class="toolbar-atas">
        <p class="tanpa-margin">
            Daftar kanal pembayaran bersifat baca-saja bagi Kasir. Pengaturan aktif atau nonaktif kanal dikelola oleh Manajer.
        </p>
    </div>

    <div class="metode-grid">
        @foreach ($metode as $m)
            <div class="kartu-metode {{ $m->aktif ? 'aktif' : 'nonaktif' }}">
                <div class="metode-atas">
                    <div class="metode-identitas">
                        <span class="metode-ikon" aria-hidden="true">{{ $m->ikon }}</span>
                        <div>
                            <span class="metode-kategori">{{ $m->kategori }}</span>
                            <h2 class="metode-nama">{{ $m->nama_metode }}</h2>
                        </div>
                    </div>

                    <span class="lencana-metode-kasir {{ $m->aktif ? 'aktif' : 'nonaktif' }}">
                        {{ $m->aktif ? 'Aktif' : 'Nonaktif' }}
                    </span>
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
                    <span class="status-teks">{{ $m->aktif ? 'Tersedia di transaksi Kasir' : 'Dinonaktifkan oleh Manajer' }}</span>
                </div>
            </div>
        @endforeach
    </div>
@endsection
