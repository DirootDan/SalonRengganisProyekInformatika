<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Reservasi;
use App\Services\ReservasiService;

class AntreanController extends Controller
{
    public function __construct(
        private readonly ReservasiService $reservasiService
    ) {}

    public function index()
    {
        $antrean = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->where('status', Reservasi::STATUS_DITERIMA)
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->get();

        $petugasMap = $this->reservasiService->petakanNamaPetugas($antrean);

        return view('Karyawan.Kasir.antrean', compact('antrean', 'petugasMap'));
    }
}
