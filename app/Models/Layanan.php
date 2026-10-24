<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Layanan extends Model
{
    use SoftDeletes;

    protected $table = 'layanan';
    protected $primaryKey = 'id_layanan';

    public const JABATAN_PETUGAS = ['Stylist', 'Therapist'];

    public const KATEGORI_LIST = [
        'Perawatan Rambut'        => 'Stylist',
        'Perawatan Wajah & Tubuh' => 'Therapist',
    ];

    protected $fillable = [
        'nama_layanan',
        'kategori',
        'harga_layanan',
        'jabatan_petugas',
    ];

    public static function jabatanDariKategori(string $kategori): string
    {
        if (stripos($kategori, 'Wajah') !== false || stripos($kategori, 'Tubuh') !== false || stripos($kategori, 'Kuku') !== false || stripos($kategori, 'Terapi') !== false) {
            return 'Therapist';
        }

        return self::KATEGORI_LIST[$kategori] ?? 'Stylist';
    }
}
