<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promo extends Model
{
    use SoftDeletes;

    protected $table = 'promo';
    protected $primaryKey = 'id_promo';

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
            'berlaku_mulai'  => 'date',
            'berlaku_sampai' => 'date',
            'aktif'          => 'boolean',
        ];
    }

    public function masihBerlaku(): bool
    {
        if (! $this->aktif) {
            return false;
        }

        $hariIni = now()->startOfDay();
        if ($this->berlaku_mulai && $hariIni->lt($this->berlaku_mulai->startOfDay())) {
            return false;
        }

        return $hariIni->lte($this->berlaku_sampai->endOfDay());
    }

    public function hitungPotongan(float $subtotal): float
    {
        if (! $this->masihBerlaku() || $subtotal < (float) $this->min_transaksi) {
            return 0;
        }

        if ($this->tipe_diskon === 'Persentase') {
            return round($subtotal * ((float) $this->nilai_diskon / 100), 2);
        }

        return min($subtotal, (float) $this->nilai_diskon);
    }
}
