<?php

namespace App\Http\Requests\Manajer;

use App\Models\Layanan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LayananRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_layanan'    => ['required', 'string', 'max:100'],
            'kategori'        => ['required', 'string', 'max:50'],
            'jabatan_petugas' => ['required', Rule::in(Layanan::JABATAN_PETUGAS)],
            'harga_layanan'   => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_layanan.required'    => 'Nama layanan perawatan wajib diisi.',
            'nama_layanan.max'         => 'Nama layanan maksimal 100 karakter.',
            'kategori.required'        => 'Kategori layanan wajib dipilih.',
            'jabatan_petugas.required' => 'Jabatan petugas penanggung jawab wajib dipilih.',
            'jabatan_petugas.in'       => 'Jabatan petugas harus Stylist atau Therapist.',
            'harga_layanan.required'   => 'Harga layanan wajib diisi.',
            'harga_layanan.numeric'    => 'Harga layanan harus berupa angka.',
            'harga_layanan.min'        => 'Harga layanan tidak boleh bernilai negatif.',
        ];
    }
}
