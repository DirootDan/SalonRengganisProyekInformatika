<?php

namespace App\Http\Requests\Kasir\Concerns;

trait AturanKeranjangPos
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function aturanItemKeranjangPos(): array
    {
        return [
            'id_reservasi' => ['nullable', 'integer', 'exists:reservasi,id_reservasi'],
            'id_otorisasi' => ['nullable', 'integer', 'exists:permintaan_otorisasi,id_otorisasi'],
            'keranjang_disinkronkan' => ['nullable', 'boolean'],
            'nama_pelanggan_walkin' => ['required_without:id_reservasi', 'nullable', 'string', 'max:100'],
            'kode_promo' => ['nullable', 'string', 'max:40'],
            'id_petugas' => ['nullable', 'integer'],
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan,id_layanan'],
            'petugas_layanan' => ['nullable', 'array'],
            'petugas_layanan.*' => ['nullable', 'integer'],
            'produk_items' => ['nullable', 'array'],
            'produk_items.*' => ['nullable', 'integer', 'min:0'],
            'harga_override_layanan' => ['nullable', 'array'],
            'harga_override_layanan.*' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'harga_override_produk' => ['nullable', 'array'],
            'harga_override_produk.*' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function pesanAturanKeranjangPos(): array
    {
        return [
            'nama_pelanggan_walkin.required_without' => 'Nama pelanggan walk-in wajib diisi untuk transaksi tanpa reservasi.',
            'nama_pelanggan_walkin.max' => 'Nama pelanggan walk-in maksimal 100 karakter.',
        ];
    }
}
