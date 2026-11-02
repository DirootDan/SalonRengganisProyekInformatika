<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\NotifikasiPelanggan;
use App\Models\Pelanggan;
use App\Models\Produk;
use App\Models\Reservasi;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReservasiService
{
    public function __construct(
        private readonly JadwalPetugasService $jadwalService,
        private readonly KalkulatorHargaService $kalkulatorHarga
    ) {}

    /**
     * Membuat reservasi baru di dalam transaksi DB dengan lockForUpdate pada petugas dan stok produk.
     *
     * @param  array<string, mixed>  $data
     */
    public function buatReservasi(Pelanggan $pelanggan, array $data): Reservasi
    {
        $idLayananUnik = array_values(array_unique(array_map('intval', (array) $data['layanan'])));
        $layananDipilih = Layanan::whereIn('id_layanan', $idLayananUnik)->orderBy('id_layanan')->get();

        if ($layananDipilih->isEmpty()) {
            throw new DomainException('Pilih minimal satu layanan perawatan yang aktif.');
        }

        $timeline = $this->jadwalService->susunTimelineLayanan($layananDipilih, (string) $data['jam_booking']);
        $petugasInput = (array) ($data['petugas'] ?? []);
        $produkInput = $this->saringProdukInput((array) ($data['produk_addon'] ?? []));
        $kodePromoInput = $data['kode_promo'] ?? null;

        return DB::transaction(function () use (
            $pelanggan,
            $data,
            $layananDipilih,
            $timeline,
            $petugasInput,
            $produkInput,
            $kodePromoInput
        ) {
            // 1. Kunci baris karyawan & validasi ketersediaan slot / auto-assign petugas
            $petugasTerpilih = $this->jadwalService->tentukanPetugasUntukTimeline(
                $layananDipilih,
                $timeline['items'],
                $petugasInput,
                (string) $data['tgl_booking'],
                null,
                true
            );

            // 2. Hitung subtotal layanan & susun lampiran pivot detail_reservasi
            [$subtotalLayanan, $lampiranLayanan, $idPetugasUtama] = $this->siapkanLampiranLayanan(
                $layananDipilih,
                $timeline['items'],
                $petugasTerpilih
            );

            // 3. Kunci baris produk, validasi stok, potong stok, & susun lampiran detail_reservasi_produk
            [$subtotalProduk, $lampiranProduk] = $this->kunciDanPotongStokProduk($produkInput);

            // 4. Validasi & hitung diskon promo di server (abaikan angka harga/diskon dari client)
            $subtotalGabungan = $subtotalLayanan + $subtotalProduk;
            $infoPromo = $this->kalkulatorHarga->validasiDanHitungPromo($kodePromoInput, $subtotalGabungan);

            // 5. Simpan reservasi beserta snapshot durasi & promo
            $reservasi = Reservasi::create([
                'id_pelanggan' => $pelanggan->id_pelanggan,
                'id_karyawan' => $idPetugasUtama,
                'tgl_booking' => $data['tgl_booking'],
                'jam_booking' => $timeline['jam_mulai'],
                'jam_selesai' => $timeline['jam_selesai'],
                'total_durasi_menit' => $timeline['total_durasi_menit'],
                'status' => Reservasi::STATUS_MENUNGGU,
                'id_promo' => $infoPromo['id_promo'],
                'kode_promo' => $infoPromo['kode_promo'],
                'tipe_diskon_promo' => $infoPromo['tipe_diskon_promo'],
                'nilai_diskon_promo' => $infoPromo['nilai_diskon_promo'],
                'nominal_diskon' => $infoPromo['nominal_diskon'],
            ]);

            $reservasi->layanan()->attach($lampiranLayanan);

            if (! empty($lampiranProduk)) {
                $reservasi->produkAddon()->attach($lampiranProduk);
            }

            return $reservasi;
        });
    }

    /**
     * Menerima permintaan reservasi pelanggan oleh Kasir dengan pengecekan anti-bentrok satu kueri.
     */
    public function terimaReservasiKasir(int $idReservasi): Reservasi
    {
        return DB::transaction(function () use ($idReservasi) {
            $reservasi = Reservasi::with('layanan')
                ->where('id_reservasi', $idReservasi)
                ->lockForUpdate()
                ->firstOrFail();

            $this->pastikanTidakBentrokDiterima($reservasi);
            $reservasi->ubahStatus(Reservasi::STATUS_DITERIMA);

            NotifikasiPelanggan::kirim(
                $reservasi->id_pelanggan,
                'Permintaan Booking Diterima',
                "Kabar gembira! Reservasi #{$reservasi->id_reservasi} untuk tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking} telah diterima oleh kasir. Silakan datang tepat waktu.",
                NotifikasiPelanggan::TIPE_SUKSES,
                $reservasi->id_reservasi
            );

            return $reservasi;
        });
    }

    /**
     * Menolak permintaan reservasi pelanggan oleh Kasir beserta alasan dan pengembalian stok otomatis.
     */
    public function tolakReservasiKasir(int $idReservasi, string $alasanPenolakan): Reservasi
    {
        return DB::transaction(function () use ($idReservasi, $alasanPenolakan) {
            $reservasi = Reservasi::where('id_reservasi', $idReservasi)
                ->lockForUpdate()
                ->firstOrFail();

            $reservasi->ubahStatus(Reservasi::STATUS_DITOLAK, [
                'alasan_penolakan' => $alasanPenolakan,
                'dibatalkan_oleh' => Reservasi::DIBATALKAN_OLEH_KASIR,
            ]);

            NotifikasiPelanggan::kirim(
                $reservasi->id_pelanggan,
                'Permintaan Booking Ditolak',
                "Mohon maaf, reservasi #{$reservasi->id_reservasi} untuk tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$reservasi->jam_booking} belum dapat kami terima. Alasan: {$alasanPenolakan}.",
                NotifikasiPelanggan::TIPE_BAHAYA,
                $reservasi->id_reservasi
            );

            return $reservasi;
        });
    }

    /**
     * Memastikan petugas pada reservasi tidak bentrok dengan reservasi lain yang sudah berstatus Diterima.
     */
    private function pastikanTidakBentrokDiterima(Reservasi $reservasi): void
    {
        $tanggal = $reservasi->tgl_booking->toDateString();
        $jadwalDiterima = $this->jadwalService->ambilJadwalAktifTanggal(
            $tanggal,
            (int) $reservasi->id_reservasi,
            true,
            [Reservasi::STATUS_DITERIMA]
        );

        foreach ($reservasi->layanan as $layanan) {
            $idStaf = $layanan->pivot->id_karyawan ? (int) $layanan->pivot->id_karyawan : null;
            if (! $idStaf) {
                continue;
            }

            [$jamMulai, $jamBlokir] = $this->tentukanRentangBlokirPivot($reservasi, $layanan);
            $idSibuk = $this->jadwalService->saringIdSibukDariKoleksi($jadwalDiterima, $jamMulai, $jamBlokir);

            if (in_array($idStaf, $idSibuk, true)) {
                throw new DomainException(
                    "Tidak dapat menerima reservasi #{$reservasi->id_reservasi} karena petugas sudah memiliki reservasi Diterima lain pada tanggal {$reservasi->tgl_booking->format('d/m/Y')} pukul {$jamMulai} - {$jamBlokir} WIB."
                );
            }
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function tentukanRentangBlokirPivot(Reservasi $reservasi, Layanan $layanan): array
    {
        $jamMulai = substr((string) ($layanan->pivot->jam_mulai ?: $reservasi->jam_booking ?: Reservasi::JAM_BUKA_SALON), 0, 5);

        if (! empty($layanan->pivot->jam_selesai_blokir)) {
            return [$jamMulai, substr((string) $layanan->pivot->jam_selesai_blokir, 0, 5)];
        }

        $jamBlokir = Carbon::createFromFormat('H:i', $jamMulai)
            ->addMinutes($layanan->totalBlokirMenit())
            ->format('H:i');

        return [$jamMulai, $jamBlokir];
    }

    /**
     * Menyaring array input produk add-on agar hanya menyisakan jumlah > 0.
     *
     * @param  array<int|string, mixed>  $produkRaw
     * @return array<int, int>
     */
    private function saringProdukInput(array $produkRaw): array
    {
        $hasil = [];

        foreach ($produkRaw as $idProduk => $jumlah) {
            $qty = (int) $jumlah;
            if ($qty > 0) {
                $hasil[(int) $idProduk] = $qty;
            }
        }

        ksort($hasil);

        return $hasil;
    }

    /**
     * Menyiapkan data pivot detail_reservasi beserta snapshot harga, komisi, dan jadwal per layanan.
     *
     * @return array{0: float, 1: array<int, array<string, mixed>>, 2: int|null}
     */
    private function siapkanLampiranLayanan($layananDipilih, array $timelineItems, array $petugasTerpilih): array
    {
        $subtotalLayanan = 0.0;
        $lampiranLayanan = [];
        $idPetugasUtama = null;

        foreach ($layananDipilih as $item) {
            $staf = $petugasTerpilih[$item->id_layanan];
            $slot = $timelineItems[$item->id_layanan];
            $harga = (float) $item->harga_layanan;
            $persenKomisi = (float) ($staf->persen_komisi ?? 15);
            $nominalKomisi = round($harga * ($persenKomisi / 100), 2);

            $subtotalLayanan += $harga;
            $idPetugasUtama ??= (int) $staf->id_karyawan;

            $lampiranLayanan[$item->id_layanan] = [
                'id_karyawan' => (int) $staf->id_karyawan,
                'jam_mulai' => $slot['jam_mulai'],
                'jam_selesai' => $slot['jam_selesai'],
                'jam_selesai_blokir' => $slot['jam_selesai_blokir'],
                'durasi_saat_ini' => $slot['durasi_saat_ini'],
                'jeda_saat_ini' => $slot['jeda_saat_ini'],
                'harga_saat_ini' => $harga,
                'persen_komisi' => $persenKomisi,
                'nominal_komisi' => $nominalKomisi,
                'status_layanan' => 'Menunggu',
            ];
        }

        return [$subtotalLayanan, $lampiranLayanan, $idPetugasUtama];
    }

    /**
     * Mengunci baris produk, memvalidasi ketersediaan stok, mengurangi stok, dan menyiapkan pivot produk.
     *
     * @param  array<int, int>  $produkInput
     * @return array{0: float, 1: array<int, array<string, mixed>>}
     */
    private function kunciDanPotongStokProduk(array $produkInput): array
    {
        if (empty($produkInput)) {
            return [0.0, []];
        }

        $daftarProduk = Produk::whereIn('id_produk', array_keys($produkInput))
            ->orderBy('id_produk')
            ->lockForUpdate()
            ->get()
            ->keyBy('id_produk');

        $subtotalProduk = 0.0;
        $lampiranProduk = [];

        foreach ($produkInput as $idProduk => $qty) {
            $produk = $daftarProduk->get($idProduk);

            if (! $produk) {
                throw new DomainException('Produk tambahan yang dipilih tidak ditemukan atau sudah tidak tersedia.');
            }

            if ((int) $produk->stok_produk < $qty) {
                throw new DomainException(
                    "Stok produk '{$produk->nama_produk}' tidak mencukupi (tersisa {$produk->stok_produk} pcs, diminta {$qty} pcs)."
                );
            }

            $produk->decrement('stok_produk', $qty);
            $hargaSatuan = (float) $produk->harga_produk;
            $subtotalProduk += ($hargaSatuan * $qty);

            $lampiranProduk[$produk->id_produk] = [
                'jumlah' => $qty,
                'harga_saat_ini' => $hargaSatuan,
                'stok_dipotong' => true,
            ];
        }

        return [$subtotalProduk, $lampiranProduk];
    }
}
