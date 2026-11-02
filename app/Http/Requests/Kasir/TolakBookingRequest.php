<?php

namespace App\Http\Requests\Kasir;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class TolakBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('alasan_penolakan')) {
            $this->merge([
                'alasan_penolakan' => Str::squish((string) $this->input('alasan_penolakan')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'alasan_penolakan' => ['required', 'string', 'min:4', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'alasan_penolakan.required' => 'Alasan penolakan wajib diisi agar pelanggan mengetahui penyebabnya.',
            'alasan_penolakan.min' => 'Alasan penolakan minimal 4 karakter.',
            'alasan_penolakan.max' => 'Alasan penolakan maksimal 255 karakter.',
        ];
    }
}
