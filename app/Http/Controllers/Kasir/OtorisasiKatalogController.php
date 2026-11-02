<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kasir\AjukanAksesKatalogRequest;
use App\Services\OtorisasiKatalogService;
use DomainException;
use Illuminate\Support\Facades\Auth;

class OtorisasiKatalogController extends Controller
{
    public function __construct(
        private readonly OtorisasiKatalogService $otorisasiKatalogService
    ) {}

    public function store(AjukanAksesKatalogRequest $request)
    {
        try {
            $idKasir = (int) Auth::guard('karyawan')->id();
            $tiket = $this->otorisasiKatalogService->ajukanAkses($idKasir, $request->validated());
        } catch (DomainException $e) {
            return back()->withErrors(['akses_katalog' => $e->getMessage()])->withInput();
        }

        return back()->with(
            'status',
            "Permintaan akses 1x pakai #{$tiket->kode_referensi} ({$tiket->label_target}) berhasil dikirim ke Manajer."
        );
    }

    public function destroy(int $idOtorisasi)
    {
        try {
            $idKasir = (int) Auth::guard('karyawan')->id();
            $tiket = $this->otorisasiKatalogService->batalkanAtauKunciAkses($idOtorisasi, $idKasir);
        } catch (DomainException $e) {
            return back()->withErrors(['akses_katalog' => $e->getMessage()]);
        }

        $pesan = $tiket->diputuskan_pada !== null
            ? "Hak akses 1x pakai #{$tiket->kode_referensi} ({$tiket->label_target}) dibatalkan dan otomatis terkunci kembali."
            : "Pengajuan akses #{$tiket->kode_referensi} berhasil ditarik dari antrean Manajer.";

        return back()->with('status', $pesan);
    }
}
