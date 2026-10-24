<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Karyawan extends Authenticatable
{
    use Notifiable;

    protected $table = 'karyawan';
    protected $primaryKey = 'id_karyawan';

    protected $fillable = [
        'nama_staff',
        'email',
        'role_jabatan',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /**
     * Nama route halaman utama sesuai jabatan.
     * Mengembalikan null kalau jabatannya tidak dikenali.
     */
    public function rutePeran(): ?string
    {
        return match ($this->role_jabatan) {
            'Manajer' => 'manajer.dashboard',
            'Kasir'   => 'kasir.dashboard',
            'Stylist'   => 'stylist.dashboard',
            'Therapist' => 'therapist.dashboard',
            default   => null,
        };
    }
}