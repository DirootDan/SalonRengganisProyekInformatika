<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class PengaturanStruk extends Model
{
    protected $table = 'pengaturan_struk';

    public const UKURAN_58MM = '58mm';
    public const UKURAN_76MM = '76mm';
    public const UKURAN_80MM = '80mm';

    public const DAFTAR_UKURAN_KERTAS = [
        self::UKURAN_58MM => '58mm — Thermal Kecil / Portable',
        self::UKURAN_76MM => '76mm — Kasir Menengah / Standar',
        self::UKURAN_80MM => '80mm — Thermal Lebar (Meja Kasir)',
    ];

    protected $fillable = [
        'nama_salon',
        'slogan',
        'alamat',
        'telepon',
        'instagram',
        'teks_header',
        'teks_footer',
        'ukuran_kertas',
        'tampil_logo',
        'tampil_petugas',
        'tampil_kasir',
        'tampil_sosmed',
    ];

    protected function casts(): array
    {
        return [
            'tampil_logo'    => 'boolean',
            'tampil_petugas' => 'boolean',
            'tampil_kasir'   => 'boolean',
            'tampil_sosmed'  => 'boolean',
        ];
    }

    public function getUkuranKertasAttribute(?string $value): string
    {
        return $value && array_key_exists($value, self::DAFTAR_UKURAN_KERTAS)
            ? $value
            : self::UKURAN_80MM;
    }

    public static function aktif(): self
    {
        $existing = self::first();
        if ($existing) {
            return $existing;
        }

        $atributDefault = [
            'nama_salon'     => 'SALON RENGGANIS',
            'slogan'         => 'Pusat Perawatan Kecantikan & Rambut',
            'alamat'         => 'Jl. Paingan No. 88, Maguwoharjo, Sleman',
            'telepon'        => '0812-3456-7890',
            'instagram'      => '@salonrengganis.id',
            'teks_header'    => 'Selamat Datang di Salon Rengganis',
            'teks_footer'    => 'Terima kasih atas kunjungan Anda! Simpan struk ini sebagai bukti pembayaran yang sah.',
            'tampil_logo'    => true,
            'tampil_petugas' => true,
            'tampil_kasir'   => true,
            'tampil_sosmed'  => true,
        ];

        if (Schema::hasColumn('pengaturan_struk', 'ukuran_kertas')) {
            $atributDefault['ukuran_kertas'] = self::UKURAN_80MM;
        }

        return self::create($atributDefault);
    }
}
