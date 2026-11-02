<?php

namespace App\Http\Controllers\Manajer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manajer\KeputusanOtorisasiRequest;
use App\Models\LogPerubahanOtorisasi;
use App\Models\PermintaanOtorisasi;
use App\Services\OtorisasiPosService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PersetujuanOtorisasiController extends Controller
{
    private const BATAS_RIWAYAT_KEPUTUSAN = 30;

    public function __construct(
        private readonly OtorisasiPosService $otorisasiPosService
    ) {}

    public function index(Request $request)
    {
        $tanggalFilter = $request->query('tanggal', '');

        $daftarPending = PermintaanOtorisasi::with(['kasir', 'reservasi.pelanggan'])
            ->menungguPersetujuan()
            ->latest('id_otorisasi')
            ->get();

        $riwayatKeputusan = PermintaanOtorisasi::with(['kasir', 'manajer'])
            ->where('status', '!=', PermintaanOtorisasi::STATUS_MENUNGGU)
            ->latest('updated_at')
            ->limit(self::BATAS_RIWAYAT_KEPUTUSAN)
            ->get();

        $kueriLog = LogPerubahanOtorisasi::with(['kasir', 'manajer', 'transaksi'])
            ->latest('id_log');

        if ($tanggalFilter !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalFilter)) {
            $kueriLog->whereDate('created_at', $tanggalFilter);
        }

        $logPerubahan = $kueriLog->limit(LogPerubahanOtorisasi::BATAS_LOG_TERBARU)->get();

        return view('Manajer.persetujuan', compact(
            'daftarPending',
            'riwayatKeputusan',
            'logPerubahan',
            'tanggalFilter'
        ));
    }

    public function setujui(KeputusanOtorisasiRequest $request, int $idOtorisasi)
    {
        try {
            $idManajer = (int) Auth::guard('karyawan')->id();
            $tiket = $this->otorisasiPosService->setujuiOlehManajer(
                $idOtorisasi,
                $idManajer,
                $request->validated('catatan_manajer')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['persetujuan' => $e->getMessage()]);
        }

        return back()->with('status', "Pengajuan #{$tiket->kode_referensi} ({$tiket->label_target}) telah disetujui.");
    }

    public function tolak(KeputusanOtorisasiRequest $request, int $idOtorisasi)
    {
        try {
            $idManajer = (int) Auth::guard('karyawan')->id();
            $tiket = $this->otorisasiPosService->tolakOlehManajer(
                $idOtorisasi,
                $idManajer,
                (string) $request->validated('catatan_manajer')
            );
        } catch (DomainException $e) {
            return back()->withErrors(['persetujuan' => $e->getMessage()]);
        }

        return back()->with('status', "Pengajuan #{$tiket->kode_referensi} ditolak dan nilai keranjang Hold telah dikembalikan ke harga normal.");
    }
}
