<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\MetodePembayaran;

class MetodePembayaranController extends Controller
{
    public function index()
    {
        $metode = MetodePembayaran::orderBy('id_metode')->get();

        return view('Karyawan.Kasir.metode-pembayaran', compact('metode'));
    }
}
