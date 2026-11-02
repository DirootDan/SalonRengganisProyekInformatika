<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manajer\DashboardController as ManajerDashboardController;
use App\Http\Requests\Kasir\ProsesBayarRequest;
use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\PengaturanStruk;
use App\Models\PermintaanOtorisasi;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use App\Services\OtorisasiPosService;
use App\Services\PembayaranKasirService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PosController extends Controller
{
    private const BATAS_ANTREAN_POS = 25;

    public function __construct(
        private readonly PembayaranKasirService $pembayaranService,
        private readonly OtorisasiPosService $otorisasiPosService
    ) {}

    public function index(Request $request)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();

        return view('Karyawan.Kasir.dashboard', [
            'layanan' => Layanan::orderBy('kategori')->orderBy('nama_layanan')->get(),
            'produk' => Produk::tersedia()->orderBy('nama_produk')->get(),
            'reservasiAktif' => Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
                ->siapBayarKasir()
                ->orderBy('tgl_booking')
                ->orderBy('jam_booking')
                ->limit(self::BATAS_ANTREAN_POS)
                ->get(),
            'daftarHold' => PermintaanOtorisasi::with('manajer')
                ->sedangDihold($idKasir)
                ->latest('updated_at')
                ->get(),
            'batasWajibApproval' => (int) PermintaanOtorisasi::BATAS_NOMINAL_WAJIB_APPROVAL,
            'detikKunciDirect' => $this->otorisasiPosService->sisaDetikTerkunci($idKasir, (string) $request->ip()),
            'petugas' => Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
                ->where('aktif', true)
                ->orderBy('nama_staff')
                ->get(['id_karyawan', 'nama_staff', 'role_jabatan', 'persen_komisi']),
            'semuaPetugasMap' => Karyawan::withTrashed()
                ->get(['id_karyawan', 'nama_staff', 'role_jabatan', 'aktif', 'deleted_at'])
                ->keyBy('id_karyawan'),
            'metodeAktif' => MetodePembayaran::where('aktif', true)->orderBy('id_metode')->get(),
            'promoAktif' => Promo::sedangBerlaku()->orderBy('kode_promo')->get(),
            'struk' => PengaturanStruk::aktif(),
        ]);
    }

    public function store(ProsesBayarRequest $request)
    {
        try {
            $idKasir = (int) Auth::guard('karyawan')->id();
            $transaksi = $this->pembayaranService->prosesBayar($idKasir, $request->validated());
        } catch (DomainException $e) {
            return back()->withErrors(['pembayaran' => $e->getMessage()])->withInput();
        }

        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);
        $kembalianRp = number_format((float) $transaksi->kembalian, 0, ',', '.');

        return redirect()->route('kasir.dashboard')
            ->with('status', "Transaksi #INV-{$transaksi->id_transaksi} berhasil dibayar lunas! Kembalian: Rp {$kembalianRp}");
    }
}
