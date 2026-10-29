<?php

namespace App\Http\Requests\Manajer;

use App\Models\Karyawan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var Karyawan|int|null $parameterKaryawan */
        $parameterKaryawan = $this->route('karyawan') ?? $this->route('id');
        $idKaryawan = $parameterKaryawan instanceof Karyawan
            ? $parameterKaryawan->id_karyawan
            : $parameterKaryawan;

        $sedangUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        return [
            'nama_staff' => ['required', 'string', 'max:100'],
            'username' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('karyawan', 'username')->ignore($idKaryawan, 'id_karyawan'),
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                Rule::unique('karyawan', 'email')->ignore($idKaryawan, 'id_karyawan'),
            ],
            'role_jabatan' => ['required', Rule::in(Karyawan::DAFTAR_JABATAN)],
            'persen_komisi' => ['required', 'numeric', 'min:0', 'max:100'],
            'aktif' => ['nullable', 'boolean'],
            'password' => [$sedangUpdate ? 'nullable' : 'required', 'string', 'min:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_staff.required' => 'Nama lengkap karyawan wajib diisi.',
            'nama_staff.max' => 'Nama lengkap karyawan maksimal 100 karakter.',
            'username.required' => 'Username login wajib diisi.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'username.unique' => 'Username ini sudah digunakan oleh karyawan lain.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email tidak valid.',
            'email.unique' => 'Alamat email sudah terdaftar pada akun lain.',
            'role_jabatan.required' => 'Jabatan karyawan wajib dipilih.',
            'role_jabatan.in' => 'Pilihan jabatan karyawan tidak valid.',
            'persen_komisi.required' => 'Persentase komisi wajib diisi.',
            'persen_komisi.min' => 'Persentase komisi tidak boleh kurang dari 0%.',
            'persen_komisi.max' => 'Persentase komisi tidak boleh melebihi 100%.',
            'password.required' => 'Kata sandi wajib diisi untuk karyawan baru.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ];
    }
}
