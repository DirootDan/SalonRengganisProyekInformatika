<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotifikasiPelanggan extends Model
{
    protected $table = 'notifikasi_pelanggan';
    protected $primaryKey = 'id_notifikasi';

    protected $fillable = [
        'id_pelanggan',
        'id_reservasi',
        'judul',
        'pesan',
        'tipe',
        'dibaca',
    ];

    protected function casts(): array
    {
        return [
            'dibaca' => 'boolean',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public static function kirim(int $idPelanggan, string $judul, string $pesan, string $tipe = 'info', ?int $idReservasi = null): self
    {
        return self::create([
            'id_pelanggan' => $idPelanggan,
            'id_reservasi' => $idReservasi,
            'judul'        => $judul,
            'pesan'        => $pesan,
            'tipe'         => $tipe,
            'dibaca'       => false,
        ]);
    }
}
