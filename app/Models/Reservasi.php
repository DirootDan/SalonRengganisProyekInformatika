<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Reservasi extends Model
{
    protected $table = 'reservasi';

    protected $primaryKey = 'id_reservasi';

    public const STATUS_MENUNGGU = 'Menunggu';

    public const STATUS_DITERIMA = 'Diterima';

    public const STATUS_DITOLAK = 'Ditolak';

    public const STATUS_SELESAI = 'Selesai';

    public const STATUS_DIBATALKAN = 'Dibatalkan';

    public const STATUS_HANGUS = 'Hangus';

    public const STATUS_AKTIF_SLOT = [
        self::STATUS_MENUNGGU,
        self::STATUS_DITERIMA,
        'pending',
    ];

    /**
     * Peta mesin status yang sah.
     */
    public const TRANSISI_SAH = [
        self::STATUS_MENUNGGU => [self::STATUS_DITERIMA, self::STATUS_DITOLAK, self::STATUS_DIBATALKAN],
        self::STATUS_DITERIMA => [self::STATUS_SELESAI, self::STATUS_HANGUS, self::STATUS_DIBATALKAN],
        self::STATUS_DITOLAK => [],
        self::STATUS_SELESAI => [],
        self::STATUS_DIBATALKAN => [],
        self::STATUS_HANGUS => [],
    ];

    public const JAM_BUKA_SALON = '09:00';

    public const JAM_TUTUP_SALON = '21:00';

    public const JAM_OPERASIONAL = [
        '09:00', '10:00', '11:00', '12:00', '13:00',
        '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
    ];

    protected $fillable = [
        'id_pelanggan',
        'id_karyawan',
        'tgl_booking',
        'jam_booking',
        'jam_selesai',
        'total_durasi_menit',
        'status',
        'alasan_penolakan',
        'dibatalkan_oleh',
        'pelanggaran_dihitung',
        'id_promo',
        'kode_promo',
        'tipe_diskon_promo',
        'nilai_diskon_promo',
        'nominal_diskon',
    ];

    protected function casts(): array
    {
        return [
            'tgl_booking' => 'date',
            'total_durasi_menit' => 'integer',
            'pelanggaran_dihitung' => 'boolean',
            'nilai_diskon_promo' => 'decimal:2',
            'nominal_diskon' => 'decimal:2',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function promo(): BelongsTo
    {
        return $this->belongsTo(Promo::class, 'id_promo', 'id_promo')->withTrashed();
    }

    public function transaksi(): HasOne
    {
        return $this->hasOne(Transaksi::class, 'id_reservasi', 'id_reservasi');
    }

    public function layanan(): BelongsToMany
    {
        return $this->belongsToMany(
            Layanan::class,
            'detail_reservasi',
            'id_reservasi',
            'id_layanan'
        )->withPivot([
            'id_karyawan',
            'jam_mulai',
            'jam_selesai',
            'jam_selesai_blokir',
            'durasi_saat_ini',
            'jeda_saat_ini',
            'harga_saat_ini',
            'persen_komisi',
            'nominal_komisi',
            'status_layanan',
        ])->withTrashed();
    }

    public function produkAddon(): BelongsToMany
    {
        return $this->belongsToMany(
            Produk::class,
            'detail_reservasi_produk',
            'id_reservasi',
            'id_produk'
        )->withPivot(['jumlah', 'harga_saat_ini', 'stok_dipotong'])
            ->withTimestamps()
            ->withTrashed();
    }

    public function subtotalLayanan(): float
    {
        return (float) $this->layanan->sum(
            fn ($l) => (float) ($l->pivot->harga_saat_ini > 0 ? $l->pivot->harga_saat_ini : $l->harga_layanan)
        );
    }

    public function subtotalProdukAddon(): float
    {
        return (float) $this->produkAddon->sum(
            fn ($p) => (float) ($p->pivot->harga_saat_ini > 0 ? $p->pivot->harga_saat_ini : $p->harga_produk) * (int) $p->pivot->jumlah
        );
    }

    public function estimasiTotalAkhir(): float
    {
        $subtotal = $this->subtotalLayanan() + $this->subtotalProdukAddon();

        return max(0.0, $subtotal - (float) $this->nominal_diskon);
    }

    public function statusTernormalisasi(): string
    {
        return match (strtolower($this->status)) {
            'pending', 'menunggu' => self::STATUS_MENUNGGU,
            'diterima' => self::STATUS_DITERIMA,
            'ditolak' => self::STATUS_DITOLAK,
            'selesai' => self::STATUS_SELESAI,
            'batal', 'dibatalkan' => self::STATUS_DIBATALKAN,
            'hangus' => self::STATUS_HANGUS,
            default => $this->status,
        };
    }

    public function bolehTransisiKe(string $statusTujuan): bool
    {
        $asal = $this->statusTernormalisasi();
        $daftar = self::TRANSISI_SAH[$asal] ?? [];

        return in_array($statusTujuan, $daftar, true);
    }

    public function ubahStatus(string $statusTujuan, array $atributTambahan = []): void
    {
        if (! $this->bolehTransisiKe($statusTujuan)) {
            throw new DomainException("Perubahan status reservasi dari '{$this->statusTernormalisasi()}' ke '{$statusTujuan}' tidak diizinkan.");
        }

        // Jika reservasi dibatalkan, ditolak, atau hangus, kembalikan stok produk add-on yang sempat dicadangkan
        if (in_array($statusTujuan, [self::STATUS_DITOLAK, self::STATUS_DIBATALKAN, self::STATUS_HANGUS], true)) {
            $this->kembalikanStokProdukAddon();
        }

        $this->fill(array_merge(['status' => $statusTujuan], $atributTambahan));
        $this->save();
    }

    /**
     * Mengembalikan stok produk add-on yang sudah dipotong apabila reservasi batal/ditolak/hangus.
     */
    public function kembalikanStokProdukAddon(): void
    {
        $barisProduk = DB::table('detail_reservasi_produk')
            ->where('id_reservasi', $this->id_reservasi)
            ->where('stok_dipotong', true)
            ->orderBy('id_produk')
            ->lockForUpdate()
            ->get();

        foreach ($barisProduk as $baris) {
            Produk::withTrashed()
                ->where('id_produk', $baris->id_produk)
                ->lockForUpdate()
                ->increment('stok_produk', (int) $baris->jumlah);

            DB::table('detail_reservasi_produk')
                ->where('id_reservasi', $this->id_reservasi)
                ->where('id_produk', $baris->id_produk)
                ->update(['stok_dipotong' => false]);
        }
    }
}
