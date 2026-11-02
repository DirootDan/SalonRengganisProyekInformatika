<?php

namespace App\Http\Requests\Manajer;

use App\Models\Produk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProdukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nama_produk')) {
            $this->merge([
                'nama_produk' => Str::squish((string) $this->input('nama_produk')),
            ]);
        }
    }

    public function rules(): array
    {
        $produkParam = $this->route('produk') ?? $this->route('id');
        $idProduk = $produkParam instanceof Produk ? $produkParam->id_produk : $produkParam;

        return [
            'nama_produk' => [
                'required',
                'string',
                'max:100',
                Rule::unique('produk', 'nama_produk')
                    ->ignore($idProduk, 'id_produk')
                    ->withoutTrashed(),
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
