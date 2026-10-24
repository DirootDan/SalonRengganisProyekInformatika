<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Layanan extends Model
{
    protected $table = 'layanan';
    protected $primaryKey = 'id_layanan';

    /** Jabatan karyawan yang dapat menangani layanan */
    public const JABATAN_PETUGAS = ['Stylist', 'Therapist'];

    protected $fillable = [
        'nama_layanan',
        'harga_layanan',
        'jabatan_petugas',
    ];
}
