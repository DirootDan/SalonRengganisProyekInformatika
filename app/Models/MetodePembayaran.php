<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodePembayaran extends Model
{
    protected $table = 'metode_pembayaran';

    protected $primaryKey = 'id_metode';

    public const KATEGORI_TUNAI = 'Tunai';

    public const KATEGORI_QRIS = 'Dompet Digital / QRIS';

    public const KATEGORI_QR = 'Dompet Digital / QR';

    public const KATEGORI_TRANSFER = 'Transfer Bank';

    public const KATEGORI_EDC = 'Mesin EDC';

    public const IKON_DEFAULT = '🏦';

    public const IKON_KATEGORI = [
        self::KATEGORI_TUNAI => '💵',
        self::KATEGORI_QRIS => '📱',
        self::KATEGORI_QR => '📱',
        self::KATEGORI_EDC => '💳',
        self::KATEGORI_TRANSFER => '🏦',
    ];

    protected $fillable = [
        'nama_metode',
        'kategori',
        'nomor_rekening',
        'atas_nama',
        'ikon',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public static function ikonUntukKategori(string $kategori): string
    {
        return self::IKON_KATEGORI[$kategori] ?? self::IKON_DEFAULT;
    }
}
