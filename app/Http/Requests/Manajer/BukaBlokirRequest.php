<?php

namespace App\Http\Requests\Manajer;

use Illuminate\Foundation\Http\FormRequest;

class BukaBlokirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alasan_buka' => ['required', 'string', 'min:5', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_buka.required' => 'Alasan pembukaan blokir wajib diisi.',
            'alasan_buka.min' => 'Alasan pembukaan blokir minimal 5 karakter.',
            'alasan_buka.max' => 'Alasan pembukaan blokir maksimal 255 karakter.',
        ];
    }
}
