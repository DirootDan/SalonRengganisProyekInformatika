<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\BukaBlokirRequest;
use App\Http\Requests\Manajer\PengaturanKebijakanRequest;
use App\Models\Pelanggan;
use App\Models\PengaturanKebijakan;
use App\Models\RiwayatPelanggaran;
use App\Services\PelanggaranService;

class KebijakanBookingController extends Controller
{
    public function __construct(
        private readonly PelanggaranService $pelanggaranService
    ) {}

    public function edit()
    {
        $kebijakan = PengaturanKebijakan::aktif();

        $pelangganDiblokir = Pelanggan::diblokirAktif()
            ->orderByDesc('diblokir_permanen')
            ->orderByDesc('diblokir_sampai')
            ->get();

        $pelangganPelanggar = Pelanggan::memilikiPelanggaran()
            ->orderByDesc('diblokir_permanen')
            ->orderByDesc('jumlah_pelanggaran')
            ->get();

        $riwayatPelanggaran = RiwayatPelanggaran::with('pelanggan')
            ->latest('id_pelanggaran')
            ->limit(RiwayatPelanggaran::BATAS_LOG_TERBARU)
            ->get();

        return view('Manajer.kebijakan', compact(
            'kebijakan',
            'pelangganDiblokir',
            'pelangganPelanggar',
            'riwayatPelanggaran'
        ));
    }

    public function update(PengaturanKebijakanRequest $request)
    {
        $data = $request->validated();

        $data['rentang_hari_pelanggaran'] = $data['rentang_hari_pelanggaran'] ?? PengaturanKebijakan::DEFAULT_RENTANG_HARI;
        $data['lama_blokir_kedua_hari']   = $data['lama_blokir_kedua_hari'] ?? PengaturanKebijakan::DEFAULT_BLOKIR_KEDUA_HARI;
        $data['blokir_ketiga_permanen']   = $request->has('blokir_ketiga_permanen')
            ? $request->boolean('blokir_ketiga_permanen')
            : PengaturanKebijakan::DEFAULT_BLOKIR_KETIGA_PERMANEN;
        $data['kebijakan_aktif'] = $request->boolean('kebijakan_aktif');

        PengaturanKebijakan::aktif()->update($data);

        return back()->with('status', 'Pengaturan kebijakan booking dan pinalti bertingkat berhasil disimpan.');
    }

    public function bukaBlokir(BukaBlokirRequest $request, Pelanggan $pelanggan)
    {
        $this->pelanggaranService->bukaBlokir(
            $pelanggan,
            $request->validated('alasan_buka')
        );

        return back()->with('status', "Blokir pelanggan {$pelanggan->nama_cust} berhasil dibuka dan pelanggaran direset ke 0.");
    }
}
