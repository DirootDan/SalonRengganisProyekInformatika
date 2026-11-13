<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\PengaturanStruk;
use App\Models\Transaksi;
use App\Services\ReservasiService;

class StrukController extends Controller
{
    public function __construct(
        private readonly ReservasiService $reservasiService
    ) {}

    public function index()
    {
        $struk = PengaturanStruk::aktif();
        $daftarUkuran = PengaturanStruk::DAFTAR_UKURAN_KERTAS;

        return view('Karyawan.Kasir.struk', compact('struk', 'daftarUkuran'));
    }

    public function cetak(Transaksi $transaksi)
    {
        $transaksi->load(['reservasi.pelanggan', 'reservasi.layanan', 'produk', 'kasir']);
        $struk = PengaturanStruk::aktif();
        $petugasMap = $this->reservasiService->petakanNamaPetugas(collect([$transaksi->reservasi]));
        $ukuran = $struk->ukuranTernormalisasi();
        $nomorNota = str_pad((string) $transaksi->id_reservasi, 4, '0', STR_PAD_LEFT);

        return view('Karyawan.Kasir.struk-cetak', compact('transaksi', 'struk', 'petugasMap', 'ukuran', 'nomorNota'));
    }
}