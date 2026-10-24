<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservasi extends Model
{
    protected $table = 'reservasi';
    protected $primaryKey = 'id_reservasi';

    public const STATUS_MENUNGGU   = 'Menunggu';
    public const STATUS_DITERIMA   = 'Diterima';
    public const STATUS_DITOLAK    = 'Ditolak';
    public const STATUS_SELESAI    = 'Selesai';
    public const STATUS_DIBATALKAN = 'Dibatalkan';
    public const STATUS_HANGUS     = 'Hangus';

    /**
     * Peta mesin status yang sah (Anti-Bug #3).
     */
    public const TRANSISI_SAH = [
        self::STATUS_MENUNGGU   => [self::STATUS_DITERIMA, self::STATUS_DITOLAK, self::STATUS_DIBATALKAN],
        self::STATUS_DITERIMA   => [self::STATUS_SELESAI, self::STATUS_HANGUS, self::STATUS_DIBATALKAN],
        self::STATUS_DITOLAK    => [],
        self::STATUS_SELESAI    => [],
        self::STATUS_DIBATALKAN => [],
        self::STATUS_HANGUS     => [],
    ];

    public const JAM_OPERASIONAL = [
        '09:00', '10:00', '11:00', '12:00', '13:00',
        '14:00', '15:00', '16:00', '17:00', '18:00', '19:00',
    ];

    protected $fillable = [
        'id_pelanggan',
        'id_karyawan',
        'tgl_booking',
        'jam_booking',
        'status',
        'alasan_penolakan',
        'dibatalkan_oleh',
        'pelanggaran_dihitung',
    ];

    protected function casts(): array
    {
        return [
            'tgl_booking'          => 'date',
            'pelanggaran_dihitung' => 'boolean',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
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
        )->withPivot(['id_karyawan', 'harga_saat_ini', 'persen_komisi', 'nominal_komisi', 'status_layanan'])
         ->withTrashed();
    }

    public function statusTernormalisasi(): string
    {
        return match (strtolower($this->status)) {
            'pending', 'menunggu'   => self::STATUS_MENUNGGU,
            'diterima'              => self::STATUS_DITERIMA,
            'ditolak'               => self::STATUS_DITOLAK,
            'selesai'               => self::STATUS_SELESAI,
            'batal', 'dibatalkan'   => self::STATUS_DIBATALKAN,
            'hangus'                => self::STATUS_HANGUS,
            default                 => $this->status,
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

        $this->fill(array_merge(['status' => $statusTujuan], $atributTambahan));
        $this->save();
    }
}
