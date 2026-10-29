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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'durasi_menit' => $this->input('durasi_menit', Layanan::DEFAULT_DURASI_MENIT),
            'jeda_menit' => $this->input('jeda_menit', Layanan::DEFAULT_JEDA_MENIT),
            'petugas_eksklusif' => $this->has('petugas_eksklusif')
                ? $this->boolean('petugas_eksklusif')
                : true,
        ]);
    }

    public function rules(): array
    {
        return [
            'nama_layanan' => ['required', 'string', 'max:100'],
            'kategori' => ['required', 'string', 'max:50'],
            'jabatan_petugas' => ['required', Rule::in(Layanan::JABATAN_PETUGAS)],
            'harga_layanan' => ['required', 'numeric', 'min:0'],
            'durasi_menit' => ['required', 'integer', 'min:15', 'max:480'],
            'jeda_menit' => ['required', 'integer', 'min:0', 'max:120'],
            'petugas_eksklusif' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_layanan.required' => 'Nama layanan perawatan wajib diisi.',
            'nama_layanan.max' => 'Nama layanan maksimal 100 karakter.',
            'kategori.required' => 'Kategori layanan wajib dipilih.',
            'jabatan_petugas.required' => 'Jabatan petugas penanggung jawab wajib dipilih.',
            'jabatan_petugas.in' => 'Jabatan petugas harus Stylist atau Therapist.',
            'harga_layanan.required' => 'Harga layanan wajib diisi.',
            'harga_layanan.numeric' => 'Harga layanan harus berupa angka.',
            'harga_layanan.min' => 'Harga layanan tidak boleh bernilai negatif.',
            'durasi_menit.required' => 'Durasi pengerjaan layanan wajib diisi.',
            'durasi_menit.integer' => 'Durasi pengerjaan harus berupa angka menit.',
            'durasi_menit.min' => 'Durasi pengerjaan minimal 15 menit.',
            'durasi_menit.max' => 'Durasi pengerjaan maksimal 480 menit (8 jam).',
            'jeda_menit.required' => 'Waktu jeda persiapan setelah layanan wajib diisi.',
            'jeda_menit.min' => 'Waktu jeda tidak boleh negatif.',
            'jeda_menit.max' => 'Waktu jeda maksimal 120 menit.',
        ];
    }
}
