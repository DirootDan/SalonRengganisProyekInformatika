<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanStruk extends Model
{
    protected $table = 'pengaturan_struk';

    protected $fillable = [
        'nama_salon',
        'slogan',
        'alamat',
        'telepon',
        'instagram',
        'teks_header',
        'teks_footer',
        'tampil_logo',
        'tampil_petugas',
        'tampil_kasir',
        'tampil_sosmed',
    ];

    protected function casts(): array
    {
        return [
            'tampil_logo' => 'boolean',
            'tampil_petugas' => 'boolean',
            'tampil_kasir' => 'boolean',
            'tampil_sosmed' => 'boolean',
        ];
    }

    public static function aktif(): self
    {
        return self::first() ?? self::create([
            'nama_salon' => 'SALON RENGGANIS',
            'slogan' => 'Pusat Perawatan Kecantikan & Rambut',
            'alamat' => 'Jl. Paingan No. 88, Maguwoharjo, Sleman',
            'telepon' => '0812-3456-7890',
            'instagram' => '@salonrengganis.id',
            'teks_header' => 'Selamat Datang di Salon Rengganis',
            'teks_footer' => 'Terima kasih atas kunjungan Anda! Simpan struk ini sebagai bukti pembayaran yang sah.',
            'tampil_logo' => true,
            'tampil_petugas' => true,
            'tampil_kasir' => true,
            'tampil_sosmed' => true,
        ]);
    }
}
