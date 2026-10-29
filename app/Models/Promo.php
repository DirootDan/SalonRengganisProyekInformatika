<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @method static Builder sedangBerlaku()
 */
class Promo extends Model
{
    use SoftDeletes;

    protected $table = 'promo';

    protected $primaryKey = 'id_promo';

    public const TIPE_PERSENTASE = 'Persentase';

    public const TIPE_NOMINAL = 'Nominal';

    public const DAFTAR_TIPE = [self::TIPE_PERSENTASE, self::TIPE_NOMINAL];

    public const MAKS_PERSEN_DISKON = 100;

    protected $fillable = [
        'kode_promo',
        'nama_promo',
        'tipe_diskon',
        'nilai_diskon',
        'min_transaksi',
        'berlaku_mulai',
        'berlaku_sampai',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'nilai_diskon' => 'decimal:2',
            'min_transaksi' => 'decimal:2',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Scope query untuk mengambil daftar promo yang aktif dan berada dalam masa berlaku hari ini.
     */
    public function scopeSedangBerlaku(Builder $query): Builder
    {
        $hariIni = now()->toDateString();

        return $query->where('aktif', true)
            ->where(function (Builder $q) use ($hariIni) {
                $q->whereNull('berlaku_mulai')
                    ->orWhereDate('berlaku_mulai', '<=', $hariIni);
            })
            ->whereDate('berlaku_sampai', '>=', $hariIni);
    }

    public function masihBerlaku(): bool
    {
        if (! $this->aktif || $this->trashed()) {
            return false;
        }

        $hariIni = now()->startOfDay();
        if ($this->berlaku_mulai && $hariIni->lt($this->berlaku_mulai->startOfDay())) {
            return false;
        }

        return $this->berlaku_sampai !== null && $hariIni->lte($this->berlaku_sampai->endOfDay());
    }

    public function hitungPotongan(float $subtotal): float
    {
        if ($subtotal <= 0 || ! $this->masihBerlaku() || $subtotal < (float) $this->min_transaksi) {
            return 0.0;
        }

        if ($this->tipe_diskon === self::TIPE_PERSENTASE) {
            $persen = min((float) self::MAKS_PERSEN_DISKON, max(0.0, (float) $this->nilai_diskon));
            $potongan = round($subtotal * ($persen / self::MAKS_PERSEN_DISKON), 2);

            return min($subtotal, $potongan);
        }

        return min($subtotal, max(0.0, (float) $this->nilai_diskon));
    }

    public function jumlahReservasiAktif(): int
    {
        return DB::table('reservasi')
            ->where('id_promo', $this->id_promo)
            ->whereIn('status', Reservasi::STATUS_AKTIF_SLOT)
            ->count();
    }
}
