<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;

class LaporanController extends Controller
{
    private const ITEM_PER_HALAMAN = 25;

    public function index()
    {
        $reservasi = Reservasi::with(['layanan', 'produkAddon', 'pelanggan', 'transaksi'])
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->paginate(self::ITEM_PER_HALAMAN);

        return view('Karyawan.Kasir.laporan', compact('reservasi'));
    }
}
