<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanKebijakan extends Model
{
    protected $table = 'pengaturan_kebijakan';

    public const DEFAULT_BATAS_PELANGGARAN    = 5;
    public const DEFAULT_RENTANG_HARI         = 90;
    public const DEFAULT_BLOKIR_PERTAMA_HARI  = 7;
    public const DEFAULT_BLOKIR_KEDUA_HARI    = 30;
    public const DEFAULT_BLOKIR_KETIGA_TETAP  = true;
    public const DEFAULT_TOLERANSI_BAYAR_HARI = 1;
    public const DEFAULT_BATAS_BATAL_JAM      = 3;

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
            'batas_pelanggaran'        => self::DEFAULT_BATAS_PELANGGARAN,
            'rentang_hari_pelanggaran' => self::DEFAULT_RENTANG_HARI,
            'lama_blokir_hari'         => self::DEFAULT_BLOKIR_PERTAMA_HARI,
            'lama_blokir_kedua_hari'   => self::DEFAULT_BLOKIR_KEDUA_HARI,
            'blokir_ketiga_permanen'   => self::DEFAULT_BLOKIR_KETIGA_TETAP,
            'toleransi_bayar_hari'     => self::DEFAULT_TOLERANSI_BAYAR_HARI,
            'batas_batal_jam'          => self::DEFAULT_BATAS_BATAL_JAM,
            'kebijakan_aktif'          => true,
        ]);
    }
}
