<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @method static Builder tersedia()
 */
class Produk extends Model
{
    use SoftDeletes;

    protected $table = 'produk';

    protected $primaryKey = 'id_produk';

    public const BATAS_STOK_MENIPIS = 10;

    protected $fillable = [
        'nama_produk',
        'stok_produk',
        'harga_produk',
    ];

    protected function casts(): array
    {
        return [
            'stok_produk' => 'integer',
            'harga_produk' => 'decimal:2',
        ];
    }

    public function reservasi(): BelongsToMany
    {
        return $this->belongsToMany(
            Reservasi::class,
            'detail_reservasi_produk',
            'id_produk',
            'id_reservasi'
        );
    }

    public function reservasiAktif(): BelongsToMany
    {
        return $this->reservasi()->whereIn('reservasi.status', Reservasi::STATUS_AKTIF_SLOT);
    }

    public function scopeTersedia(Builder $query): Builder
    {
        return $query->where('stok_produk', '>', 0);
    }

    public function apakahStokMenipis(): bool
    {
        return $this->stok_produk < self::BATAS_STOK_MENIPIS;
    }

    public function jumlahReservasiAktif(): int
    {
        if (array_key_exists('reservasi_aktif_count', $this->attributes)) {
            return (int) $this->attributes['reservasi_aktif_count'];
        }

        return DB::table('detail_reservasi_produk')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi_produk.id_reservasi')
            ->where('detail_reservasi_produk.id_produk', $this->id_produk)
            ->whereIn('reservasi.status', Reservasi::STATUS_AKTIF_SLOT)
            ->count();
    }

    public function pesanDampakHapusKasir(): string
    {
        $jmlAktif = $this->jumlahReservasiAktif();

        return $jmlAktif > 0
            ? 'Perhatian: Produk ini masih dipesan pada '.$jmlAktif.' reservasi aktif sehingga penghapusan akan ditolak server.'
            : 'Setelah dihapus, hak akses otomatis terkunci kembali.';
    }
}
