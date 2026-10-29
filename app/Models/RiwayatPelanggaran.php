<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatPelanggaran extends Model
{
    protected $table = 'riwayat_pelanggaran';

    protected $primaryKey = 'id_pelanggaran';

    public const JENIS_PELANGGARAN = 'Pelanggaran';

    public const JENIS_BUKA_MANUAL = 'Buka Blokir Manual';

    public const JENIS_RESET_OTOMATIS = 'Reset Otomatis';

    public const BATAS_LOG_TERBARU = 25;

    protected $fillable = [
        'id_pelanggan',
        'id_reservasi',
        'jenis',
        'tanggal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function reservasi(): BelongsTo
    {
        return $this->belongsTo(Reservasi::class, 'id_reservasi', 'id_reservasi');
    }
}
