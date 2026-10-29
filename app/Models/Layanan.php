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

    public function totalBlokirMenit(): int
    {
        return max(15, (int) ($this->durasi_menit ?: self::DEFAULT_DURASI_MENIT))
            + max(0, (int) ($this->jeda_menit ?? self::DEFAULT_JEDA_MENIT));
    }

    public function jumlahReservasiAktif(): int
    {
        return DB::table('detail_reservasi')
            ->join('reservasi', 'reservasi.id_reservasi', '=', 'detail_reservasi.id_reservasi')
            ->where('detail_reservasi.id_layanan', $this->id_layanan)
            ->whereIn('reservasi.status', Reservasi::STATUS_AKTIF_SLOT)
            ->count();
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
