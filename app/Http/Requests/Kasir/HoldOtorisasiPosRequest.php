<?php

namespace App\Http\Requests\Kasir;

use App\Http\Requests\Kasir\Concerns\AturanKeranjangPos;
use Illuminate\Foundation\Http\FormRequest;

class HoldOtorisasiPosRequest extends FormRequest
{
    use AturanKeranjangPos;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->aturanItemKeranjangPos(), [
            'alasan_kasir' => ['required', 'string', 'min:5', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return [
            'alasan_kasir.required' => 'Alasan pengajuan perubahan ke Manajer wajib diisi.',
            'alasan_kasir.min' => 'Alasan pengajuan minimal 5 karakter agar jelas bagi Manajer.',
            'alasan_kasir.max' => 'Alasan pengajuan maksimal 500 karakter.',
        ];
    }
}
