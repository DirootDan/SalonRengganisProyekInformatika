<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Pelanggan extends Authenticatable
{
    use Notifiable;

    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'nama_cust',
        'no_telp',
        'email',
        'password',
        'jumlah_pelanggaran',
        'jumlah_kali_blokir',
        'diblokir_permanen',
        'diblokir_sampai',
        'alasan_blokir',
        'reset_pelanggaran_pada',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'               => 'hashed',
            'jumlah_pelanggaran'     => 'integer',
            'jumlah_kali_blokir'     => 'integer',
            'diblokir_permanen'      => 'boolean',
            'diblokir_sampai'        => 'datetime',
            'reset_pelanggaran_pada' => 'datetime',
        ];
    }

    public function reservasi(): HasMany
    {
        return $this->hasMany(Reservasi::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function riwayatPelanggaran(): HasMany
    {
        return $this->hasMany(RiwayatPelanggaran::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(NotifikasiPelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function sedangDiblokir(): bool
    {
        if ($this->diblokir_permanen) {
            return true;
        }

        return $this->diblokir_sampai !== null && $this->diblokir_sampai->isFuture();
    }

    public function sisaHariBlokir(): int
    {
        if (! $this->sedangDiblokir() || $this->diblokir_permanen || ! $this->diblokir_sampai) {
            return 0;
        }

        return (int) max(1, ceil(now()->floatDiffInDays($this->diblokir_sampai, false)));
    }

    public function keteranganBlokir(): string
    {
        if (! $this->sedangDiblokir()) {
            return 'Aktif';
        }

        if ($this->diblokir_permanen) {
            return 'Diblokir Permanen (Blokir ke-' . max(3, (int) $this->jumlah_kali_blokir) . ' — menunggu pembukaan manual oleh Manajer)';
        }

        return 'Diblokir Sementara hingga ' . $this->diblokir_sampai->format('d/m/Y H:i') . ' WIB (' . $this->sisaHariBlokir() . ' hari lagi)';
    }

    /**
     * Hitung jumlah pelanggaran aktif dalam rentang N hari terakhir (default 90 hari)
     * yang terjadi setelah kejadian Reset Otomatis / Buka Blokir Manual terakhir.
     */
    public function hitungPelanggaranAktifDalamRentang(?int $rentangHari = null): int
    {
        $rentang = $rentangHari ?? PengaturanKebijakan::aktif()->rentang_hari_pelanggaran ?? 90;
        $batasTanggal = now()->subDays($rentang)->toDateString();

        $idResetTerakhir = (int) $this->riwayatPelanggaran()
            ->whereIn('jenis', ['Reset Otomatis', 'Buka Blokir Manual'])
            ->max('id_pelanggaran');

        return $this->riwayatPelanggaran()
            ->where('jenis', 'Pelanggaran')
            ->where('id_pelanggaran', '>', $idResetTerakhir)
            ->whereDate('tanggal', '>=', $batasTanggal)
            ->count();
    }
}
