<?php

namespace App\Http\Requests\Kasir;

use App\Http\Requests\Kasir\Concerns\AturanKeranjangPos;
use Illuminate\Foundation\Http\FormRequest;

class ProsesBayarRequest extends FormRequest
{
    use AturanKeranjangPos;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->aturanItemKeranjangPos(), [
            'metode_bayar' => ['required', 'string', 'max:80'],
            'nominal_uang' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'alasan_perubahan' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return array_merge($this->pesanAturanKeranjangPos(), [
            'metode_bayar.required' => 'Metode pembayaran wajib dipilih.',
            'nominal_uang.required' => 'Nominal uang yang diterima wajib diisi.',
            'nominal_uang.min' => 'Nominal uang yang diterima tidak boleh negatif.',
            'nominal_uang.max' => 'Nominal uang melampaui batas maksimal transaksi.',
        ]);
    }
}
