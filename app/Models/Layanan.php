<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Layanan extends Model
{
    use SoftDeletes;

    protected $table = 'layanan';

    protected $primaryKey = 'id_layanan';

    public const JABATAN_PETUGAS = ['Stylist', 'Therapist'];

    public const DEFAULT_DURASI_MENIT = 60;

    public const DEFAULT_JEDA_MENIT = 15;

    public const KATEGORI_LIST = [
        'Perawatan Rambut' => 'Stylist',
        'Perawatan Wajah & Tubuh' => 'Therapist',
    ];

    protected $fillable = [
        'nama_layanan',
        'kategori',
        'harga_layanan',
        'durasi_menit',
        'jeda_menit',
        'petugas_eksklusif',
        'jabatan_petugas',
    ];

    protected function casts(): array
    {
        return [
            'harga_layanan' => 'decimal:2',
            'durasi_menit' => 'integer',
            'jeda_menit' => 'integer',
            'petugas_eksklusif' => 'boolean',
        ];
    }

    public function reservasi(): BelongsToMany
    {
        return $this->belongsToMany(
            Reservasi::class,
            'detail_reservasi',
            'id_layanan',
            'id_reservasi'
        );
    }

    public function reservasiAktif(): BelongsToMany
    {
        return $this->reservasi()->whereIn('reservasi.status', Reservasi::STATUS_AKTIF_SLOT);
    }

    public function durasiEfektifMenit(): int
    {
        return max(15, (int) ($this->durasi_menit ?: self::DEFAULT_DURASI_MENIT));
    }

    public function jedaEfektifMenit(): int
    {
        return max(0, (int) ($this->jeda_menit ?? self::DEFAULT_JEDA_MENIT));
    }

    public function totalBlokirMenit(): int
    {
        return $this->durasiEfektifMenit() + $this->jedaEfektifMenit();
    }

    public function jumlahReservasiAktif(): int
    {
        if (array_key_exists('reservasi_aktif_count', $this->attributes)) {
            return (int) $this->attributes['reservasi_aktif_count'];
        }

        return DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->where('detail_reservasi.id_layanan', $this->id_layanan)
            ->whereIn('reservasi.status', Reservasi::STATUS_AKTIF_SLOT)
            ->count();
    }

    public function pesanDampakHapusKasir(): string
    {
        $jmlAktif = $this->jumlahReservasiAktif();

        return $jmlAktif > 0
            ? 'Perhatian: Layanan ini masih memiliki '.$jmlAktif.' reservasi aktif sehingga penghapusan akan ditolak server.'
            : 'Setelah dihapus, hak akses otomatis terkunci kembali.';
    }

    public function pesanDampakHapusManajer(): string
    {
        $jmlAktif = $this->jumlahReservasiAktif();

        return $jmlAktif > 0
            ? 'Perhatian: Layanan ini masih memiliki '.$jmlAktif.' reservasi aktif (Menunggu/Diterima) sehingga penghapusan akan ditolak server.'
            : 'Layanan ini akan diarsipkan dari daftar pemesanan baru, namun riwayat nota lama tetap tersimpan.';
    }

    public static function jabatanDariKategori(string $kategori): string
    {
        if (
            stripos($kategori, 'Wajah') !== false
            || stripos($kategori, 'Tubuh') !== false
            || stripos($kategori, 'Kuku') !== false
            || stripos($kategori, 'Terapi') !== false
            || stripos($kategori, 'Perawatan') !== false && stripos($kategori, 'Rambut') === false
        ) {
            return 'Therapist';
        }

        return self::KATEGORI_LIST[$kategori] ?? 'Stylist';
    }

    public static function jabatanUntukKategori(string $kategori): string
    {
        return self::jabatanDariKategori($kategori);
    }
}
