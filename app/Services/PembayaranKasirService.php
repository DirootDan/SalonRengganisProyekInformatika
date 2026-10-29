<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Reservasi;
use App\Models\Transaksi;
use DomainException;
use Illuminate\Support\Facades\DB;

class PembayaranKasirService
{
    public function __construct(
        private readonly JadwalPetugasService $jadwalService,
        private readonly KalkulatorHargaService $kalkulatorHarga
    ) {}

    /**
     * Memproses pembayaran reservasi maupun transaksi langsung (walk-in) di meja kasir.
     *
     * @param  array<string, mixed>  $data
     */
    public function prosesBayar(int $idKasir, array $data): Transaksi
    {
        return DB::transaction(function () use ($idKasir, $data) {
            $this->validasiMetodePembayaran((string) $data['metode_bayar']);

            $reservasi = ! empty($data['id_reservasi'])
                ? $this->ambilDanValidasiReservasi((int) $data['id_reservasi'])
                : $this->buatReservasiWalkIn($idKasir, $data);

            $petugasInput = (array) ($data['petugas_layanan'] ?? []);
            $idPetugasFallback = ! empty($data['id_petugas']) ? (int) $data['id_petugas'] : null;

            // 1. Validasi petugas final per layanan & hitung ulang komisi berdasarkan petugas final
            [$subtotalLayanan, $totalKomisi] = $this->tetapkanPetugasFinalDanHitungKomisi(
                $reservasi,
                $petugasInput,
                $idPetugasFallback
            );

            // 2. Hitung subtotal produk & sesuaikan selisih stok terhadap produk add-on reservasi
            $produkItems = (array) ($data['produk_items'] ?? []);
            [$subtotalProduk, $lampiranProduk] = $this->rekonsiliasiStokDanHitungProduk($reservasi, $produkItems);

            // 3. Hitung diskon promo (pakai snapshot promo reservasi jika kode sama, atau hitung ulang jika diubah kasir)
            $subtotal = $subtotalLayanan + $subtotalProduk;
            [$kodePromoFinal, $diskonFinal] = $this->hitungDiskonTransaksi(
                $reservasi,
                $data['kode_promo'] ?? null,
                $subtotal
            );

            $totalBayar = $this->kalkulatorHarga->hitungTotalAkhir($subtotalLayanan, $subtotalProduk, $diskonFinal);
            $nominalUang = (float) $data['nominal_uang'];

            if ($nominalUang < $totalBayar) {
                throw new DomainException(
                    'Nominal uang yang diterima kurang dari total tagihan (Rp '.number_format($totalBayar, 0, ',', '.').').'
                );
            }

            $kembalian = round($nominalUang - $totalBayar, 2);

            if ($reservasi->statusTernormalisasi() === Reservasi::STATUS_MENUNGGU) {
                $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);
            }
            $reservasi->ubahStatus(Reservasi::STATUS_SELESAI);

            $transaksi = Transaksi::create([
                'id_reservasi' => $reservasi->id_reservasi,
                'id_karyawan' => $idKasir,
                'kode_promo' => $kodePromoFinal,
                'diskon' => $diskonFinal,
                'total_bayar' => $totalBayar,
                'nominal_uang' => $nominalUang,
                'kembalian' => $kembalian,
                'metode_bayar' => $data['metode_bayar'],
                'komisi_stylist' => $totalKomisi,
            ]);

            if (! empty($lampiranProduk)) {
                $transaksi->produk()->attach($lampiranProduk);
            }

            return $transaksi;
        });
    }

    private function validasiMetodePembayaran(string $namaMetode): void
    {
        if (! MetodePembayaran::where('aktif', true)->exists()) {
            return;
        }

        $valid = MetodePembayaran::where('nama_metode', $namaMetode)
            ->where('aktif', true)
            ->exists();

        if (! $valid) {
            throw new DomainException("Metode pembayaran '{$namaMetode}' sedang tidak aktif atau tidak terdaftar.");
        }
    }

    private function ambilDanValidasiReservasi(int $idReservasi): Reservasi
    {
        $reservasi = Reservasi::with(['layanan', 'produkAddon'])
            ->where('id_reservasi', $idReservasi)
            ->lockForUpdate()
            ->firstOrFail();

        if ($reservasi->transaksi()->exists()) {
            throw new DomainException("Reservasi #{$reservasi->id_reservasi} sudah pernah dibayar lunas sebelumnya.");
        }

        $status = $reservasi->statusTernormalisasi();
        if (in_array($status, [Reservasi::STATUS_HANGUS, Reservasi::STATUS_DIBATALKAN, Reservasi::STATUS_DITOLAK], true)) {
            throw new DomainException(
                "Reservasi #{$reservasi->id_reservasi} berstatus '{$status}' dan tidak dapat diproses pembayarannya."
            );
        }

        return $reservasi;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buatReservasiWalkIn(int $idKasir, array $data): Reservasi
    {
        $layananIds = array_values(array_unique(array_map('intval', (array) ($data['layanan_ids'] ?? []))));
        $produkQtyTotal = collect((array) ($data['produk_items'] ?? []))->map(fn ($v) => (int) $v)->sum();

        if (empty($layananIds) && $produkQtyTotal <= 0) {
            throw new DomainException('Pilih minimal satu layanan atau satu produk untuk memproses transaksi langsung (walk-in).');
        }

        $pelangganDefault = Pelanggan::first();
        if (! $pelangganDefault) {
            throw new DomainException('Data pelanggan belum tersedia untuk mencatat transaksi.');
        }

        $idPetugasWalkIn = ! empty($data['id_petugas']) ? (int) $data['id_petugas'] : null;
        $stafWalkIn = $idPetugasWalkIn ? Karyawan::where('aktif', true)->find($idPetugasWalkIn) : null;
        $persenWalkIn = (float) ($stafWalkIn->persen_komisi ?? 15);

        $jamSekarang = now()->format('H:i');
        $reservasi = Reservasi::create([
            'id_pelanggan' => $pelangganDefault->id_pelanggan,
            'id_karyawan' => $stafWalkIn?->id_karyawan ?? $idKasir,
            'tgl_booking' => now()->toDateString(),
            'jam_booking' => $jamSekarang,
            'jam_selesai' => $jamSekarang,
            'total_durasi_menit' => 60,
            'status' => Reservasi::STATUS_DITERIMA,
        ]);

        $lampiran = [];
        foreach (Layanan::whereIn('id_layanan', $layananIds)->get() as $l) {
            $harga = (float) $l->harga_layanan;
            $lampiran[$l->id_layanan] = [
                'id_karyawan' => $stafWalkIn?->id_karyawan,
                'jam_mulai' => $jamSekarang,
                'jam_selesai' => $jamSekarang,
                'jam_selesai_blokir' => $jamSekarang,
                'durasi_saat_ini' => (int) ($l->durasi_menit ?: 60),
                'jeda_saat_ini' => (int) ($l->jeda_menit ?? 15),
                'harga_saat_ini' => $harga,
                'persen_komisi' => $persenWalkIn,
                'nominal_komisi' => round($harga * ($persenWalkIn / 100), 2),
                'status_layanan' => 'Selesai',
            ];
        }

        if (! empty($lampiran)) {
            $reservasi->layanan()->attach($lampiran);
        }

        $reservasi->load(['layanan', 'produkAddon']);

        return $reservasi;
    }

    /**
     * Memvalidasi petugas final per layanan (aktif, sesuai jabatan, tidak bentrok bila diganti)
     * dan menghitung komisi berdasarkan petugas final.
     *
     * @param  array<int|string, mixed>  $petugasInput
     * @return array{0: float, 1: float}
     */
    private function tetapkanPetugasFinalDanHitungKomisi(
        Reservasi $reservasi,
        array $petugasInput,
        ?int $idPetugasFallback
    ): array {
        $subtotalLayanan = 0.0;
        $totalKomisi = 0.0;
        $idPetugasUtama = null;

        foreach ($reservasi->layanan as $layanan) {
            $idAwal = $layanan->pivot->id_karyawan ? (int) $layanan->pivot->id_karyawan : null;
            $idPilihanKasir = ! empty($petugasInput[$layanan->id_layanan])
                ? (int) $petugasInput[$layanan->id_layanan]
                : ($idAwal ?? $idPetugasFallback);

            $petugasFinal = $this->validasiPetugasLayananFinal(
                $reservasi,
                $layanan,
                $idAwal,
                $idPilihanKasir
            );

            $hargaSnap = (float) ($layanan->pivot->harga_saat_ini > 0 ? $layanan->pivot->harga_saat_ini : $layanan->harga_layanan);

            if ($petugasFinal) {
                $persenFinal = (float) $petugasFinal->persen_komisi;
                $komisiFinal = round($hargaSnap * ($persenFinal / 100), 2);
                $idKaryawanSimpan = (int) $petugasFinal->id_karyawan;
                $idPetugasUtama ??= $idKaryawanSimpan;
            } else {
                $persenFinal = (float) ($layanan->pivot->persen_komisi ?: 15);
                $komisiFinal = (float) ($layanan->pivot->nominal_komisi ?: round($hargaSnap * ($persenFinal / 100), 2));
                $idKaryawanSimpan = null;
            }

            $subtotalLayanan += $hargaSnap;
            $totalKomisi += $komisiFinal;

            DB::table('detail_reservasi')
                ->where('id_reservasi', $reservasi->id_reservasi)
                ->where('id_layanan', $layanan->id_layanan)
                ->update([
                    'id_karyawan' => $idKaryawanSimpan,
                    'harga_saat_ini' => $hargaSnap,
                    'persen_komisi' => $persenFinal,
                    'nominal_komisi' => $komisiFinal,
                    'status_layanan' => 'Selesai',
                ]);
        }

        if ($idPetugasUtama !== null && (int) $reservasi->id_karyawan !== $idPetugasUtama) {
            $reservasi->update(['id_karyawan' => $idPetugasUtama]);
        }

        return [$subtotalLayanan, $totalKomisi];
    }

    private function validasiPetugasLayananFinal(
        Reservasi $reservasi,
        Layanan $layanan,
        ?int $idAwal,
        ?int $idPilihanKasir
    ): ?Karyawan {
        if ($idPilihanKasir === null) {
            return null;
        }

        $staf = Karyawan::withTrashed()
            ->where('id_karyawan', $idPilihanKasir)
            ->lockForUpdate()
            ->first();

        if (! $staf || $staf->trashed() || ! $staf->aktif) {
            $namaStaf = $staf->nama_staff ?? 'Petugas';

            throw new DomainException(
                "Petugas '{$namaStaf}' pada layanan '{$layanan->nama_layanan}' sudah tidak aktif atau telah dihapus. Silakan pilih {$layanan->jabatan_petugas} pengganti yang aktif."
            );
        }

        if ($staf->role_jabatan !== $layanan->jabatan_petugas) {
            throw new DomainException(
                "Petugas '{$staf->nama_staff}' ({$staf->role_jabatan}) tidak sesuai untuk layanan '{$layanan->nama_layanan}' yang membutuhkan {$layanan->jabatan_petugas}."
            );
        }

        // Jika kasir mengganti petugas ke orang yang berbeda dari petugas awal reservasi, pastikan tidak bentrok jadwal
        if ($idAwal !== null && (int) $staf->id_karyawan !== $idAwal) {
            $jamMulai = (string) ($layanan->pivot->jam_mulai ?: $reservasi->jam_booking ?: '10:00');
            $jamBlokir = (string) ($layanan->pivot->jam_selesai_blokir ?: $reservasi->jam_selesai ?: '11:15');

            $idSibuk = $this->jadwalService->cariIdPetugasSibukPadaRentang(
                $reservasi->tgl_booking->toDateString(),
                $jamMulai,
                $jamBlokir,
                (int) $reservasi->id_reservasi,
                true
            );

            if (in_array((int) $staf->id_karyawan, $idSibuk, true)) {
                throw new DomainException(
                    "Petugas pengganti '{$staf->nama_staff}' sedang memiliki jadwal reservasi aktif lain pada tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$jamMulai} - {$jamBlokir} WIB."
                );
            }
        }

        return $staf;
    }

    /**
     * Menghitung subtotal produk dan menyesuaikan selisih stok terhadap produk add-on reservasi
     * agar tidak terjadi pemotongan stok dua kali.
     *
     * @param  array<int|string, mixed>  $produkItems
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     */
    private function rekonsiliasiStokDanHitungProduk(Reservasi $reservasi, array $produkItems): array
    {
        $addonLamaMap = $reservasi->produkAddon->keyBy('id_produk');

        // Jika kasir tidak mengirim produk_items sama sekali namun reservasi punya produk add-on, gunakan produk add-on reservasi
        if (empty($produkItems) && $addonLamaMap->isNotEmpty()) {
            foreach ($addonLamaMap as $idProd => $prodAddon) {
                $produkItems[$idProd] = (int) $prodAddon->pivot->jumlah;
            }
        }

        $semuaIdProduk = array_values(array_unique(array_merge(
            array_map('intval', array_keys($produkItems)),
            $addonLamaMap->keys()->map(fn ($k) => (int) $k)->all()
        )));
        sort($semuaIdProduk);

        if (empty($semuaIdProduk)) {
            return [0.0, []];
        }

        $produkDbMap = Produk::withTrashed()
            ->whereIn('id_produk', $semuaIdProduk)
            ->orderBy('id_produk')
            ->lockForUpdate()
            ->get()
            ->keyBy('id_produk');

        $subtotalProduk = 0.0;
        $lampiranProduk = [];

        foreach ($semuaIdProduk as $idProduk) {
            $qtyFinal = max(0, (int) ($produkItems[$idProduk] ?? 0));
            $addonLama = $addonLamaMap->get($idProduk);
            $qtySudahDipotong = ($addonLama && $addonLama->pivot->stok_dipotong)
                ? (int) $addonLama->pivot->jumlah
                : 0;

            $produk = $produkDbMap->get($idProduk);
            if (! $produk) {
                continue;
            }

            $selisihStok = $qtyFinal - $qtySudahDipotong;

            if ($selisihStok > 0) {
                if ((int) $produk->stok_produk < $selisihStok) {
                    throw new DomainException(
                        "Stok produk '{$produk->nama_produk}' tidak mencukupi (tersisa {$produk->stok_produk} pcs)."
                    );
                }
                $produk->decrement('stok_produk', $selisihStok);
            } elseif ($selisihStok < 0) {
                $produk->increment('stok_produk', abs($selisihStok));
            }

            if ($qtyFinal > 0) {
                $hargaSatuan = $addonLama && (float) $addonLama->pivot->harga_saat_ini > 0
                    ? (float) $addonLama->pivot->harga_saat_ini
                    : (float) $produk->harga_produk;

                $subtotalProduk += ($hargaSatuan * $qtyFinal);
                $lampiranProduk[$produk->id_produk] = [
                    'jumlah' => $qtyFinal,
                    'harga_saat_ini' => $hargaSatuan,
                ];
            }
        }

        return [$subtotalProduk, $lampiranProduk];
    }

    /**
     * Menghitung diskon transaksi: menggunakan snapshot promo reservasi apabila kode sama,
     * atau memvalidasi promo baru jika kasir memilih/mengganti kode promo di POS.
     *
     * @return array{0: string|null, 1: float}
     */
    private function hitungDiskonTransaksi(Reservasi $reservasi, ?string $kodeInputKasir, float $subtotal): array
    {
        $kodeBersih = strtoupper(trim((string) ($kodeInputKasir ?? $reservasi->kode_promo ?? '')));

        if ($kodeBersih === '') {
            return [null, 0.0];
        }

        // Jika kode promo sama dengan kode yang sudah tersimpan pada reservasi saat booking,
        // gunakan aturan snapshot dari reservasi agar tidak gagal jika promo baru saja berakhir hari ini
        if ($reservasi->kode_promo && strtoupper($reservasi->kode_promo) === $kodeBersih) {
            if ($reservasi->tipe_diskon_promo === 'Persentase') {
                $persen = min(100.0, max(0.0, (float) $reservasi->nilai_diskon_promo));
                $potongan = min($subtotal, round($subtotal * ($persen / 100), 2));
            } elseif ((float) $reservasi->nilai_diskon_promo > 0) {
                $potongan = min($subtotal, (float) $reservasi->nilai_diskon_promo);
            } else {
                $potongan = min($subtotal, (float) $reservasi->nominal_diskon);
            }

            return [$reservasi->kode_promo, $potongan];
        }

        $infoPromo = $this->kalkulatorHarga->validasiDanHitungPromo($kodeBersih, $subtotal);

        return [$infoPromo['kode_promo'], $infoPromo['nominal_diskon']];
    }
}
