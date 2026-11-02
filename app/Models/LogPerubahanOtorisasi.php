<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPerubahanOtorisasi extends Model
{
    protected $table = 'log_perubahan_otorisasi';

    protected $primaryKey = 'id_log';

    public const MEKANISME_MANDIRI_BAWAH_AMBANG = 'mandiri_bawah_ambang';

    public const MEKANISME_APPROVAL_DASHBOARD = 'approval_dashboard';

    public const MEKANISME_KREDENSIAL_LANGSUNG = 'kredensial_langsung';

    public const JENIS_OVERRIDE_HARGA_POS = 'override_harga_pos';

    public const JENIS_UBAH_LAYANAN_POS = 'ubah_layanan_pos';

    public const JENIS_GABUNGAN_POS = 'gabungan_pos';

    public const JENIS_TAMBAH_KATALOG = 'tambah_katalog';

    public const JENIS_UBAH_KATALOG = 'ubah_katalog';

    public const JENIS_HAPUS_KATALOG = 'hapus_katalog';

    public const BATAS_LOG_TERBARU = 50;

    protected $fillable = [
        'id_otorisasi',
        'id_kasir',
        'id_manajer',
        'id_reservasi',
        'id_transaksi',
        'konteks',
        'jenis_perubahan',
        'mekanisme_otorisasi',
        'tipe_target',
        'id_target',
        'label_target',
        'alasan',
        'nilai_awal',
        'nilai_akhir',
        'data_sebelum',
        'data_sesudah',
    ];

    protected function casts(): array
    {
        return [
            'id_target' => 'integer',
            'nilai_awal' => 'decimal:2',
            'nilai_akhir' => 'decimal:2',
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new DomainException('Catatan log audit perubahan bersifat permanen dan tidak dapat diubah.');
        });

        static::deleting(function () {
            throw new DomainException('Catatan log audit perubahan bersifat permanen dan tidak dapat dihapus.');
        });
    }

    public function otorisasi(): BelongsTo
    {
        return $this->belongsTo(PermintaanOtorisasi::class, 'id_otorisasi', 'id_otorisasi');
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
}
