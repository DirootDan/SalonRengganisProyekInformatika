<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manajer\DashboardController as ManajerDashboardController;
use App\Http\Requests\Manajer\ProdukRequest;
use App\Models\PermintaanOtorisasi;
use App\Models\Produk;
use App\Services\OtorisasiKatalogService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ProdukController extends Controller
{
    public function __construct(
        private readonly OtorisasiKatalogService $otorisasiKatalog
    ) {}

    public function index()
    {
        $idKasir = (int) Auth::guard('karyawan')->id();
        $produk = Produk::withCount('reservasiAktif')->orderBy('nama_produk')->get();
        $ringkasanAkses = $this->otorisasiKatalog->ringkasanAksesKasir($idKasir, PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK);
        $this->otorisasiKatalog->pasangTiketKeKoleksi($produk, $ringkasanAkses['peta_akses'], 'id_produk');

        $errorsBag = session()->get('errors');
        $tiketTambah = $ringkasanAkses['peta_akses'][OtorisasiKatalogService::kunciAkses(PermintaanOtorisasi::AKSI_TAMBAH, null)] ?? null;

        return view('Karyawan.Kasir.produk', [
            'produk' => $produk,
            'konteksProduk' => PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
            'tiketTambah' => $tiketTambah,
            'bukaUlangModalProduk' => $errorsBag?->hasAny(['nama_produk', 'stok_produk', 'harga_produk']) ?? false,
            'petaAkses' => $ringkasanAkses['peta_akses'],
            'penolakanTerbaru' => $ringkasanAkses['penolakan_terbaru'],
        ]);
    }

    public function store(ProdukRequest $request)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();
        $atribut = $request->validated();

        try {
            $this->otorisasiKatalog->eksekusiSatuKaliPakai(
                $idKasir,
                PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                PermintaanOtorisasi::AKSI_TAMBAH,
                null,
                function () use ($atribut): array {
                    $baru = Produk::create($atribut);
                    $snapSesudah = $baru->only(['id_produk', 'nama_produk', 'stok_produk', 'harga_produk']);

                    return [
                        'id_target' => (int) $baru->id_produk,
                        'label_target' => "Tambah Produk: {$baru->nama_produk} (#{$baru->id_produk})",
                        'nilai_awal' => 0.0,
                        'nilai_akhir' => (float) $baru->harga_produk,
                        'data_sebelum' => null,
                        'data_sesudah' => $snapSesudah,
                    ];
                }
            );
        } catch (DomainException $e) {
            return back()->withErrors(['produk' => $e->getMessage()])->withInput();
        }

        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Produk baru berhasil ditambahkan. Hak akses 1x pakai otomatis terkunci kembali.');
    }

    public function update(ProdukRequest $request, Produk $produk)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();
        $atribut = $request->validated();

        try {
            $this->otorisasiKatalog->eksekusiSatuKaliPakai(
                $idKasir,
                PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                PermintaanOtorisasi::AKSI_UBAH,
                (int) $produk->id_produk,
                function () use ($produk, $atribut): array {
                    $snapSebelum = $produk->only(['id_produk', 'nama_produk', 'stok_produk', 'harga_produk']);
                    $hargaAwal = (float) $produk->harga_produk;

                    $produk->update($atribut);
                    $snapSesudah = $produk->fresh()->only(['id_produk', 'nama_produk', 'stok_produk', 'harga_produk']);

                    return [
                        'id_target' => (int) $produk->id_produk,
                        'label_target' => "Ubah Produk: {$produk->nama_produk} (#{$produk->id_produk})",
                        'nilai_awal' => $hargaAwal,
                        'nilai_akhir' => (float) $produk->harga_produk,
                        'data_sebelum' => $snapSebelum,
                        'data_sesudah' => $snapSesudah,
                    ];
                }
            );
        } catch (DomainException $e) {
            return back()->withErrors(['produk' => $e->getMessage()])->withInput();
        }

        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Stok dan harga produk berhasil diperbarui. Hak akses 1x pakai otomatis terkunci kembali.');
    }

    public function destroy(Produk $produk)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();

        try {
            $this->otorisasiKatalog->eksekusiSatuKaliPakai(
                $idKasir,
                PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK,
                PermintaanOtorisasi::AKSI_HAPUS,
                (int) $produk->id_produk,
                function () use ($produk): array {
                    $reservasiAktif = $produk->jumlahReservasiAktif();
                    if ($reservasiAktif > 0) {
                        throw new DomainException(
                            "Produk '{$produk->nama_produk}' tidak dapat dihapus karena masih dipesan pada {$reservasiAktif} reservasi aktif."
                        );
                    }

                    $snapSebelum = $produk->only(['id_produk', 'nama_produk', 'stok_produk', 'harga_produk']);
                    $hargaAwal = (float) $produk->harga_produk;
                    $label = "Hapus Produk: {$produk->nama_produk} (#{$produk->id_produk})";
                    $idAsli = (int) $produk->id_produk;

                    $produk->delete();

                    return [
                        'id_target' => $idAsli,
                        'label_target' => $label,
                        'nilai_awal' => $hargaAwal,
                        'nilai_akhir' => 0.0,
                        'data_sebelum' => $snapSebelum,
                        'data_sesudah' => null,
                    ];
                }
            );
        } catch (DomainException $e) {
            return back()->withErrors(['produk' => $e->getMessage()]);
        }

        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Produk berhasil dihapus. Hak akses 1x pakai otomatis terkunci kembali.');
    }
}
