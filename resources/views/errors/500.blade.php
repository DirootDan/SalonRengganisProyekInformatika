@extends('errors.layout')

@section('kode', '500')
@section('ikon', '🛠️')
@section('judul', 'Terjadi Gangguan pada Sistem')
@section('pesan', 'Maaf, ada kendala di sisi kami. Tim sedang menanganinya. Silakan coba lagi beberapa saat lagi.')
@section('aksi')
    <a href="javascript:location.reload()" class="er-tombol">Muat Ulang Halaman</a>
@endsection
