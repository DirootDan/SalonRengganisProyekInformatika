<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kasir\DirectOverridePosRequest;
use App\Http\Requests\Kasir\HoldOtorisasiPosRequest;
use App\Services\OtorisasiPosService;
use DomainException;
use Illuminate\Support\Facades\Auth;

class OtorisasiPosController extends Controller
{
    public function __construct(
        private readonly OtorisasiPosService $otorisasiPosService
    ) {}

    public function hold(HoldOtorisasiPosRequest $request)
    {
        try {
            $idKasir = (int) Auth::guard('karyawan')->id();
            $tiket = $this->otorisasiPosService->ajukanDanHoldPos($idKasir, $request->validated());
        } catch (DomainException $e) {
            return back()->withErrors(['otorisasi' => $e->getMessage()])->withInput();
        }

        return redirect()->route('kasir.dashboard')
            ->with('status', "Permintaan #{$tiket->kode_referensi} dikirim ke Manajer dan transaksi disimpan ke daftar Hold.");
    }

    public function directOverride(DirectOverridePosRequest $request)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();
        $ip = (string) $request->ip();

        try {
            $tiket = $this->otorisasiPosService->verifikasiDirectOverridePos($idKasir, $ip, $request->validated());
        } catch (DomainException $e) {
            $detikKunci = $this->otorisasiPosService->sisaDetikTerkunci($idKasir, $ip);
            if ($request->expectsJson()) {
                return response()->json([
                    'berhasil' => false,
                    'message' => $e->getMessage(),
                    'detik_terkunci' => $detikKunci,
                ], $detikKunci > 0 ? 429 : 422);
            }

            return back()->withErrors(['direct_override' => $e->getMessage()])->withInput();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'berhasil' => true,
                'id_otorisasi' => $tiket->id_otorisasi,
                'kode_referensi' => $tiket->kode_referensi,
                'hash_keranjang' => $tiket->hash_keranjang,
                'nama_manajer' => $tiket->manajer?->nama_staff ?? 'Manajer',
                'message' => "Otorisasi langsung berhasil disahkan oleh Manajer (#{$tiket->kode_referensi}). Silakan selesaikan pembayaran.",
            ]);
        }

        return back()->with('status', "Otorisasi langsung #{$tiket->kode_referensi} berhasil disetujui Manajer.");
    }

    public function batalkanHold(int $idOtorisasi)
    {
        try {
            $idKasir = (int) Auth::guard('karyawan')->id();
            $tiket = $this->otorisasiPosService->batalkanHoldOlehKasir($idOtorisasi, $idKasir);
        } catch (DomainException $e) {
            return back()->withErrors(['otorisasi' => $e->getMessage()]);
        }

        return redirect()->route('kasir.dashboard')
            ->with('status', "Transaksi Hold #{$tiket->kode_referensi} berhasil dibatalkan dan ditarik dari antrean Manajer.");
    }
}
