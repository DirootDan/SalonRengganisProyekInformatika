<?php

namespace App\Http\Requests\Manajer;

use Illuminate\Foundation\Http\FormRequest;

class MetodePembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_metode'    => ['required', 'string', 'max:80'],
            'kategori'       => ['required', 'string', 'max:50'],
            'nomor_rekening' => ['required', 'string', 'max:100'],
            'atas_nama'      => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_metode.required'    => 'Nama metode pembayaran wajib diisi.',
            'nama_metode.max'         => 'Nama metode pembayaran maksimal 80 karakter.',
            'kategori.required'       => 'Kategori metode pembayaran wajib dipilih.',
            'kategori.max'            => 'Kategori metode pembayaran maksimal 50 karakter.',
            'nomor_rekening.required' => 'Nomor rekening atau kode akun wajib diisi.',
            'nomor_rekening.max'      => 'Nomor rekening maksimal 100 karakter.',
            'atas_nama.required'      => 'Nama pemilik rekening/akun wajib diisi.',
            'atas_nama.max'           => 'Nama pemilik akun maksimal 100 karakter.',
        ];
    }
}
