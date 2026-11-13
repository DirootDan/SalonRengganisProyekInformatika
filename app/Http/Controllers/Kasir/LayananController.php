<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Manajer\DashboardController as ManajerDashboardController;
use App\Http\Requests\Manajer\LayananRequest;
use App\Models\Layanan;
use App\Models\PermintaanOtorisasi;
use App\Services\OtorisasiKatalogService;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LayananController extends Controller
{
    public function __construct(
        private readonly OtorisasiKatalogService $otorisasiKatalog
    ) {}

    public function index()
    {
        $idKasir = (int) Auth::guard('karyawan')->id();
        $layanan = Layanan::withCount('reservasiAktif')
            ->orderBy('kategori')
            ->orderBy('nama_layanan')
            ->get();
        $ringkasanAkses = $this->otorisasiKatalog->ringkasanAksesKasir($idKasir, PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN);
        $this->otorisasiKatalog->pasangTiketKeKoleksi($layanan, $ringkasanAkses['peta_akses'], 'id_layanan');

        $errorsBag = session()->get('errors');
        $tiketTambah = $ringkasanAkses['peta_akses'][OtorisasiKatalogService::kunciAkses(PermintaanOtorisasi::AKSI_TAMBAH, null)] ?? null;

        return view('Karyawan.Kasir.layanan', [
            'layanan' => $layanan,
            'konteksLayanan' => PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
            'tiketTambah' => $tiketTambah,
            'bukaUlangModalLayanan' => $errorsBag?->hasAny(['nama_layanan', 'kategori', 'jabatan_petugas', 'harga_layanan', 'durasi_menit', 'jeda_menit']) ?? false,
            'petaAkses' => $ringkasanAkses['peta_akses'],
            'penolakanTerbaru' => $ringkasanAkses['penolakan_terbaru'],
        ]);
    }

    public function store(LayananRequest $request)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();
        $atribut = $request->validated();

        try {
            $this->otorisasiKatalog->eksekusiSatuKaliPakai(
                $idKasir,
                PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
                PermintaanOtorisasi::AKSI_TAMBAH,
                null,
                function () use ($atribut): array {
                    $baru = Layanan::create($atribut);
                    $snapSesudah = $baru->only(['id_layanan', 'nama_layanan', 'kategori', 'jabatan_petugas', 'durasi_menit', 'jeda_menit', 'harga_layanan']);

                    return [
                        'id_target' => (int) $baru->id_layanan,
                        'label_target' => "Tambah Layanan: {$baru->nama_layanan} (#{$baru->id_layanan})",
                        'nilai_awal' => 0.0,
                        'nilai_akhir' => (float) $baru->harga_layanan,
                        'data_sebelum' => null,
                        'data_sesudah' => $snapSesudah,
                    ];
                }
            );
        } catch (DomainException $e) {
            return back()->withErrors(['layanan' => $e->getMessage()])->withInput();
        }

        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Layanan baru berhasil ditambahkan. Hak akses 1x pakai otomatis terkunci kembali.');
    }

    public function update(LayananRequest $request, Layanan $layanan)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();
        $atribut = $request->validated();

        try {
            $this->otorisasiKatalog->eksekusiSatuKaliPakai(
                $idKasir,
                PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
                PermintaanOtorisasi::AKSI_UBAH,
                (int) $layanan->id_layanan,
                function () use ($layanan, $atribut): array {
                    $snapSebelum = $layanan->only(['id_layanan', 'nama_layanan', 'kategori', 'jabatan_petugas', 'durasi_menit', 'jeda_menit', 'harga_layanan']);
                    $hargaAwal = (float) $layanan->harga_layanan;

                    $layanan->update($atribut);
                    $snapSesudah = $layanan->fresh()->only(['id_layanan', 'nama_layanan', 'kategori', 'jabatan_petugas', 'durasi_menit', 'jeda_menit', 'harga_layanan']);

                    return [
                        'id_target' => (int) $layanan->id_layanan,
                        'label_target' => "Ubah Layanan: {$layanan->nama_layanan} (#{$layanan->id_layanan})",
                        'nilai_awal' => $hargaAwal,
                        'nilai_akhir' => (float) $layanan->harga_layanan,
                        'data_sebelum' => $snapSebelum,
                        'data_sesudah' => $snapSesudah,
                    ];
                }
            );
        } catch (DomainException $e) {
            return back()->withErrors(['layanan' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Data layanan berhasil diperbarui. Hak akses 1x pakai otomatis terkunci kembali.');
    }

    public function destroy(Layanan $layanan)
    {
        $idKasir = (int) Auth::guard('karyawan')->id();

        try {
            $this->otorisasiKatalog->eksekusiSatuKaliPakai(
                $idKasir,
                PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN,
                PermintaanOtorisasi::AKSI_HAPUS,
                (int) $layanan->id_layanan,
                function () use ($layanan): array {
                    $reservasiAktif = $layanan->jumlahReservasiAktif();
                    if ($reservasiAktif > 0) {
                        throw new DomainException(
                            "Layanan '{$layanan->nama_layanan}' tidak dapat dihapus karena masih terikat pada {$reservasiAktif} reservasi aktif."
                        );
                    }

                    $snapSebelum = $layanan->only(['id_layanan', 'nama_layanan', 'kategori', 'jabatan_petugas', 'durasi_menit', 'jeda_menit', 'harga_layanan']);
                    $hargaAwal = (float) $layanan->harga_layanan;
                    $label = "Hapus Layanan: {$layanan->nama_layanan} (#{$layanan->id_layanan})";
                    $idAsli = (int) $layanan->id_layanan;

                    $layanan->delete();

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
            return back()->withErrors(['layanan' => $e->getMessage()]);
        }

        Cache::forget(ManajerDashboardController::CACHE_KEY_RINGKASAN);

        return back()->with('status', 'Layanan berhasil dihapus. Hak akses 1x pakai otomatis terkunci kembali.');
    }
}
