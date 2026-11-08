<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use App\Models\Reservasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BerandaPublikController extends Controller
{
    private const JUMLAH_LAYANAN_UNGGULAN = 6;

    public function index(Request $request)
    {
        if (Auth::guard('karyawan')->check()) {
            return redirect()->route('dashboard');
        }
        if (Auth::guard('pelanggan')->check()) {
            return redirect()->route('pelanggan.beranda');
        }

        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Publik.beranda', [
            'layananUnggulan' => $layanan->take(self::JUMLAH_LAYANAN_UNGGULAN),
            'totalLayanan' => $layanan->count(),
            'jamBuka' => Reservasi::JAM_BUKA_SALON,
            'jamTutup' => Reservasi::JAM_TUTUP_SALON,
            'perangkatStafTerdeteksi' => $request->cookie(AuthController::COOKIE_PERANGKAT_STAF) === '1',
        ]);
    }
}
