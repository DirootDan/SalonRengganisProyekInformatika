<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\Layanan;
use App\Models\MetodePembayaran;
use App\Models\Produk;
use App\Models\Reservasi;
use App\Models\Transaksi;
use DomainException;
use Illuminate\Support\Facades\DB;

class PembayaranKasirService
{
    public function __construct(
        private readonly JadwalPetugasService $jadwalService,
        private readonly KalkulatorHargaService $kalkulatorHarga,
        private readonly OtorisasiPosService $otorisasiPos
    ) {}

    /**
     * Memproses pembayaran reservasi maupun walk-in beserta otorisasi perubahan layanan/override harga POS.
     *
     * @param  array<string, mixed>  $data
     */
    public function prosesBayar(int $idKasir, array $data): Transaksi
    {
        return DB::transaction(function () use ($idKasir, $data) {
            $this->validasiMetodePembayaran((string) $data['metode_bayar']);

            $reservasiExisting = ! empty($data['id_reservasi'])
                ? $this->ambilDanValidasiReservasi((int) $data['id_reservasi'])
                : null;

            $konteksOtorisasi = $this->otorisasiPos->siapkanOtorisasiSebelumBayar($idKasir, $data);
            $usulan = $konteksOtorisasi['evaluasi']['snapshot_usulan'];

            $reservasi = $reservasiExisting
                ? $this->sinkronkanLayananReservasi($reservasiExisting, $usulan['layanan'])
                : $this->buatReservasiWalkIn($idKasir, $data, $usulan['layanan']);

            $petugasInput = (array) ($data['petugas_layanan'] ?? []);
            $idPetugasFallback = ! empty($data['id_petugas']) ? (int) $data['id_petugas'] : null;
            $petaHargaLayanan = collect($usulan['layanan'])->pluck('harga_aktif', 'id_layanan')->all();

            [$subtotalLayanan, $totalKomisi] = $this->tetapkanPetugasFinalDanHitungKomisi(
                $reservasi,
                $petugasInput,
                $idPetugasFallback,
                $petaHargaLayanan
            );

            [$subtotalProduk, $lampiranProduk] = $this->rekonsiliasiStokDanHitungProduk($reservasi, $usulan['produk']);

            $subtotal = $subtotalLayanan + $subtotalProduk;
            [$kodePromoFinal, $diskonFinal] = $this->hitungDiskonTransaksi($reservasi, $usulan['kode_promo'], $subtotal);

            $totalBayar = $this->kalkulatorHarga->hitungTotalAkhir($subtotalLayanan, $subtotalProduk, $diskonFinal);
            $nominalUang = (float) $data['nominal_uang'];

            if ($nominalUang < $totalBayar) {
                throw new DomainException(
                    'Nominal uang yang diterima kurang dari total tagihan (Rp '.number_format($totalBayar, 0, ',', '.').').'
                );
            }

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
                'kembalian' => round($nominalUang - $totalBayar, 2),
                'metode_bayar' => $data['metode_bayar'],
                'komisi_stylist' => $totalKomisi,
            ]);

            if (! empty($lampiranProduk)) {
                $transaksi->produk()->attach($lampiranProduk);
            }

            $this->otorisasiPos->selesaikanOtorisasiSetelahBayar($idKasir, $transaksi, $reservasi->id_reservasi, $konteksOtorisasi);

            return $transaksi;
        });
    }

    private function validasiMetodePembayaran(string $namaMetode): void
    {
        if (! MetodePembayaran::where('aktif', true)->exists()) {
            return;
        }

        if (! MetodePembayaran::where('nama_metode', $namaMetode)->where('aktif', true)->exists()) {
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
            throw new DomainException("Reservasi #{$reservasi->id_reservasi} berstatus '{$status}' dan tidak dapat diproses pembayarannya.");
        }

        return $reservasi;
    }

    /**
     * Menyinkronkan penambahan atau pengurangan layanan pada reservasi saat checkout di POS.
     *
     * @param  array<int, array<string, mixed>>  $layananUsulan
     */
    private function sinkronkanLayananReservasi(Reservasi $reservasi, array $layananUsulan): Reservasi
    {
        $idUsulan = array_map(fn (array $i) => (int) $i['id_layanan'], $layananUsulan);
        $idLama = $reservasi->layanan->pluck('id_layanan')->map(fn ($v) => (int) $v)->all();

        $idDihapus = array_diff($idLama, $idUsulan);
        if (! empty($idDihapus)) {
            DB::table('detail_reservasi')
                ->where('id_reservasi', $reservasi->id_reservasi)
                ->whereIn('id_layanan', $idDihapus)
                ->delete();
        }

        $idDitambah = array_diff($idUsulan, $idLama);
        if (! empty($idDitambah)) {
            $jamRef = (string) ($reservasi->jam_booking ?: Reservasi::JAM_BUKA_SALON);
            foreach (Layanan::whereIn('id_layanan', $idDitambah)->get() as $baru) {
                $reservasi->layanan()->attach($baru->id_layanan, [
                    'id_karyawan' => null,
                    'jam_mulai' => $jamRef,
                    'jam_selesai' => $jamRef,
                    'jam_selesai_blokir' => $jamRef,
                    'durasi_saat_ini' => (int) ($baru->durasi_menit ?: Layanan::DEFAULT_DURASI_MENIT),
                    'jeda_saat_ini' => (int) ($baru->jeda_menit ?? Layanan::DEFAULT_JEDA_MENIT),
                    'harga_saat_ini' => (float) $baru->harga_layanan,
                    'persen_komisi' => 15,
                    'nominal_komisi' => 0,
                    'status_layanan' => Reservasi::STATUS_SELESAI,
                ]);
            }
        }

        return $reservasi->load(['layanan', 'produkAddon']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $layananUsulan
     */
    private function buatReservasiWalkIn(int $idKasir, array $data, array $layananUsulan): Reservasi
    {
        $namaWalkIn = trim((string) ($data['nama_pelanggan_walkin'] ?? ''));
        if ($namaWalkIn === '') {
            throw new DomainException('Nama pelanggan walk-in wajib diisi untuk transaksi tanpa reservasi.');
        }

        $idPetugasWalkIn = ! empty($data['id_petugas']) ? (int) $data['id_petugas'] : null;
        $stafWalkIn = $idPetugasWalkIn ? Karyawan::where('aktif', true)->find($idPetugasWalkIn) : null;
        $persenWalkIn = (float) ($stafWalkIn->persen_komisi ?? 15);
        $jamSekarang = now()->format('H:i');

        $reservasi = Reservasi::create([
            'id_pelanggan' => null,
            'nama_pelanggan_walkin' => $namaWalkIn,
            'id_karyawan' => $stafWalkIn?->id_karyawan ?? $idKasir,
            'tgl_booking' => now()->toDateString(),
            'jam_booking' => $jamSekarang,
            'jam_selesai' => $jamSekarang,
            'total_durasi_menit' => 60,
            'status' => Reservasi::STATUS_DITERIMA,
        ]);

        $lampiran = [];
        $katalogMap = Layanan::whereIn('id_layanan', array_column($layananUsulan, 'id_layanan'))->get()->keyBy('id_layanan');

        foreach ($layananUsulan as $item) {
            $master = $katalogMap->get((int) $item['id_layanan']);
            if (! $master) {
                continue;
            }
            $harga = (float) $item['harga_aktif'];
            $lampiran[$master->id_layanan] = [
                'id_karyawan' => $stafWalkIn?->id_karyawan,
                'jam_mulai' => $jamSekarang,
                'jam_selesai' => $jamSekarang,
                'jam_selesai_blokir' => $jamSekarang,
                'durasi_saat_ini' => (int) ($master->durasi_menit ?: 60),
                'jeda_saat_ini' => (int) ($master->jeda_menit ?? 15),
                'harga_saat_ini' => $harga,
                'persen_komisi' => $persenWalkIn,
                'nominal_komisi' => round($harga * ($persenWalkIn / 100), 2),
                'status_layanan' => Reservasi::STATUS_SELESAI,
            ];
        }

        if (! empty($lampiran)) {
            $reservasi->layanan()->attach($lampiran);
        }

        return $reservasi->load(['layanan', 'produkAddon']);
    }

    /**
     * @param  array<int|string, mixed>  $petugasInput
     * @param  array<int, float>  $petaHargaLayanan
     * @return array{0: float, 1: float}
     */
    private function tetapkanPetugasFinalDanHitungKomisi(Reservasi $reservasi, array $petugasInput, ?int $idFallback, array $petaHargaLayanan): array
    {
        $subtotalLayanan = 0.0;
        $totalKomisi = 0.0;
        $idPetugasUtama = null;

        foreach ($reservasi->layanan as $layanan) {
            $idAwal = $layanan->pivot->id_karyawan ? (int) $layanan->pivot->id_karyawan : null;
            $idPilihan = ! empty($petugasInput[$layanan->id_layanan]) ? (int) $petugasInput[$layanan->id_layanan] : ($idAwal ?? $idFallback);
            $petugasFinal = $this->validasiPetugasLayananFinal($reservasi, $layanan, $idAwal, $idPilihan);

            $hargaSnap = array_key_exists($layanan->id_layanan, $petaHargaLayanan)
                ? (float) $petaHargaLayanan[$layanan->id_layanan]
                : (float) ($layanan->pivot->harga_saat_ini > 0 ? $layanan->pivot->harga_saat_ini : $layanan->harga_layanan);

            $persenFinal = $petugasFinal ? (float) $petugasFinal->persen_komisi : (float) ($layanan->pivot->persen_komisi ?: 15);
            $komisiFinal = $petugasFinal ? round($hargaSnap * ($persenFinal / 100), 2) : 0.0;
            $idSimpan = $petugasFinal ? (int) $petugasFinal->id_karyawan : null;
            $idPetugasUtama ??= $idSimpan;

            $subtotalLayanan += $hargaSnap;
            $totalKomisi += $komisiFinal;

            DB::table('detail_reservasi')
                ->where('id_reservasi', $reservasi->id_reservasi)
                ->where('id_layanan', $layanan->id_layanan)
                ->update([
                    'id_karyawan' => $idSimpan,
                    'harga_saat_ini' => $hargaSnap,
                    'persen_komisi' => $persenFinal,
                    'nominal_komisi' => $komisiFinal,
                    'status_layanan' => Reservasi::STATUS_SELESAI,
                ]);
        }

        if ($idPetugasUtama !== null && (int) $reservasi->id_karyawan !== $idPetugasUtama) {
            $reservasi->update(['id_karyawan' => $idPetugasUtama]);
        }

        return [$subtotalLayanan, $totalKomisi];
    }

    private function validasiPetugasLayananFinal(Reservasi $reservasi, Layanan $layanan, ?int $idAwal, ?int $idPilihanKasir): ?Karyawan
    {
        if ($idPilihanKasir === null) {
            return null;
        }

        $staf = Karyawan::withTrashed()->where('id_karyawan', $idPilihanKasir)->lockForUpdate()->first();
        if (! $staf || $staf->trashed() || ! $staf->aktif) {
            $namaStaf = $staf->nama_staff ?? 'Petugas';
            throw new DomainException("Petugas '{$namaStaf}' pada layanan '{$layanan->nama_layanan}' sudah tidak aktif atau telah dihapus. Silakan pilih {$layanan->jabatan_petugas} pengganti yang aktif.");
        }

        if ($staf->role_jabatan !== $layanan->jabatan_petugas) {
            throw new DomainException("Petugas '{$staf->nama_staff}' ({$staf->role_jabatan}) tidak sesuai untuk layanan '{$layanan->nama_layanan}' yang membutuhkan {$layanan->jabatan_petugas}.");
        }

        if ($idAwal !== null && (int) $staf->id_karyawan !== $idAwal) {
            $jamMulai = (string) ($layanan->pivot->jam_mulai ?: $reservasi->jam_booking ?: '10:00');
            $jamBlokir = (string) ($layanan->pivot->jam_selesai_blokir ?: $reservasi->jam_selesai ?: '11:15');
            $idSibuk = $this->jadwalService->cariIdPetugasSibukPadaRentang($reservasi->tgl_booking->toDateString(), $jamMulai, $jamBlokir, (int) $reservasi->id_reservasi, true);

            if (in_array((int) $staf->id_karyawan, $idSibuk, true)) {
                throw new DomainException("Petugas pengganti '{$staf->nama_staff}' sedang memiliki jadwal reservasi aktif lain pada tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$jamMulai} - {$jamBlokir} WIB.");
            }
        }

        return $staf;
    }

    /**
     * @param  array<int, array<string, mixed>>  $produkUsulan
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     */
    private function rekonsiliasiStokDanHitungProduk(Reservasi $reservasi, array $produkUsulan): array
    {
        $addonLamaMap = $reservasi->produkAddon->keyBy('id_produk');
        $usulanMap = collect($produkUsulan)->keyBy('id_produk');

        $semuaId = array_values(array_unique(array_merge($usulanMap->keys()->map('intval')->all(), $addonLamaMap->keys()->map('intval')->all())));
        sort($semuaId);

        if (empty($semuaId)) {
            return [0.0, []];
        }

        $produkDbMap = Produk::withTrashed()->whereIn('id_produk', $semuaId)->orderBy('id_produk')->lockForUpdate()->get()->keyBy('id_produk');
        $subtotalProduk = 0.0;
        $lampiranProduk = [];

        foreach ($semuaId as $idProduk) {
            $itemUsulan = $usulanMap->get($idProduk);
            $qtyFinal = $itemUsulan ? max(0, (int) $itemUsulan['qty']) : 0;
            $addonLama = $addonLamaMap->get($idProduk);
            $qtySudahDipotong = ($addonLama && $addonLama->pivot->stok_dipotong) ? (int) $addonLama->pivot->jumlah : 0;

            $produk = $produkDbMap->get($idProduk);
            if (! $produk) {
                continue;
            }

            $selisihStok = $qtyFinal - $qtySudahDipotong;
            if ($selisihStok > 0) {
                if ((int) $produk->stok_produk < $selisihStok) {
                    throw new DomainException("Stok produk '{$produk->nama_produk}' tidak mencukupi (tersisa {$produk->stok_produk} pcs).");
                }
                $produk->decrement('stok_produk', $selisihStok);
            } elseif ($selisihStok < 0) {
                $produk->increment('stok_produk', abs($selisihStok));
            }

            if ($qtyFinal > 0) {
                $hargaSatuan = (float) $itemUsulan['harga_aktif'];
                $subtotalProduk += ($hargaSatuan * $qtyFinal);
                $lampiranProduk[$produk->id_produk] = ['jumlah' => $qtyFinal, 'harga_saat_ini' => $hargaSatuan];
            }
        }

        return [$subtotalProduk, $lampiranProduk];
    }

    /**
     * @return array{0: string|null, 1: float}
     */
    private function hitungDiskonTransaksi(Reservasi $reservasi, ?string $kodePromoTerpilih, float $subtotal): array
    {
        $kodeBersih = strtoupper(trim((string) ($kodePromoTerpilih ?? '')));
        if ($kodeBersih === '') {
            return [null, 0.0];
        }

        if ($reservasi->kode_promo && strtoupper($reservasi->kode_promo) === $kodeBersih) {
            $potongan = $reservasi->tipe_diskon_promo === 'Persentase'
                ? round($subtotal * (min(100.0, max(0.0, (float) $reservasi->nilai_diskon_promo)) / 100), 2)
                : ((float) $reservasi->nilai_diskon_promo > 0 ? (float) $reservasi->nilai_diskon_promo : (float) $reservasi->nominal_diskon);

            return [$reservasi->kode_promo, min($subtotal, $potongan)];
        }

        $infoPromo = $this->kalkulatorHarga->validasiDanHitungPromo($kodeBersih, $subtotal);

        return [$infoPromo['kode_promo'], $infoPromo['nominal_diskon']];
    }
}
