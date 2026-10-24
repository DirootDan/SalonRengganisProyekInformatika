<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Transaksi extends Model
{
    protected $table = 'transaksi';
    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'id_reservasi',
        'id_karyawan',
        'kode_promo',
        'diskon',
        'total_bayar',
        'nominal_uang',
        'kembalian',
        'metode_bayar',
        'komisi_stylist',
    ];

    public function reservasi(): BelongsTo
    {
        return $this->belongsTo(Reservasi::class, 'id_reservasi', 'id_reservasi');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id_karyawan')->withTrashed();
    }

    public function produk(): BelongsToMany
    {
        return $this->belongsToMany(
            Produk::class,
            'detail_transaksi',
            'id_transaksi',
            'id_produk'
        )->withPivot(['jumlah', 'harga_saat_ini'])->withTrashed();
    }
}
