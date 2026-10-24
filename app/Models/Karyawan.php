<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Karyawan extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'karyawan';
    protected $primaryKey = 'id_karyawan';

    protected $fillable = [
        'nama_staff',
        'email',
        'role_jabatan',
        'jabatan',
        'persen_komisi',
        'aktif',
        'username',
        'password',
    ];

    protected static function booted(): void
    {
        static::creating(function (Karyawan $karyawan) {
            if (empty($karyawan->username)) {
                $base = !empty($karyawan->email)
                    ? explode('@', $karyawan->email)[0]
                    : \Illuminate\Support\Str::slug($karyawan->nama_staff, '_');
                $karyawan->username = $base . '_' . substr(uniqid(), -4);
            }
        });
    }

    public function getJabatanAttribute(): ?string
    {
        return $this->attributes['role_jabatan'] ?? null;
    }

    public function setJabatanAttribute(?string $value): void
    {
        $this->attributes['role_jabatan'] = $value;
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'      => 'hashed',
            'persen_komisi' => 'decimal:2',
            'aktif'         => 'boolean',
        ];
    }

    public function rutePeran(): ?string
    {
        return match ($this->role_jabatan) {
            'Manajer'   => 'manajer.dashboard',
            'Kasir'     => 'kasir.dashboard',
            'Stylist'   => 'stylist.dashboard',
            'Therapist' => 'therapist.dashboard',
            default     => null,
        };
    }
}
