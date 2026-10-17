<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Reservasi extends Model
{
    protected $table = 'reservasi';
    protected $primaryKey = 'id_reservasi';

    protected $fillable = [
        'id_pelanggan',
        'id_karyawan',
        'tgl_booking',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tgl_booking' => 'date',
        ];
    }

    public function layanan(): BelongsToMany
    {
        return $this->belongsToMany(
            Layanan::class,
            'detail_reservasi',
            'id_reservasi',
            'id_layanan'
        );
    }
}