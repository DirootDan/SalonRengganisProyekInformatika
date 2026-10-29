<?php

namespace App\Http\Requests\Pelanggan;

use App\Models\Reservasi;
use Illuminate\Foundation\Http\FormRequest;

class SimpanBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tgl_booking' => ['required', 'date', 'after_or_equal:today'],
            'jam_booking' => [
                'required',
                'regex:/^([01][0-9]|2[0-3]):[0-5][0-9]$/',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $jam = substr((string) $value, 0, 5);
                    if ($jam < Reservasi::JAM_BUKA_SALON || $jam > '20:30') {
                        $fail('Jam kedatangan harus berada dalam jam operasional salon (09:00 s/d 20:30 WIB).');
                    }
                },
            ],
            'layanan' => ['required', 'array', 'min:1'],
            'layanan.*' => ['integer', 'exists:layanan,id_layanan'],
            'petugas' => ['nullable', 'array'],
            'produk_addon' => ['nullable', 'array'],
            'produk_addon.*' => ['nullable', 'integer', 'min:0', 'max:50'],
            'kode_promo' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function messages(): array
    {
        return [
            'tgl_booking.required' => 'Tanggal kedatangan wajib diisi.',
            'tgl_booking.date' => 'Format tanggal tidak valid.',
            'tgl_booking.after_or_equal' => 'Tanggal kedatangan tidak boleh di masa lalu.',
            'jam_booking.required' => 'Jam kedatangan wajib dipilih.',
            'jam_booking.regex' => 'Format jam kedatangan harus HH:MM (contoh: 10:30).',
            'layanan.required' => 'Pilih minimal satu layanan perawatan.',
            'layanan.min' => 'Pilih minimal satu layanan perawatan.',
            'layanan.*.exists' => 'Layanan yang dipilih tidak ditemukan.',
            'produk_addon.*.integer' => 'Jumlah produk tambahan harus berupa angka bulat.',
            'produk_addon.*.min' => 'Jumlah produk tambahan tidak boleh negatif.',
            'produk_addon.*.max' => 'Jumlah produk tambahan maksimal 50 pcs per item.',
            'kode_promo.max' => 'Kode promo maksimal 40 karakter.',
        ];
    }
}
