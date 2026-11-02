<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kasir\TolakBookingRequest;
use App\Models\PengaturanKebijakan;
use App\Models\Reservasi;
use App\Services\ReservasiService;
use DomainException;
use Illuminate\Http\Request;

class PermintaanBookingController extends Controller
{
    private const ITEM_PER_HALAMAN = 8;

    public function __construct(
        private readonly ReservasiService $reservasiService
    ) {}

    public function index(Request $request)
    {
        $statusTab = (string) $request->query('status', Reservasi::STATUS_MENUNGGU);
        $query = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->latest('tgl_booking')
            ->latest('id_reservasi');

        if ($statusTab === Reservasi::STATUS_MENUNGGU) {
            $query->menungguKonfirmasi();
        } elseif ($statusTab !== 'Semua') {
            $query->where('status', $statusTab);
        }

        $daftarBooking = $query->paginate(self::ITEM_PER_HALAMAN)->withQueryString();
        $petugasMap = $this->reservasiService->petakanNamaPetugas($daftarBooking->getCollection());
        $kebijakan = PengaturanKebijakan::aktif();

        return view('Karyawan.Kasir.permintaan-booking', compact(
            'daftarBooking',
            'statusTab',
            'kebijakan',
            'petugasMap'
        ));
    }

    public function terima(int $idReservasi)
    {
        try {
            $this->reservasiService->terimaReservasiKasir($idReservasi);
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Reservasi #{$idReservasi} berhasil diterima dan notifikasi telah dikirim ke pelanggan.");
    }

    public function tolak(TolakBookingRequest $request, int $idReservasi)
    {
        try {
            $alasan = (string) $request->validated('alasan_penolakan');
            $this->reservasiService->tolakReservasiKasir($idReservasi, $alasan);
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Reservasi #{$idReservasi} telah ditolak beserta alasan kepada pelanggan.");
    }
}
