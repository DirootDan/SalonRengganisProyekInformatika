<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Reservasi;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public const BATAS_RESERVASI_TERBARU = 6;
    public const CACHE_KEY_RINGKASAN     = 'manajer.dashboard.ringkasan';
    public const CACHE_TTL_DETIK         = 60;

    public function index()
    {
        $ringkasan = Cache::remember(self::CACHE_KEY_RINGKASAN, self::CACHE_TTL_DETIK, function () {
            return [
                'totalKaryawan'  => Karyawan::count(),
                'totalLayanan'   => Layanan::count(),
                'totalProduk'    => Produk::count(),
                'totalPelanggan' => Pelanggan::count(),
                'totalReservasi' => Reservasi::count(),
            ];
        });

        $reservasiBaru = Reservasi::with(['layanan', 'pelanggan'])
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->limit(self::BATAS_RESERVASI_TERBARU)
            ->get();

        // Tetap sediakan $pelangganMap dari relasi eager-loaded tanpa kueri tambahan
        $pelangganMap = $reservasiBaru
            ->pluck('pelanggan')
            ->filter()
            ->pluck('nama_cust', 'id_pelanggan');

        return view('Manajer.dashboard', array_merge($ringkasan, compact(
            'reservasiBaru',
            'pelangganMap'
        )));
    }
}
