<?php

namespace App\Http\Requests\Kasir;

use App\Http\Requests\Kasir\Concerns\AturanKeranjangPos;
use Illuminate\Foundation\Http\FormRequest;

class DirectOverridePosRequest extends FormRequest
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
            'username_manajer' => ['required', 'string', 'max:50'],
            'password_manajer' => ['required', 'string'],
        ]);
    }

    public function messages(): array
    {
        return array_merge($this->pesanAturanKeranjangPos(), [
            'alasan_kasir.required' => 'Alasan perubahan wajib diisi terlebih dahulu oleh Kasir.',
            'alasan_kasir.min' => 'Alasan perubahan minimal 5 karakter.',
            'username_manajer.required' => 'Username Manajer wajib diisi.',
            'password_manajer.required' => 'Password Manajer wajib diisi.',
        ]);
    }
}
