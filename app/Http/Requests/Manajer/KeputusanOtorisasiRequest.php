<?php

namespace App\Http\Requests\Manajer;

use Illuminate\Foundation\Http\FormRequest;

class KeputusanOtorisasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $sedangMenolak = $this->routeIs('manajer.persetujuan.tolak');

        return [
            'catatan_manajer' => [$sedangMenolak ? 'required' : 'nullable', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'catatan_manajer.required' => 'Alasan penolakan wajib diisi agar Kasir dapat menyampaikan informasi yang jelas kepada pelanggan.',
            'catatan_manajer.min' => 'Catatan minimal 3 karakter.',
            'catatan_manajer.max' => 'Catatan maksimal 500 karakter.',
        ];
    }
}
