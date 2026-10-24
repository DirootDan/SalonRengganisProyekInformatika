@extends('pelanggan.layout')

@section('judul', 'Beranda - Salon Rengganis')

@section('isi')
    <div style="background: white; border-radius: 24px; padding: 60px 40px; text-align: center; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); margin-bottom: 30px;">
        <h1 class="judul-halaman" style="margin-bottom: 16px;">Selamat Datang, {{ auth('pelanggan')->user()->nama_cust }}!</h1>
        <p class="sub-halaman" style="margin-bottom: 0;">Kami siap memberikan pelayanan terbaik untuk kecantikan dan perawatan Anda hari ini.</p>
    </div>

    <div class="kosong" style="border: 2px dashed #f3f4f6; background: transparent; box-shadow: none;">
        <p style="margin-bottom: 16px;">--- Ruang Kosong untuk Informasi Salon ---<br>
        (Bisa diisi banner promo, jam operasional, atau artikel kecantikan nantinya)</p>
        
        <a href="{{ route('pelanggan.layanan') }}" class="btn">Lihat Daftar Layanan</a>
    </div>
@endsection