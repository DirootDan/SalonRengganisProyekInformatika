<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @method static Builder menungguPersetujuan(?string $konteks = null)
 * @method static Builder sedangDihold(?int $idKasir = null)
 * @method static Builder izinKatalogSiapPakai(int $idKasir, string $konteks, string $jenisAksi, ?int $idTarget = null)
 */
class PermintaanOtorisasi extends Model
{
    protected $table = 'permintaan_otorisasi';

    protected $primaryKey = 'id_otorisasi';

    public const BATAS_NOMINAL_WAJIB_APPROVAL = 150000.0;

    public const DURASI_IZIN_KATALOG_MENIT = 60;

    public const KONTEKS_POS_TRANSAKSI = 'pos_transaksi';

    public const KONTEKS_KATALOG_LAYANAN = 'katalog_layanan';

    public const KONTEKS_KATALOG_PRODUK = 'katalog_produk';

    public const DAFTAR_KONTEKS = [self::KONTEKS_POS_TRANSAKSI, self::KONTEKS_KATALOG_LAYANAN, self::KONTEKS_KATALOG_PRODUK];

    public const AKSI_OVERRIDE_POS = 'override_pos';

    public const AKSI_TAMBAH = 'tambah';

    public const AKSI_UBAH = 'ubah';

    public const AKSI_HAPUS = 'hapus';

    public const DAFTAR_AKSI_KATALOG = [self::AKSI_TAMBAH, self::AKSI_UBAH, self::AKSI_HAPUS];

    public const TARGET_RESERVASI = 'reservasi';

    public const TARGET_WALK_IN = 'walk_in';

    public const TARGET_LAYANAN = 'layanan';

    public const TARGET_PRODUK = 'produk';

    public const STATUS_MENUNGGU = 'Menunggu';

    public const STATUS_DISETUJUI = 'Disetujui';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_DIGUNAKAN = 'Digunakan';

    public const STATUS_DIBATALKAN = 'Dibatalkan';

    public const STATUS_KEDALUWARSA = 'Kedaluwarsa';

    public const METODE_DASHBOARD_MANAJER = 'dashboard_manajer';

    public const METODE_KREDENSIAL_LANGSUNG = 'kredensial_langsung';

    public const TRANSISI_SAH = [
        self::STATUS_MENUNGGU => [self::STATUS_DISETUJUI, self::STATUS_DITOLAK, self::STATUS_DIBATALKAN],
        self::STATUS_DISETUJUI => [self::STATUS_DIGUNAKAN, self::STATUS_DIBATALKAN, self::STATUS_KEDALUWARSA],
        self::STATUS_DITOLAK => [self::STATUS_DIGUNAKAN, self::STATUS_DIBATALKAN],
        self::STATUS_DIGUNAKAN => [],
        self::STATUS_DIBATALKAN => [],
        self::STATUS_KEDALUWARSA => [],
    ];

    protected $fillable = [
        'kode_referensi',
        'id_kasir',
        'id_reservasi',
        'konteks',
        'jenis_aksi',
        'tipe_target',
        'id_target',
        'label_target',
        'alasan_kasir',
        'total_awal',
        'total_akhir',
        'snapshot_awal',
        'snapshot_keranjang',
        'hash_keranjang',
        'sedang_dihold',
    ];

    protected function casts(): array
    {
        return [
            'id_target' => 'integer',
            'total_awal' => 'decimal:2',
            'total_akhir' => 'decimal:2',
            'snapshot_awal' => 'array',
            'snapshot_keranjang' => 'array',
            'sedang_dihold' => 'boolean',
            'diputuskan_pada' => 'datetime',
            'kedaluwarsa_pada' => 'datetime',
            'digunakan_pada' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $permintaan) {
            $permintaan->kode_referensi = $permintaan->kode_referensi ?: self::buatKodeReferensi();
            $permintaan->status = $permintaan->status ?: self::STATUS_MENUNGGU;
        });
    }

    public static function buatKodeReferensi(): string
    {
        return 'OTR-'.now()->format('Ymd').'-'.Str::upper(Str::random(5));
    }

    public static function perluPersetujuanManajer(float $totalAwal, float $totalAkhir): bool
    {
        return max($totalAwal, $totalAkhir) >= self::BATAS_NOMINAL_WAJIB_APPROVAL;
    }

    /**
     * Menghitung hash SHA-256 deterministik atas komponen inti item & harga pada snapshot keranjang.
     *
     * @param  array<string, mixed>  $snapshot
     */
    public static function hitungHashKeranjang(array $snapshot): string
    {
        if (array_key_exists('layanan', $snapshot) || array_key_exists('produk', $snapshot)) {
            $intiLayanan = collect($snapshot['layanan'] ?? [])
                ->mapWithKeys(fn (array $i) => [(int) $i['id_layanan'] => round((float) $i['harga_aktif'], 0)])
                ->all();
            $intiProduk = collect($snapshot['produk'] ?? [])
                ->mapWithKeys(fn (array $i) => [(int) $i['id_produk'] => ['qty' => (int) $i['qty'], 'harga' => round((float) $i['harga_aktif'], 0)]])
                ->all();
            $snapshot = ['id_reservasi' => $snapshot['id_reservasi'] ?? null, 'layanan' => $intiLayanan, 'produk' => $intiProduk];
        }

        return hash('sha256', json_encode(self::urutkanKunciRekursif($snapshot), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<int|string, mixed>  $data
     * @return array<int|string, mixed>
     */
    private static function urutkanKunciRekursif(array $data): array
    {
        ksort($data);
        foreach ($data as $kunci => $nilai) {
            if (is_array($nilai)) {
                $data[$kunci] = self::urutkanKunciRekursif($nilai);
            }
        }

        return $data;
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_kasir', 'id_karyawan')->withTrashed();
    }

    public function manajer(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_manajer', 'id_karyawan')->withTrashed();
    }

    public function reservasi(): BelongsTo
    {
        return $this->belongsTo(Reservasi::class, 'id_reservasi', 'id_reservasi');
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi', 'id_transaksi');
    }

    public function logPerubahan(): HasMany
    {
        return $this->hasMany(LogPerubahanOtorisasi::class, 'id_otorisasi', 'id_otorisasi');
    }

    public function scopeMenungguPersetujuan(Builder $query, ?string $konteks = null): Builder
    {
        if ($konteks !== null) {
            $query->where('konteks', $konteks);
        }

        return $query->where('status', self::STATUS_MENUNGGU);
    }

    public function scopeSedangDihold(Builder $query, ?int $idKasir = null): Builder
    {
        if ($idKasir !== null) {
            $query->where('id_kasir', $idKasir);
        }

        return $query->where('sedang_dihold', true)
            ->whereIn('status', [self::STATUS_MENUNGGU, self::STATUS_DISETUJUI, self::STATUS_DITOLAK]);
    }

    public function scopeIzinKatalogSiapPakai(Builder $query, int $idKasir, string $konteks, string $jenisAksi, ?int $idTarget = null): Builder
    {
        $query->where('id_kasir', $idKasir)
            ->where('konteks', $konteks)
            ->where('jenis_aksi', $jenisAksi)
            ->where('status', self::STATUS_DISETUJUI)
            ->whereNull('digunakan_pada')
            ->where(fn (Builder $q) => $q->whereNull('kedaluwarsa_pada')->orWhere('kedaluwarsa_pada', '>', now()));

        return $idTarget === null ? $query->whereNull('id_target') : $query->where('id_target', $idTarget);
    }

    public function bolehTransisiKe(string $statusTujuan): bool
    {
        return in_array($statusTujuan, self::TRANSISI_SAH[$this->status] ?? [], true);
    }

    public function setujui(int $idManajer, string $metode, ?string $catatan = null): void
    {
        $this->pastikanBolehTransisi(self::STATUS_DISETUJUI);
        $adalahKatalog = in_array($this->konteks, [self::KONTEKS_KATALOG_LAYANAN, self::KONTEKS_KATALOG_PRODUK], true);

        $this->forceFill([
            'id_manajer' => $idManajer,
            'status' => self::STATUS_DISETUJUI,
            'metode_persetujuan' => $metode,
            'catatan_manajer' => $catatan,
            'diputuskan_pada' => now(),
            'kedaluwarsa_pada' => $adalahKatalog ? now()->addMinutes(self::DURASI_IZIN_KATALOG_MENIT) : null,
        ])->save();
    }

    /**
     * Menolak pengajuan dan mengembalikan keranjang Hold ke harga/layanan awal tanpa menghapus data Hold.
     */
    public function tolak(int $idManajer, string $catatanPenolakan): void
    {
        $this->pastikanBolehTransisi(self::STATUS_DITOLAK);
        $keranjangPulih = ! empty($this->snapshot_awal) ? $this->snapshot_awal : $this->snapshot_keranjang;

        $this->forceFill([
            'id_manajer' => $idManajer,
            'status' => self::STATUS_DITOLAK,
            'metode_persetujuan' => self::METODE_DASHBOARD_MANAJER,
            'catatan_manajer' => $catatanPenolakan,
            'diputuskan_pada' => now(),
            'snapshot_keranjang' => $keranjangPulih,
            'hash_keranjang' => is_array($keranjangPulih) ? self::hitungHashKeranjang($keranjangPulih) : null,
            'total_akhir' => $this->total_awal,
        ])->save();
    }

    public function tandaiDigunakan(?int $idTransaksi = null): void
    {
        $this->pastikanBolehTransisi(self::STATUS_DIGUNAKAN);

        $this->forceFill([
            'status' => self::STATUS_DIGUNAKAN,
            'id_transaksi' => $idTransaksi ?? $this->id_transaksi,
            'sedang_dihold' => false,
            'digunakan_pada' => now(),
        ])->save();
    }

    public function batalkanOlehKasir(): void
    {
        $this->pastikanBolehTransisi(self::STATUS_DIBATALKAN);
        $this->forceFill(['status' => self::STATUS_DIBATALKAN, 'sedang_dihold' => false])->save();
    }

    public function tandaiKedaluwarsa(): void
    {
        $this->pastikanBolehTransisi(self::STATUS_KEDALUWARSA);
        $this->forceFill(['status' => self::STATUS_KEDALUWARSA, 'sedang_dihold' => false])->save();
    }

    private function pastikanBolehTransisi(string $statusTujuan): void
    {
        if (! $this->bolehTransisiKe($statusTujuan)) {
            throw new DomainException(
                "Status permintaan otorisasi #{$this->kode_referensi} tidak dapat diubah dari '{$this->status}' ke '{$statusTujuan}'."
            );
        }
    }
}
