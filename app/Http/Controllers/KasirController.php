<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Manajer\DashboardController as ManajerDashboardController;
use App\Http\Requests\Kasir\ProsesBayarRequest;
use App\Http\Requests\Kasir\TolakBookingRequest;
use App\Http\Requests\Manajer\LayananRequest;
use App\Http\Requests\Manajer\ProdukRequest;
use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\PengaturanKebijakan;
use App\Models\PengaturanStruk;
use App\Models\Produk;
use App\Models\Promo;
use App\Models\Reservasi;
use App\Services\PembayaranKasirService;
use App\Services\ReservasiService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class KasirController extends Controller
{
    private const BATAS_ANTREAN_POS = 25;

    private const ITEM_PER_HALAMAN_BOOKING = 8;

    private const ITEM_PER_HALAMAN_LAPORAN = 25;

    public function __construct(
        private readonly ReservasiService $reservasiService,
        private readonly PembayaranKasirService $pembayaranService
    ) {}

    public function dashboard()
    {
        return view('Karyawan.Kasir.dashboard', [
            'layanan' => Layanan::orderBy('kategori')->orderBy('nama_layanan')->get(),
            'produk' => Produk::tersedia()->orderBy('nama_produk')->get(),
            'reservasiAktif' => Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
                ->siapBayarKasir()
                ->orderBy('tgl_booking')
                ->orderBy('jam_booking')
                ->limit(self::BATAS_ANTREAN_POS)
                ->get(),
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

    public function permintaanBooking(Request $request)
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

        $daftarBooking = $query->paginate(self::ITEM_PER_HALAMAN_BOOKING)->withQueryString();
        $petugasMap = $this->petakanNamaPetugas($daftarBooking->getCollection());
        $kebijakan = PengaturanKebijakan::aktif();

        return view('Karyawan.Kasir.permintaan-booking', compact(
            'daftarBooking',
            'statusTab',
            'kebijakan',
            'petugasMap'
        ));
    }

    public function terimaBooking(int $idReservasi)
    {
        try {
            $this->reservasiService->terimaReservasiKasir($idReservasi);
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Reservasi #{$idReservasi} berhasil diterima dan notifikasi telah dikirim ke pelanggan.");
    }

    public function tolakBooking(TolakBookingRequest $request, int $idReservasi)
    {
        try {
            $alasan = (string) $request->validated('alasan_penolakan');
            $this->reservasiService->tolakReservasiKasir($idReservasi, $alasan);
        } catch (DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', "Reservasi #{$idReservasi} telah ditolak beserta alasan kepada pelanggan.");
    }

    public function antreanHariIni()
    {
        $antrean = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->where('status', Reservasi::STATUS_DITERIMA)
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->get();

        $petugasMap = $this->petakanNamaPetugas($antrean);

        return view('Karyawan.Kasir.antrean', compact('antrean', 'petugasMap'));
    }

    public function prosesBayar(ProsesBayarRequest $request)
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

    public function layanan()
    {
        $layanan = Layanan::orderBy('kategori')->orderBy('nama_layanan')->get();

        return view('Karyawan.Kasir.layanan', compact('layanan'));
    }

    public function storeLayanan(LayananRequest $request)
    {
        Layanan::create($request->validated());
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Layanan baru berhasil ditambahkan.');
    }

    public function updateLayanan(LayananRequest $request, Layanan $layanan)
    {
        $layanan->update($request->validated());

        return back()->with('status', 'Data layanan berhasil diperbarui.');
    }

    public function destroyLayanan(Layanan $layanan)
    {
        $reservasiAktif = $layanan->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'layanan' => "Layanan '{$layanan->nama_layanan}' tidak dapat dihapus karena masih terikat pada {$reservasiAktif} reservasi aktif.",
            ]);
        }

        $layanan->delete();
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Layanan berhasil dihapus.');
    }

    public function produk()
    {
        $produk = Produk::orderBy('nama_produk')->get();

        return view('Karyawan.Kasir.produk', compact('produk'));
    }

    public function storeProduk(ProdukRequest $request)
    {
        Produk::create($request->validated());
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Produk baru berhasil ditambahkan.');
    }

    public function updateProduk(ProdukRequest $request, Produk $produk)
    {
        $produk->update($request->validated());
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Stok dan harga produk berhasil diperbarui.');
    }

    public function destroyProduk(Produk $produk)
    {
        $reservasiAktif = $produk->jumlahReservasiAktif();

        if ($reservasiAktif > 0) {
            return back()->withErrors([
                'produk' => "Produk '{$produk->nama_produk}' tidak dapat dihapus karena masih dipesan pada {$reservasiAktif} reservasi aktif.",
            ]);
        }

        $produk->delete();
        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    public function laporan()
    {
        $reservasi = Reservasi::with(['layanan', 'produkAddon', 'pelanggan', 'transaksi'])
            ->latest('tgl_booking')
            ->latest('id_reservasi')
            ->paginate(self::ITEM_PER_HALAMAN_LAPORAN);

        return view('Karyawan.Kasir.laporan', compact('reservasi'));
    }

    public function metodePembayaran()
    {
        $metode = MetodePembayaran::orderBy('id_metode')->get();

        return view('Karyawan.Kasir.metode-pembayaran', compact('metode'));
    }

    public function struk()
    {
        $struk = PengaturanStruk::aktif();
        $daftarUkuran = PengaturanStruk::DAFTAR_UKURAN_KERTAS;

        return view('Karyawan.Kasir.struk', compact('struk', 'daftarUkuran'));
    }

    /**
     * Memetakan ID karyawan menjadi nama staf untuk sekumpulan reservasi tanpa duplikasi logika.
     *
     * @param  Collection<int, Reservasi>  $daftarReservasi
     * @return Collection<int, string>
     */
    private function petakanNamaPetugas(Collection $daftarReservasi): Collection
    {
        $idPetugas = $daftarReservasi
            ->flatMap(fn (Reservasi $r) => $r->layanan->pluck('pivot.id_karyawan'))
            ->filter()
            ->unique();

        if ($idPetugas->isEmpty()) {
            return collect();
        }

        return Karyawan::withTrashed()
            ->whereIn('id_karyawan', $idPetugas)
            ->pluck('nama_staff', 'id_karyawan');
    }
}
