<?php

namespace App\Http\Requests\Kasir;

use App\Models\PermintaanOtorisasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AjukanAksesKatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'konteks' => [
                'required',
                'string',
                Rule::in([PermintaanOtorisasi::KONTEKS_KATALOG_LAYANAN, PermintaanOtorisasi::KONTEKS_KATALOG_PRODUK]),
            ],
            'jenis_aksi' => [
                'required',
                'string',
                Rule::in(PermintaanOtorisasi::DAFTAR_AKSI_KATALOG),
            ],
            'id_target' => [
                Rule::requiredIf(fn () => in_array($this->input('jenis_aksi'), [PermintaanOtorisasi::AKSI_UBAH, PermintaanOtorisasi::AKSI_HAPUS], true)),
                'nullable',
                'integer',
                'min:1',
            ],
            'alasan_kasir' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'konteks.required' => 'Kategori katalog yang diajukan wajib dipilih.',
            'konteks.in' => 'Kategori katalog tidak valid.',
            'jenis_aksi.required' => 'Jenis aksi (tambah/ubah/hapus) wajib dipilih.',
            'jenis_aksi.in' => 'Jenis aksi tidak valid.',
            'id_target.required' => 'Item layanan atau produk yang akan diubah/dihapus wajib dipilih.',
            'alasan_kasir.required' => 'Alasan pengajuan hak akses ke Manajer wajib diisi.',
            'alasan_kasir.min' => 'Alasan pengajuan minimal 5 karakter agar jelas bagi Manajer.',
            'alasan_kasir.max' => 'Alasan pengajuan maksimal 500 karakter.',
        ];
    }
}
