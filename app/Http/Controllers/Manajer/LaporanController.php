<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Services\LaporanService;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function __construct(
        private readonly LaporanService $laporanService
    ) {}

    public function index(Request $request)
    {
        [$tglMulai, $tglSelesai, $waktuMulai, $waktuSelesai] = $this->laporanService->rentangTanggal($request);

        $dataLaporan = $this->laporanService->ringkasanLaporan($tglMulai, $tglSelesai, $waktuMulai, $waktuSelesai);

        return view('Manajer.laporan', $dataLaporan);
    }

    public function eksporCsv(Request $request)
    {
        [$tglMulai, $tglSelesai] = $this->laporanService->rentangTanggal($request);

        return $this->laporanService->eksporCsv($tglMulai, $tglSelesai);
    }
}
