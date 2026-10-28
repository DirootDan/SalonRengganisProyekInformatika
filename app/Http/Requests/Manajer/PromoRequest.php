<?php

namespace App\Http\Requests\Manajer;

use App\Models\Promo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Promo|int|null $parameterPromo */
        $parameterPromo = $this->route('promo') ?? $this->route('id');
        $idPromo = $parameterPromo instanceof Promo
            ? $parameterPromo->id_promo
            : $parameterPromo;

        return [
            'kode_promo'     => [
                'required',
                'string',
                'max:40',
                Rule::unique('promo', 'kode_promo')->ignore($idPromo, 'id_promo'),
            ],
            'nama_promo'     => ['required', 'string', 'max:100'],
            'tipe_diskon'    => ['required', Rule::in(Promo::DAFTAR_TIPE)],
            'nilai_diskon'   => [
                'required',
                'numeric',
                'min:1',
                Rule::when(
                    $this->input('tipe_diskon') === Promo::TIPE_PERSENTASE,
                    ['max:' . Promo::MAKS_PERSEN_DISKON]
                ),
            ],
            'min_transaksi'  => ['required', 'numeric', 'min:0'],
            'berlaku_sampai' => ['required', 'date'],
            'aktif'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'kode_promo.required'     => 'Kode promo wajib diisi.',
            'kode_promo.max'          => 'Kode promo maksimal 40 karakter.',
            'kode_promo.unique'       => 'Kode promo ini sudah digunakan oleh program promo lain.',
            'nama_promo.required'     => 'Nama program promo wajib diisi.',
            'tipe_diskon.required'    => 'Tipe potongan diskon wajib dipilih.',
            'tipe_diskon.in'          => 'Tipe diskon harus berupa Persentase atau Nominal.',
            'nilai_diskon.required'   => 'Nilai potongan diskon wajib diisi.',
            'nilai_diskon.min'        => 'Nilai diskon minimal 1.',
            'nilai_diskon.max'        => 'Potongan diskon persentase tidak boleh melebihi 100%.',
            'min_transaksi.required'  => 'Minimal nilai transaksi wajib diisi.',
            'min_transaksi.min'       => 'Minimal transaksi tidak boleh negatif.',
            'berlaku_sampai.required' => 'Tanggal berakhirnya promo wajib diisi.',
            'berlaku_sampai.date'     => 'Format tanggal berakhir promo tidak valid.',
        ];
    }
}
