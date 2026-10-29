<?php

namespace App\Http\Requests\Manajer;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanStrukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_salon' => ['required', 'string', 'max:100'],
            'slogan' => ['nullable', 'string', 'max:150'],
            'alamat' => ['required', 'string', 'max:255'],
            'telepon' => ['required', 'string', 'max:50'],
            'instagram' => ['nullable', 'string', 'max:80'],
            'teks_header' => ['nullable', 'string', 'max:150'],
            'teks_footer' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_salon.required' => 'Nama salon pada struk wajib diisi.',
            'nama_salon.max' => 'Nama salon maksimal 100 karakter.',
            'slogan.max' => 'Slogan salon maksimal 150 karakter.',
            'alamat.required' => 'Alamat salon wajib diisi.',
            'alamat.max' => 'Alamat salon maksimal 255 karakter.',
            'telepon.required' => 'Nomor telepon salon wajib diisi.',
            'telepon.max' => 'Nomor telepon salon maksimal 50 karakter.',
            'instagram.max' => 'Akun Instagram maksimal 80 karakter.',
            'teks_header.max' => 'Teks header struk maksimal 150 karakter.',
            'teks_footer.max' => 'Teks footer struk maksimal 500 karakter.',
        ];
    }
}
