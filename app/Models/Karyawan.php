<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Karyawan extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'karyawan';
    protected $primaryKey = 'id_karyawan';

    public const JABATAN_MANAJER   = 'Manajer';
    public const JABATAN_KASIR     = 'Kasir';
    public const JABATAN_STYLIST   = 'Stylist';
    public const JABATAN_THERAPIST = 'Therapist';

    public const DAFTAR_JABATAN = [
        self::JABATAN_MANAJER,
        self::JABATAN_KASIR,
        self::JABATAN_STYLIST,
        self::JABATAN_THERAPIST,
    ];

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
                $base = ! empty($karyawan->email)
                    ? explode('@', $karyawan->email)[0]
                    : Str::slug($karyawan->nama_staff, '_');
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
            self::JABATAN_MANAJER   => 'manajer.dashboard',
            self::JABATAN_KASIR     => 'kasir.dashboard',
            self::JABATAN_STYLIST   => 'stylist.dashboard',
            self::JABATAN_THERAPIST => 'therapist.dashboard',
            default                 => null,
        };
    }

    /**
     * Hitung jumlah reservasi yang masih berjalan dan ditugaskan pada karyawan ini
     * agar manajer mendapat peringatan pengalihan tugas sebelum menonaktifkan akun.
     */
    public function jumlahBookingAktif(): int
    {
        return DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->where('detail_reservasi.id_karyawan', $this->id_karyawan)
            ->whereIn('reservasi.status', [Reservasi::STATUS_MENUNGGU, Reservasi::STATUS_DITERIMA])
            ->count();
    }

    /**
     * Nonaktifkan dan arsipkan karyawan tanpa merusak integritas riwayat transaksi masa lalu.
     */
    public function nonaktifkanDanArsipkan(): int
    {
        $bookingAktif = $this->jumlahBookingAktif();

        $this->update(['aktif' => false]);
        $this->delete();

        return $bookingAktif;
    }
}
