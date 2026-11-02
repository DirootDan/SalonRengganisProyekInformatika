<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\PengaturanStruk;

class StrukController extends Controller
{
    public function index()
    {
        $struk = PengaturanStruk::aktif();
        $daftarUkuran = PengaturanStruk::DAFTAR_UKURAN_KERTAS;

        return view('Karyawan.Kasir.struk', compact('struk', 'daftarUkuran'));
    }
}
