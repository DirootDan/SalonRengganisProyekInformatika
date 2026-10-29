<?php

namespace App\Http\Requests\Manajer;

use Illuminate\Foundation\Http\FormRequest;

class ProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_produk' => ['required', 'string', 'max:100'],
            'stok_produk' => ['required', 'integer', 'min:0'],
            'harga_produk' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'nama_produk.max' => 'Nama produk maksimal 100 karakter.',
            'stok_produk.required' => 'Jumlah stok produk wajib diisi.',
            'stok_produk.integer' => 'Stok produk harus berupa bilangan bulat.',
            'stok_produk.min' => 'Stok produk tidak boleh bernilai negatif.',
            'harga_produk.required' => 'Harga jual produk wajib diisi.',
            'harga_produk.numeric' => 'Harga produk harus berupa angka.',
            'harga_produk.min' => 'Harga produk tidak boleh bernilai negatif.',
        ];
    }
}
