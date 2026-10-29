<?php

namespace App\Services;

use App\Models\Promo;
use DomainException;

class KalkulatorHargaService
{
    /**
     * Memvalidasi kode promo di sisi server dan menghitung potongan diskon beserta metadata snapshot-nya.
     *
     * @return array{
     *     id_promo: int|null,
     *     kode_promo: string|null,
     *     tipe_diskon_promo: string|null,
     *     nilai_diskon_promo: float,
     *     nominal_diskon: float
     * }
     */
    public function validasiDanHitungPromo(?string $kodeInput, float $subtotal): array
    {
        $kosong = [
            'id_promo' => null,
            'kode_promo' => null,
            'tipe_diskon_promo' => null,
            'nilai_diskon_promo' => 0.0,
            'nominal_diskon' => 0.0,
        ];

        $kodeBersih = strtoupper(trim((string) $kodeInput));
        if ($kodeBersih === '') {
            return $kosong;
        }

        $promo = Promo::withTrashed()->where('kode_promo', $kodeBersih)->first();

        if (! $promo || $promo->trashed() || ! $promo->aktif) {
            throw new DomainException("Kode promo '{$kodeBersih}' tidak ditemukan atau sedang tidak aktif.");
        }

        $hariIni = now()->startOfDay();
        if ($promo->berlaku_mulai && $hariIni->lt($promo->berlaku_mulai->startOfDay())) {
            throw new DomainException("Kode promo '{$promo->kode_promo}' baru berlaku mulai tanggal ".$promo->berlaku_mulai->format('d/m/Y').'.');
        }

        if (! $promo->berlaku_sampai || $hariIni->gt($promo->berlaku_sampai->endOfDay())) {
            throw new DomainException("Kode promo '{$promo->kode_promo}' sudah kedaluwarsa pada tanggal ".($promo->berlaku_sampai?->format('d/m/Y') ?? '-').'.');
        }

        if ($promo->tipe_diskon === Promo::TIPE_PERSENTASE && (float) $promo->nilai_diskon > Promo::MAKS_PERSEN_DISKON) {
            throw new DomainException("Nilai potongan persentase pada promo '{$promo->kode_promo}' melebihi 100%.");
        }

        if ($subtotal < (float) $promo->min_transaksi) {
            throw new DomainException(
                "Kode promo '{$promo->kode_promo}' memerlukan minimum transaksi Rp ".number_format((float) $promo->min_transaksi, 0, ',', '.').' (subtotal saat ini Rp '.number_format($subtotal, 0, ',', '.').').'
            );
        }

        $nominalDiskon = $promo->hitungPotongan($subtotal);

        return [
            'id_promo' => (int) $promo->id_promo,
            'kode_promo' => $promo->kode_promo,
            'tipe_diskon_promo' => $promo->tipe_diskon,
            'nilai_diskon_promo' => (float) $promo->nilai_diskon,
            'nominal_diskon' => $nominalDiskon,
        ];
    }

    /**
     * Menghitung total tagihan akhir yang dijamin tidak pernah bernilai negatif.
     */
    public function hitungTotalAkhir(float $subtotalLayanan, float $subtotalProduk, float $nominalDiskon): float
    {
        $subtotal = max(0.0, $subtotalLayanan) + max(0.0, $subtotalProduk);

        return max(0.0, round($subtotal - max(0.0, $nominalDiskon), 2));
    }
}
