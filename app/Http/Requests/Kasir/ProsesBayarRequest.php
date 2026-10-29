<?php

namespace App\Http\Requests\Kasir;

use Illuminate\Foundation\Http\FormRequest;

class ProsesBayarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_reservasi' => ['nullable', 'integer', 'exists:reservasi,id_reservasi'],
            'metode_bayar' => ['required', 'string', 'max:80'],
            'nominal_uang' => ['required', 'numeric', 'min:0'],
            'kode_promo' => ['nullable', 'string', 'max:40'],
            'id_petugas' => ['nullable', 'integer'],
            'layanan_ids' => ['nullable', 'array'],
            'layanan_ids.*' => ['integer', 'exists:layanan,id_layanan'],
            'petugas_layanan' => ['nullable', 'array'],
            'petugas_layanan.*' => ['nullable', 'integer'],
            'produk_items' => ['nullable', 'array'],
            'produk_items.*' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'metode_bayar.required' => 'Metode pembayaran wajib dipilih.',
            'nominal_uang.required' => 'Nominal uang yang diterima wajib diisi.',
            'nominal_uang.min' => 'Nominal uang yang diterima tidak boleh negatif.',
        ];
    }
}
