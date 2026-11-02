<?php

namespace App\Services;

use App\Models\Layanan;
use App\Models\LogPerubahanOtorisasi;
use App\Models\PermintaanOtorisasi;
use App\Models\Produk;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OtorisasiKatalogService
{
    /**
     * Menyusun peta status akses katalog aktif (Menunggu & Disetujui) serta riwayat penolakan terbaru milik Kasir.
     *
     * @return array{peta_akses: array<string, PermintaanOtorisasi>, penolakan_terbaru: Collection<int, PermintaanOtorisasi>}
     */
    public function ringkasanAksesKasir(int $idKasir, string $konteks): array
    {
        $this->sinkronkanKedaluwarsaOtomatis($idKasir, $konteks);

        $tiketAktif = PermintaanOtorisasi::with('manajer')
            ->where('id_kasir', $idKasir)
            ->where('konteks', $konteks)
            ->whereIn('status', [PermintaanOtorisasi::STATUS_MENUNGGU, PermintaanOtorisasi::STATUS_DISETUJUI])
            ->orderByDesc('id_otorisasi')
            ->get();

        $petaAkses = [];
        foreach ($tiketAktif as $tiket) {
            $kunci = self::kunciAkses($tiket->jenis_aksi, $tiket->id_target);
            $petaAkses[$kunci] ??= $tiket;
        }

        $penolakanTerbaru = PermintaanOtorisasi::with('manajer')
            ->where('id_kasir', $idKasir)
            ->where('konteks', $konteks)
            ->where('status', PermintaanOtorisasi::STATUS_DITOLAK)
            ->where('diputuskan_pada', '>=', now()->subHours(12))
            ->orderByDesc('diputuskan_pada')
            ->limit(5)
            ->get();

        return [
            'peta_akses' => $petaAkses,
            'penolakan_terbaru' => $penolakanTerbaru,
        ];
    }

    public static function kunciAkses(string $jenisAksi, ?int $idTarget): string
    {
        return "{$jenisAksi}:".($idTarget ?? 'baru');
    }

    /**
     * Mengajukan permintaan hak akses 1x pakai katalog Layanan atau Produk ke Manajer.
     *
     * @param  array<string, mixed>  $data
     */
    public function ajukanAkses(int $idKasir, array $data): PermintaanOtorisasi
    {
        $konteks = (string) $data['konteks'];
        $jenisAksi = (string) $data['jenis_aksi'];
        $idTarget = $jenisAksi === PermintaanOtorisasi::AKSI_TAMBAH ? null : (int) $data['id_target'];
        $alasan = trim((string) $data['alasan_kasir']);

        [$tipeTarget, $labelTarget, $hargaSaatIni, $snapshotTarget] = $this->validasiDanSusunInfoTarget($konteks, $jenisAksi, $idTarget);
        $this->sinkronkanKedaluwarsaOtomatis($idKasir, $konteks);

        return DB::transaction(function () use ($idKasir, $konteks, $jenisAksi, $tipeTarget, $idTarget, $labelTarget, $hargaSaatIni, $snapshotTarget, $alasan) {

            $masihAktif = PermintaanOtorisasi::where('id_kasir', $idKasir)
                ->where('konteks', $konteks)
                ->where('jenis_aksi', $jenisAksi)
                ->when($idTarget === null, fn ($q) => $q->whereNull('id_target'), fn ($q) => $q->where('id_target', $idTarget))
                ->whereIn('status', [PermintaanOtorisasi::STATUS_MENUNGGU, PermintaanOtorisasi::STATUS_DISETUJUI])
                ->lockForUpdate()
                ->exists();

            if ($masihAktif) {
                throw new DomainException("Anda masih memiliki pengajuan atau hak akses aktif untuk '{$labelTarget}'.");
            }

            $this->bersihkanPenolakanLama($idKasir, $konteks, $jenisAksi, $idTarget);

            return PermintaanOtorisasi::create([
                'id_kasir' => $idKasir,
                'konteks' => $konteks,
                'jenis_aksi' => $jenisAksi,
                'tipe_target' => $tipeTarget,
                'id_target' => $idTarget,
                'label_target' => $labelTarget,
                'alasan_kasir' => $alasan,
                'total_awal' => $hargaSaatIni,
                'total_akhir' => $hargaSaatIni,
                'snapshot_awal' => $snapshotTarget,
                'sedang_dihold' => false,
            ]);
        });
    }

    /**
     * Menarik pengajuan pending atau membatalkan/mengunci kembali hak akses 1x pakai yang belum digunakan.
     */
    public function batalkanAtauKunciAkses(int $idOtorisasi, int $idKasir): PermintaanOtorisasi
    {
        return DB::transaction(function () use ($idOtorisasi, $idKasir) {
            $tiket = PermintaanOtorisasi::where('id_otorisasi', $idOtorisasi)->lockForUpdate()->firstOrFail();

            if ((int) $tiket->id_kasir !== $idKasir) {
                throw new DomainException('Anda tidak berhak membatalkan permintaan akses milik kasir lain.');
            }
            if (! in_array($tiket->konteks, [PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN, PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK], true)) {
                throw new DomainException('Permintaan ini bukan merupakan hak akses katalog.');
            }
            if (! $tiket->bolehTransisiKe(PermintaanOtorisasi::STATUS_DIBATALKAN)) {
                throw new DomainException("Status akses #{$tiket->kode_referensi} sudah '{$tiket->status}' dan tidak dapat dibatalkan.");
            }

            $tiket->batalkanOlehKasir();

            return $tiket;
        });
    }

    /**
     * Mengeksekusi aksi CRUD katalog 1x pakai secara atomik di dalam DB::transaction().
     * Jika validasi bisnis gagal atau terjadi exception, transaksi di-rollback sehingga kuota 1x pakai TIDAK hangus.
     *
     * @param  callable(PermintaanOtorisasi): array{label_target: string, id_target: int|null, nilai_awal: float, nilai_akhir: float, data_sebelum: array<string, mixed>|null, data_sesudah: array<string, mixed>|null, hasil?: mixed}  $operasiDatabase
     */
    public function eksekusiSatuKaliPakai(int $idKasir, string $konteks, string $jenisAksi, ?int $idTarget, callable $operasiDatabase): mixed
    {
        $this->sinkronkanKedaluwarsaOtomatis($idKasir, $konteks);

        return DB::transaction(function () use ($idKasir, $konteks, $jenisAksi, $idTarget, $operasiDatabase) {

            $tiket = PermintaanOtorisasi::izinKatalogSiapPakai($idKasir, $konteks, $jenisAksi, $idTarget)
                ->orderByDesc('id_otorisasi')
                ->lockForUpdate()
                ->first();

            if (! $tiket) {
                throw new DomainException(
                    'Akses terkunci: Anda wajib memiliki persetujuan 1x pakai aktif dari Manajer untuk melakukan aksi ini. Silakan ajukan request akses terlebih dahulu.'
                );
            }

            $infoPerubahan = $operasiDatabase($tiket);

            $tiket->tandaiDigunakan();

            LogPerubahanOtorisasi::create([
                'id_otorisasi' => $tiket->id_otorisasi,
                'id_kasir' => $idKasir,
                'id_manajer' => $tiket->id_manajer,
                'konteks' => $konteks,
                'jenis_perubahan' => $this->petakanJenisPerubahanLog($jenisAksi),
                'mekanisme_otorisasi' => LogPerubahanOtorisasi::MEKANISME_APPROVAL_DASHBOARD,
                'tipe_target' => $tiket->tipe_target,
                'id_target' => $infoPerubahan['id_target'] ?? $idTarget,
                'label_target' => $infoPerubahan['label_target'],
                'alasan' => $tiket->alasan_kasir,
                'nilai_awal' => $infoPerubahan['nilai_awal'],
                'nilai_akhir' => $infoPerubahan['nilai_akhir'],
                'data_sebelum' => $infoPerubahan['data_sebelum'],
                'data_sesudah' => $infoPerubahan['data_sesudah'],
            ]);

            return $infoPerubahan['hasil'] ?? null;
        });
    }

    public function sinkronkanKedaluwarsaOtomatis(int $idKasir, ?string $konteks = null): int
    {
        return PermintaanOtorisasi::where('id_kasir', $idKasir)
            ->when(
                $konteks !== null,
                fn ($q) => $q->where('konteks', $konteks),
                fn ($q) => $q->whereIn('konteks', [PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN, PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK])
            )
            ->where('status', PermintaanOtorisasi::STATUS_DISETUJUI)
            ->whereNull('digunakan_pada')
            ->whereNotNull('kedaluwarsa_pada')
            ->where('kedaluwarsa_pada', '<=', now())
            ->update([
                'status' => PermintaanOtorisasi::STATUS_KEDALUWARSA,
                'sedang_dihold' => false,
            ]);
    }

    /**
     * @return array{0: string, 1: string, 2: float, 3: array<string, mixed>|null}
     */
    private function validasiDanSusunInfoTarget(string $konteks, string $jenisAksi, ?int $idTarget): array
    {
        $labelAksi = match ($jenisAksi) {
            PermintaanOtorisasi::AKSI_TAMBAH => 'Tambah',
            PermintaanOtorisasi::AKSI_UBAH => 'Ubah',
            PermintaanOtorisasi::AKSI_HAPUS => 'Hapus',
            default => throw new DomainException('Jenis aksi katalog tidak dikenali.'),
        };

        if ($konteks === PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN) {
            if ($jenisAksi === PermintaanOtorisasi::AKSI_TAMBAH) {
                return [PermintaanOtorisasi::TARGET_LAYANAN, 'Tambah Layanan Baru', 0.0, null];
            }
            $layanan = Layanan::findOrFail($idTarget);
            $snap = $layanan->only(['id_layanan', 'nama_layanan', 'kategori', 'jabatan_petugas', 'durasi_menit', 'jeda_menit', 'harga_layanan']);

            return [PermintaanOtorisasi::TARGET_LAYANAN, "{$labelAksi} Layanan: {$layanan->nama_layanan} (#{$layanan->id_layanan})", (float) $layanan->harga_layanan, $snap];
        }

        if ($konteks === PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK) {
            if ($jenisAksi === PermintaanOtorisasi::AKSI_TAMBAH) {
                return [PermintaanOtorisasi::TARGET_PRODUK, 'Tambah Produk Baru', 0.0, null];
            }
            $produk = Produk::findOrFail($idTarget);
            $snap = $produk->only(['id_produk', 'nama_produk', 'stok_produk', 'harga_produk']);

            return [PermintaanOtorisasi::TARGET_PRODUK, "{$labelAksi} Produk: {$produk->nama_produk} (#{$produk->id_produk})", (float) $produk->harga_produk, $snap];
        }

        throw new DomainException('Konteks katalog tidak valid.');
    }

    private function bersihkanPenolakanLama(int $idKasir, string $konteks, string $jenisAksi, ?int $idTarget): void
    {
        $ditolakLama = PermintaanOtorisasi::where('id_kasir', $idKasir)
            ->where('konteks', $konteks)
            ->where('jenis_aksi', $jenisAksi)
            ->when($idTarget === null, fn ($q) => $q->whereNull('id_target'), fn ($q) => $q->where('id_target', $idTarget))
            ->where('status', PermintaanOtorisasi::STATUS_DITOLAK)
            ->lockForUpdate()
            ->get();

        foreach ($ditolakLama as $lama) {
            $lama->batalkanOlehKasir();
        }
    }

    private function petakanJenisPerubahanLog(string $jenisAksi): string
    {
        return match ($jenisAksi) {
            PermintaanOtorisasi::AKSI_TAMBAH => LogPerubahanOtorisasi::JENIS_TAMBAH_KATALOG,
            PermintaanOtorisasi::AKSI_UBAH => LogPerubahanOtorisasi::JENIS_UBAH_KATALOG,
            default => LogPerubahanOtorisasi::JENIS_HAPUS_KATALOG,
        };
    }
}
