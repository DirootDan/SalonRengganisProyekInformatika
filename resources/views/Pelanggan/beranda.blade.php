@extends('pelanggan.layout')

@section('judul', 'Beranda - Salon Rengganis')

@section('isi')
    <div class="kartu-sambutan">
        <h1 class="judul-halaman">Selamat Datang, {{ auth('pelanggan')->user()->nama_cust }}!</h1>
        <p class="sub-halaman">Kami siap memberikan pelayanan terbaik untuk kecantikan dan perawatan Anda hari ini.</p>
    </div>

    <div class="kosong">
        <p>Nikmati perawatan rambut, wajah, dan tubuh bersama Stylist serta Therapist berpengalaman di Salon Rengganis.</p>
        <a href="{{ route('pelanggan.layanan') }}" class="btn">Lihat Daftar Layanan</a>
    </div>
@endsection
