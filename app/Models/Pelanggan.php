<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pelanggan extends Authenticatable
{
    use Notifiable;

    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'nama_cust',
        'no_telp',
        'email',
        'password',
        'jumlah_pelanggaran',
        'diblokir_sampai',
        'alasan_blokir',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'           => 'hashed',
            'jumlah_pelanggaran' => 'integer',
            'diblokir_sampai'    => 'datetime',
        ];
    }

    public function reservasi(): HasMany
    {
        return $this->hasMany(Reservasi::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function riwayatPelanggaran(): HasMany
    {
        return $this->hasMany(RiwayatPelanggaran::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(NotifikasiPelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function sedangDiblokir(): bool
    {
        return $this->diblokir_sampai !== null && $this->diblokir_sampai->isFuture();
    }

    public function sisaHariBlokir(): int
    {
        if (! $this->sedangDiblokir()) {
            return 0;
        }

        return (int) max(1, ceil(now()->floatDiffInDays($this->diblokir_sampai, false)));
    }
}
