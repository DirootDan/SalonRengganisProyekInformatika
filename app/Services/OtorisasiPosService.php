<?php

namespace App\Services;

use App\Models\Karyawan;
use App\Models\LogPerubahanOtorisasi;
use App\Models\PermintaanOtorisasi;
use App\Models\Transaksi;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class OtorisasiPosService
{
    public const MAKS_GAGAL_DIRECT_OVERRIDE = 4;

    public const DURASI_KUNCI_DETIK = 60;

    public function __construct(
        private readonly KalkulatorKeranjangPosService $kalkulatorKeranjang
    ) {}

    public function sisaDetikTerkunci(int $idKasir, string $ip): int
    {
        $kunci = $this->kunciRateLimiter($idKasir, $ip);

        return RateLimiter::tooManyAttempts($kunci, self::MAKS_GAGAL_DIRECT_OVERRIDE)
            ? RateLimiter::availableIn($kunci)
            : 0;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ajukanDanHoldPos(int $idKasir, array $data): PermintaanOtorisasi
    {
        $evaluasi = $this->kalkulatorKeranjang->evaluasiKeranjang($data + ['keranjang_disinkronkan' => true]);
        if (! $evaluasi['ada_perubahan']) {
            throw new DomainException('Tidak ada perubahan harga atau layanan yang perlu diajukan ke Manajer.');
        }

        $alasan = trim((string) ($data['alasan_kasir'] ?? ''));

        return DB::transaction(function () use ($idKasir, $data, $evaluasi, $alasan) {
            $this->tutupTiketLamaYangMenggantung($idKasir, $data);

            return $this->buatTiketPos($idKasir, $evaluasi, $alasan, true);
        });
    }

    /**
     * Memvalidasi kredensial Manajer di tempat tanpa mengubah sesi Kasir (Zero Session Hijacking)
     * dan otomatis menyetujui tiket pending terkait (Anti-Race Condition).
     *
     * @param  array<string, mixed>  $data
     */
    public function verifikasiDirectOverridePos(int $idKasir, string $ip, array $data): PermintaanOtorisasi
    {
        $manajer = $this->validasiKredensialManajer($idKasir, $ip, (string) $data['username_manajer'], (string) $data['password_manajer']);
        $evaluasi = $this->kalkulatorKeranjang->evaluasiKeranjang($data + ['keranjang_disinkronkan' => true]);

        if (! $evaluasi['ada_perubahan']) {
            throw new DomainException('Tidak ada perubahan harga atau layanan pada keranjang saat ini.');
        }

        $alasan = trim((string) ($data['alasan_kasir'] ?? ''));

        return DB::transaction(function () use ($idKasir, $manajer, $data, $evaluasi, $alasan) {
            $tiket = $this->ambilTiketPendingTerkait($idKasir, $data);
            if ($tiket) {
                $tiket->fill($this->atributSnapshotPos($evaluasi, $alasan, false))->save();
            } else {
                $this->tutupTiketLamaYangMenggantung($idKasir, $data);
                $tiket = $this->buatTiketPos($idKasir, $evaluasi, $alasan, false);
            }

            $tiket->setujui($manajer->id_karyawan, PermintaanOtorisasi::METODE_KREDENSIAL_LANGSUNG, 'Disetujui langsung di meja kasir (Direct Override).');

            return $tiket;
        });
    }

    public function setujuiOlehManajer(int $idOtorisasi, int $idManajer, ?string $catatan = null): PermintaanOtorisasi
    {
        return DB::transaction(function () use ($idOtorisasi, $idManajer, $catatan) {
            $tiket = PermintaanOtorisasi::where('id_otorisasi', $idOtorisasi)->lockForUpdate()->firstOrFail();
            $this->pastikanMasihMenungguDiDashboard($tiket, 'disetujui');
            $tiket->setujui($idManajer, PermintaanOtorisasi::METODE_DASHBOARD_MANAJER, $catatan);

            return $tiket;
        });
    }

    public function tolakOlehManajer(int $idOtorisasi, int $idManajer, string $catatanPenolakan): PermintaanOtorisasi
    {
        return DB::transaction(function () use ($idOtorisasi, $idManajer, $catatanPenolakan) {
            $tiket = PermintaanOtorisasi::where('id_otorisasi', $idOtorisasi)->lockForUpdate()->firstOrFail();
            $this->pastikanMasihMenungguDiDashboard($tiket, 'ditolak');
            $tiket->tolak($idManajer, $catatanPenolakan);

            return $tiket;
        });
    }

    public function batalkanHoldOlehKasir(int $idOtorisasi, int $idKasir): PermintaanOtorisasi
    {
        return DB::transaction(function () use ($idOtorisasi, $idKasir) {
            $tiket = PermintaanOtorisasi::where('id_otorisasi', $idOtorisasi)->lockForUpdate()->firstOrFail();
            if ((int) $tiket->id_kasir !== $idKasir) {
                throw new DomainException('Anda tidak berhak membatalkan transaksi Hold milik kasir lain.');
            }
            if ($tiket->status === PermintaanOtorisasi::STATUS_DIGUNAKAN) {
                throw new DomainException("Transaksi Hold #{$tiket->kode_referensi} sudah selesai dibayar.");
            }
            $tiket->batalkanOlehKasir();

            return $tiket;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function siapkanOtorisasiSebelumBayar(int $idKasir, array $data): array
    {
        $evaluasi = $this->kalkulatorKeranjang->evaluasiKeranjang($data);
        $tiket = ! empty($data['id_otorisasi'])
            ? PermintaanOtorisasi::where('id_otorisasi', (int) $data['id_otorisasi'])->lockForUpdate()->first()
            : null;

        if (! $evaluasi['ada_perubahan']) {
            return ['evaluasi' => $evaluasi, 'tiket' => $tiket, 'alasan' => null, 'mekanisme' => null];
        }

        $alasanInput = trim((string) ($data['alasan_perubahan'] ?? $tiket?->alasan_kasir ?? ''));
        if (mb_strlen($alasanInput) < 5) {
            throw new DomainException('Alasan perubahan harga atau layanan wajib diisi (minimal 5 karakter).');
        }

        if (! $evaluasi['wajib_approval']) {
            return ['evaluasi' => $evaluasi, 'tiket' => $tiket, 'alasan' => $alasanInput, 'mekanisme' => LogPerubahanOtorisasi::MEKANISME_MANDIRI_BAWAH_AMBANG];
        }

        if (! $tiket || $tiket->status !== PermintaanOtorisasi::STATUS_DISETUJUI) {
            throw new DomainException('Transaksi bernilai Rp 150.000 ke atas wajib mendapat persetujuan Manajer terlebih dahulu.');
        }
        if ((int) $tiket->id_kasir !== $idKasir || $tiket->hash_keranjang !== $evaluasi['hash_usulan']) {
            throw new DomainException('Isi keranjang atau harga telah berubah setelah disetujui Manajer. Silakan ajukan persetujuan ulang.');
        }

        $mekanisme = $tiket->metode_persetujuan === PermintaanOtorisasi::METODE_KREDENSIAL_LANGSUNG
            ? LogPerubahanOtorisasi::MEKANISME_KREDENSIAL_LANGSUNG
            : LogPerubahanOtorisasi::MEKANISME_APPROVAL_DASHBOARD;

        return ['evaluasi' => $evaluasi, 'tiket' => $tiket, 'alasan' => $tiket->alasan_kasir, 'mekanisme' => $mekanisme];
    }

    /**
     * @param  array<string, mixed>  $konteksOtorisasi
     */
    public function selesaikanOtorisasiSetelahBayar(int $idKasir, Transaksi $transaksi, ?int $idReservasi, array $konteksOtorisasi): void
    {
        /** @var PermintaanOtorisasi|null $tiket */
        $tiket = $konteksOtorisasi['tiket'] ?? null;
        if ($tiket && $tiket->bolehTransisiKe(PermintaanOtorisasi::STATUS_DIGUNAKAN)) {
            $tiket->tandaiDigunakan($transaksi->id_transaksi);
        }

        $evaluasi = $konteksOtorisasi['evaluasi'];
        if (! $evaluasi['ada_perubahan']) {
            return;
        }

        LogPerubahanOtorisasi::create([
            'id_otorisasi' => $tiket?->id_otorisasi,
            'id_kasir' => $idKasir,
            'id_manajer' => $tiket?->id_manajer,
            'id_reservasi' => $idReservasi,
            'id_transaksi' => $transaksi->id_transaksi,
            'konteks' => PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI,
            'jenis_perubahan' => $evaluasi['jenis_perubahan'],
            'mekanisme_otorisasi' => $konteksOtorisasi['mekanisme'],
            'tipe_target' => $idReservasi ? PermintaanOtorisasi::TARGET_RESERVASI : PermintaanOtorisasi::TARGET_WALK_IN,
            'id_target' => $idReservasi,
            'label_target' => $evaluasi['label_target'],
            'alasan' => $konteksOtorisasi['alasan'],
            'nilai_awal' => $evaluasi['total_awal'],
            'nilai_akhir' => $evaluasi['total_akhir'],
            'data_sebelum' => $evaluasi['snapshot_awal'],
            'data_sesudah' => $evaluasi['snapshot_usulan'],
        ]);
    }

    public function hanguskanIzinKatalogSaatLogout(int $idKasir): int
    {
        return PermintaanOtorisasi::where('id_kasir', $idKasir)
            ->whereIn('konteks', [PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN, PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK])
            ->where('status', PermintaanOtorisasi::STATUS_DISETUJUI)
            ->whereNull('digunakan_pada')
            ->update(['status' => PermintaanOtorisasi::STATUS_KEDALUWARSA, 'sedang_dihold' => false]);
    }

    private function buatTiketPos(int $idKasir, array $evaluasi, string $alasan, bool $dihold): PermintaanOtorisasi
    {
        $idReservasi = $evaluasi['reservasi']?->id_reservasi;

        return PermintaanOtorisasi::create(array_merge([
            'id_kasir' => $idKasir,
            'id_reservasi' => $idReservasi,
            'konteks' => PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI,
            'jenis_aksi' => PermintaanOtorisasi::AKSI_OVERRIDE_POS,
            'tipe_target' => $idReservasi ? PermintaanOtorisasi::TARGET_RESERVASI : PermintaanOtorisasi::TARGET_WALK_IN,
            'id_target' => $idReservasi,
            'label_target' => $evaluasi['label_target'],
        ], $this->atributSnapshotPos($evaluasi, $alasan, $dihold)));
    }

    /**
     * @return array<string, mixed>
     */
    private function atributSnapshotPos(array $evaluasi, string $alasan, bool $dihold): array
    {
        return [
            'alasan_kasir' => $alasan,
            'total_awal' => $evaluasi['total_awal'],
            'total_akhir' => $evaluasi['total_akhir'],
            'snapshot_awal' => $evaluasi['snapshot_awal'],
            'snapshot_keranjang' => $evaluasi['snapshot_usulan'],
            'hash_keranjang' => $evaluasi['hash_usulan'],
            'sedang_dihold' => $dihold,
        ];
    }

    private function validasiKredensialManajer(int $idKasir, string $ip, string $username, string $password): Karyawan
    {
        $kunci = $this->kunciRateLimiter($idKasir, $ip);
        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_GAGAL_DIRECT_OVERRIDE)) {
            $detik = RateLimiter::availableIn($kunci);
            throw new DomainException("Terlalu banyak percobaan gagal. Input kredensial Manajer dikunci selama {$detik} detik. Silakan ajukan lewat Dashboard Manajer.");
        }

        $akun = Karyawan::withTrashed()->where('username', trim($username))->first();
        if (! $akun || ! Hash::check($password, $akun->password)) {
            RateLimiter::hit($kunci, self::DURASI_KUNCI_DETIK);
            $sisa = max(0, self::MAKS_GAGAL_DIRECT_OVERRIDE - RateLimiter::attempts($kunci));
            if ($sisa === 0) {
                $detik = RateLimiter::availableIn($kunci);
                throw new DomainException("Batas percobaan gagal terlampaui. Input kredensial Manajer dikunci selama {$detik} detik. Silakan ajukan lewat Dashboard Manajer.");
            }
            throw new DomainException("Username atau password Manajer tidak sesuai. Sisa percobaan: {$sisa} kali.");
        }

        if ($akun->role_jabatan !== Karyawan::JABATAN_MANAJER) {
            RateLimiter::hit($kunci, self::DURASI_KUNCI_DETIK);
            throw new DomainException('Akun ini tidak memiliki wewenang Manajer.');
        }

        if (! $akun->aktif || $akun->trashed()) {
            RateLimiter::hit($kunci, self::DURASI_KUNCI_DETIK);
            throw new DomainException('Akun Manajer tersebut sedang tidak aktif.');
        }

        RateLimiter::clear($kunci);

        return $akun;
    }

    private function pastikanMasihMenungguDiDashboard(PermintaanOtorisasi $tiket, string $aksi): void
    {
        if ($tiket->status === PermintaanOtorisasi::STATUS_MENUNGGU) {
            return;
        }
        if ($tiket->metode_persetujuan === PermintaanOtorisasi::METODE_KREDENSIAL_LANGSUNG) {
            throw new DomainException("Permintaan #{$tiket->kode_referensi} sudah diselesaikan melalui Direct Override di meja kasir dan tidak dapat {$aksi} lagi.");
        }
        if ($tiket->status === PermintaanOtorisasi::STATUS_DIBATALKAN) {
            throw new DomainException("Permintaan #{$tiket->kode_referensi} telah ditarik/dibatalkan oleh Kasir karena pelanggan membatalkan transaksi.");
        }

        throw new DomainException("Permintaan #{$tiket->kode_referensi} sudah berstatus '{$tiket->status}' dan tidak dapat {$aksi} lagi.");
    }

    private function ambilTiketPendingTerkait(int $idKasir, array $data): ?PermintaanOtorisasi
    {
        if (! empty($data['id_otorisasi'])) {
            return PermintaanOtorisasi::where('id_otorisasi', (int) $data['id_otorisasi'])->where('id_kasir', $idKasir)->where('status', PermintaanOtorisasi::STATUS_MENUNGGU)->lockForUpdate()->first();
        }
        if (! empty($data['id_reservasi'])) {
            return PermintaanOtorisasi::where('id_reservasi', (int) $data['id_reservasi'])->where('id_kasir', $idKasir)->where('konteks', PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI)->where('status', PermintaanOtorisasi::STATUS_MENUNGGU)->lockForUpdate()->first();
        }

        return null;
    }

    private function tutupTiketLamaYangMenggantung(int $idKasir, array $data): void
    {
        $query = PermintaanOtorisasi::where('id_kasir', $idKasir)
            ->where('konteks', PermintaanOtorisasi::KONTEKS_POS_TRANSAKSI)
            ->whereIn('status', [PermintaanOtorisasi::STATUS_MENUNGGU, PermintaanOtorisasi::STATUS_DITOLAK]);

        if (! empty($data['id_otorisasi'])) {
            $query->where('id_otorisasi', (int) $data['id_otorisasi']);
        } elseif (! empty($data['id_reservasi'])) {
            $query->where('id_reservasi', (int) $data['id_reservasi']);
        } else {
            return;
        }

        foreach ($query->lockForUpdate()->get() as $lama) {
            $lama->batalkanOlehKasir();
        }
    }

    private function kunciRateLimiter(int $idKasir, string $ip): string
    {
        return "direct-override:{$idKasir}:{$ip}";
    }
}
