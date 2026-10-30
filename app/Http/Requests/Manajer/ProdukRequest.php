<?php

namespace App\Http\Requests\Manajer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Mengambil ID produk saat ini jika sedang dalam mode edit/update
        $produk = $this->route('produk');
        $id = $produk instanceof \App\Models\Produk ? $produk->id_produk : $produk;

        return [
            'nama_produk' => [
                'required', 
                'string', 
                'max:100',
                // Mencegah duplikasi nama produk di database, abaikan jika sedang mengedit produk itu sendiri
                Rule::unique('produk', 'nama_produk')->ignore($id, 'id_produk'),
            ],
            'stok_produk' => ['required', 'integer', 'min:0'],
            'harga_produk' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'nama_produk.string' => 'Nama produk harus berupa teks.',
            'nama_produk.max' => 'Nama produk maksimal 100 karakter.',
            'nama_produk.unique' => 'Nama produk ini sudah terdaftar di sistem. Silakan gunakan nama lain atau lakukan edit/restok pada produk yang sudah ada.',
            'stok_produk.required' => 'Jumlah stok produk wajib diisi.',
            'stok_produk.integer' => 'Stok produk harus berupa bilangan bulat.',
            'stok_produk.min' => 'Stok produk tidak boleh bernilai negatif.',
            'harga_produk.required' => 'Harga jual produk wajib diisi.',
            'harga_produk.numeric' => 'Harga produk harus berupa angka.',
            'harga_produk.min' => 'Harga produk tidak boleh bernilai negatif.',
        ];
    }
}
