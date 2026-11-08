@extends('errors.layout')

@section('kode', '419')
@section('ikon', '⏳')
@section('judul', 'Sesi Anda Telah Berakhir')
@section('pesan', 'Demi keamanan, halaman yang terlalu lama dibiarkan terbuka akan kedaluwarsa. Data Anda aman. Silakan masuk kembali lalu ulangi langkah terakhir.')
@section('aksi')
    <a href="{{ route('login') }}" class="er-tombol">Masuk Kembali</a>
@endsection
