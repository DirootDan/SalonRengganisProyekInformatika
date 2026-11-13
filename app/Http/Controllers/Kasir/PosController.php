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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $petugas = Karyawan::whereIn('role_jabatan', Layanan::JABATAN_PETUGAS)
            ->where('aktif', true)
            ->orderBy('nama_staff')
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan', 'persen_komisi']);

        $semuaPetugasMap = Karyawan::withTrashed()
            ->get(['id_karyawan', 'nama_staff', 'role_jabatan', 'aktif', 'deleted_at'])
            ->keyBy('id_karyawan');

        $reservasiAktif = Reservasi::with(['layanan', 'produkAddon', 'pelanggan'])
            ->siapBayarKasir()
            ->orderBy('tgl_booking')
            ->orderBy('jam_booking')
            ->limit(self::BATAS_ANTREAN_POS)
            ->get();

        $daftarHold = PermintaanOtorisasi::with('manajer')
            ->sedangDihold($idKasir)
            ->latest('updated_at')
            ->get();

        $petugasJson = $petugas->map(fn (Karyawan $s) => [
            'id_karyawan' => (int) $s->id_karyawan,
            'nama_staff' => $s->nama_staff,
            'role_jabatan' => $s->role_jabatan,
            'persen_komisi' => (float) $s->persen_komisi,
        ])->values();

        $itemReservasiMap = $reservasiAktif->mapWithKeys(fn (Reservasi $r) => [
            $r->id_reservasi => $this->susunItemReservasi($r, $semuaPetugasMap),
        ]);

        $holdViewMap = $daftarHold->mapWithKeys(function (PermintaanOtorisasi $h) use ($semuaPetugasMap): array {
            $snapshotAktif = $h->snapshot_keranjang ?? [];
            $snapshotAwal = $h->snapshot_awal ?? $snapshotAktif;

            return [
                $h->id_otorisasi => [
                    'items_hold' => $this->susunItemDariSnapshot($snapshotAktif, $semuaPetugasMap),
                    'items_awal' => $this->susunItemDariSnapshot($snapshotAwal, $semuaPetugasMap),
                    'kode_promo_aktif' => $snapshotAktif['kode_promo'] ?? '',
                    'kode_promo_awal' => $snapshotAwal['kode_promo'] ?? '',
                ],
            ];
        });

        $jumlahHoldDitolak = $daftarHold->where('status', PermintaanOtorisasi::STATUS_DITOLAK)->count();
        $adaHoldDitolak = $jumlahHoldDitolak > 0;
        $adaHoldDisetujui = $daftarHold->contains('status', PermintaanOtorisasi::STATUS_DISETUJUI);

        return view('Karyawan.Kasir.dashboard', [
            'layanan' => Layanan::orderBy('kategori')->orderBy('nama_layanan')->get(),
            'produk' => Produk::tersedia()->orderBy('nama_produk')->get(),
            'reservasiAktif' => $reservasiAktif,
            'itemReservasiMap' => $itemReservasiMap,
            'daftarHold' => $daftarHold,
            'holdViewMap' => $holdViewMap,
            'jumlahHoldDitolak' => $jumlahHoldDitolak,
            'adaHoldDitolak' => $adaHoldDitolak,
            'adaHoldDisetujui' => $adaHoldDisetujui,
            'kelasTombolHold' => $adaHoldDitolak ? 'ada-ditolak' : ($adaHoldDisetujui ? 'ada-disetujui' : ''),
            'batasWajibApproval' => (int) PermintaanOtorisasi::BATAS_NOMINAL_WAJIB_APPROVAL,
            'detikKunciDirect' => $this->otorisasiPosService->sisaDetikTerkunci($idKasir, (string) $request->ip()),
            'petugas' => $petugas,
            'petugasJson' => $petugasJson,
            'semuaPetugasMap' => $semuaPetugasMap,
            'metodeAktif' => MetodePembayaran::where('aktif', true)->orderBy('id_metode')->get(),
            'promoAktif' => Promo::sedangBerlaku()->orderBy('kode_promo')->get(),
            'struk' => PengaturanStruk::aktif(),
        ]);
    }

    public function ringkasanBadge(): JsonResponse
    {
        return response()->json([
            'menunggu' => Reservasi::menungguKonfirmasi()->count(),
            'antrean' => Reservasi::siapBayarKasir()->count(),
        ]);
    }

    /**
     * @param  Collection<int, Karyawan>  $semuaPetugasMap
     * @return array<int, array<string, mixed>>
     */
    private function susunItemReservasi(Reservasi $reservasi, Collection $semuaPetugasMap): array
    {
        $layananItems = $reservasi->layanan->map(function (Layanan $l) use ($semuaPetugasMap): array {
            $idStaf = $l->pivot->id_karyawan ? (int) $l->pivot->id_karyawan : null;
            $stafObj = $idStaf ? $semuaPetugasMap->get($idStaf) : null;
            $stafAktif = $stafObj && $stafObj->aktif && $stafObj->deleted_at === null;

            return [
                'id' => 'L-'.$l->id_layanan,
                'id_asli' => (int) $l->id_layanan,
                'nama' => $l->nama_layanan,
                'harga' => (int) ($l->pivot->harga_saat_ini ?: $l->harga_layanan),
                'qty' => 1,
                'tipe' => 'Layanan',
                'jabatan_petugas' => $l->jabatan_petugas,
                'id_petugas' => $idStaf,
                'nama_petugas' => $stafObj?->nama_staff,
                'petugas_aktif' => $stafAktif,
            ];
        });

        $produkItems = $reservasi->produkAddon->map(fn (Produk $p): array => [
            'id' => 'P-'.$p->id_produk,
            'id_asli' => (int) $p->id_produk,
            'nama' => $p->nama_produk,
            'harga' => (int) ($p->pivot->harga_saat_ini ?: $p->harga_produk),
            'qty' => (int) $p->pivot->jumlah,
            'tipe' => 'Produk',
        ]);

        return $layananItems->concat($produkItems)->values()->all();
    }

    /**
     * @param  array<string, mixed>  $snap
     * @param  Collection<int, Karyawan>  $semuaPetugasMap
     * @return array<int, array<string, mixed>>
     */
    private function susunItemDariSnapshot(array $snap, Collection $semuaPetugasMap): array
    {
        $hasil = [];

        foreach (($snap['layanan'] ?? []) as $l) {
            $idStaf = ! empty($l['id_petugas']) ? (int) $l['id_petugas'] : null;
            $stafObj = $idStaf ? $semuaPetugasMap->get($idStaf) : null;
            $stafAktif = $stafObj && $stafObj->aktif && $stafObj->deleted_at === null;

            $hasil[] = [
                'id' => 'L-'.$l['id_layanan'],
                'id_asli' => (int) $l['id_layanan'],
                'nama' => $l['nama'],
                'harga_normal' => (int) $l['harga_normal'],
                'harga' => (int) $l['harga_aktif'],
                'qty' => 1,
                'tipe' => 'Layanan',
                'jabatan_petugas' => $l['jabatan_petugas'] ?? 'Stylist',
                'id_petugas' => $idStaf,
                'nama_petugas' => $stafObj?->nama_staff,
                'petugas_aktif' => $stafAktif,
            ];
        }

        foreach (($snap['produk'] ?? []) as $p) {
            $hasil[] = [
                'id' => 'P-'.$p['id_produk'],
                'id_asli' => (int) $p['id_produk'],
                'nama' => $p['nama'],
                'harga_normal' => (int) $p['harga_normal'],
                'harga' => (int) $p['harga_aktif'],
                'qty' => (int) $p['qty'],
                'tipe' => 'Produk',
            ];
        }

        return $hasil;
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

        return redirect()->route('kasir.struk.cetak', $transaksi->id_transaksi)
            ->with('status', "Transaksi #INV-{$transaksi->id_transaksi} berhasil dibayar lunas! Kembalian: Rp {$kembalianRp}");
    }
}
