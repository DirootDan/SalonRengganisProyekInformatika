<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanKebijakan extends Model
{
    protected $table = 'pengaturan_kebijakan';

    protected $fillable = [
        'batas_pelanggaran',
        'rentang_hari_pelanggaran',
        'lama_blokir_hari',
        'lama_blokir_kedua_hari',
        'blokir_ketiga_permanen',
        'toleransi_bayar_hari',
        'batas_batal_jam',
        'kebijakan_aktif',
    ];

    protected function casts(): array
    {
        return [
            'batas_pelanggaran'        => 'integer',
            'rentang_hari_pelanggaran' => 'integer',
            'lama_blokir_hari'         => 'integer',
            'lama_blokir_kedua_hari'   => 'integer',
            'blokir_ketiga_permanen'   => 'boolean',
            'toleransi_bayar_hari'     => 'integer',
            'batas_batal_jam'          => 'integer',
            'kebijakan_aktif'          => 'boolean',
        ];
    }

    public static function aktif(): self
    {
        return self::first() ?? self::create([
            'batas_pelanggaran'        => 5,
            'rentang_hari_pelanggaran' => 90,
            'lama_blokir_hari'         => 7,
            'lama_blokir_kedua_hari'   => 30,
            'blokir_ketiga_permanen'   => true,
            'toleransi_bayar_hari'     => 1,
            'batas_batal_jam'          => 3,
            'kebijakan_aktif'          => true,
        ]);
    }
}
