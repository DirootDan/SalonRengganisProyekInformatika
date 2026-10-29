<?php

namespace Database\Seeders;

use App\Models\Layanan;
use Illuminate\Database\Seeder;

class LayananSeeder extends Seeder
{
    public function run(): void
    {
        // [nama layanan, harga, jabatan petugas yang menangani]
        $daftar = [
            ['Potong Rambut',       50000,  'Stylist'],
            ['Creambath',           80000,  'Stylist'],
            ['Hair Coloring',       250000, 'Stylist'],
            ['Facial',              120000, 'Therapist'],
            ['Manicure & Pedicure', 100000, 'Therapist'],
        ];

        foreach ($daftar as [$nama, $harga, $jabatan]) {
            Layanan::updateOrCreate(
                ['nama_layanan' => $nama],
                [
                    'harga_layanan' => $harga,
                    'jabatan_petugas' => $jabatan,
                ]
            );
        }
    }
}
