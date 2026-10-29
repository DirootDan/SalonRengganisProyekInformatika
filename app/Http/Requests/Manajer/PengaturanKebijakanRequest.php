<?php

namespace App\Http\Requests\Manajer;

use Illuminate\Foundation\Http\FormRequest;

class PengaturanKebijakanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batas_pelanggaran' => ['required', 'integer', 'min:1', 'max:20'],
            'rentang_hari_pelanggaran' => ['nullable', 'integer', 'min:1', 'max:365'],
            'lama_blokir_hari' => ['required', 'integer', 'min:1', 'max:90'],
            'lama_blokir_kedua_hari' => ['nullable', 'integer', 'min:1', 'max:180'],
            'toleransi_bayar_hari' => ['required', 'integer', 'min:1', 'max:14'],
            'batas_batal_jam' => ['required', 'integer', 'min:0', 'max:48'],
        ];
    }

    public function messages(): array
    {
        return [
            'batas_pelanggaran.required' => 'Batas maksimal pelanggaran wajib diisi.',
            'batas_pelanggaran.min' => 'Batas pelanggaran minimal 1 kali.',
            'batas_pelanggaran.max' => 'Batas pelanggaran maksimal 20 kali.',
            'rentang_hari_pelanggaran.min' => 'Rentang hari evaluasi pelanggaran minimal 1 hari.',
            'rentang_hari_pelanggaran.max' => 'Rentang hari evaluasi pelanggaran maksimal 365 hari.',
            'lama_blokir_hari.required' => 'Durasi blokir tahap pertama wajib diisi.',
            'lama_blokir_hari.min' => 'Durasi blokir tahap pertama minimal 1 hari.',
            'lama_blokir_hari.max' => 'Durasi blokir tahap pertama maksimal 90 hari.',
            'lama_blokir_kedua_hari.min' => 'Durasi blokir tahap kedua minimal 1 hari.',
            'lama_blokir_kedua_hari.max' => 'Durasi blokir tahap kedua maksimal 180 hari.',
            'toleransi_bayar_hari.required' => 'Toleransi pembayaran wajib diisi.',
            'batas_batal_jam.required' => 'Batas waktu pembatalan gratis wajib diisi.',
        ];
    }
}
