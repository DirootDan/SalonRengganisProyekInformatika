<?php

namespace Database\Seeders;

use App\Models\Layanan;
use Illuminate\Database\Seeder;

class LayananSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['Potong Rambut', 50000],
            ['Creambath', 80000],
            ['Hair Coloring', 250000],
            ['Facial', 120000],
            ['Manicure & Pedicure', 100000],
        ];

        foreach ($daftar as [$nama, $harga]) {
            Layanan::updateOrCreate(
                ['nama_layanan' => $nama],
                ['harga_layanan' => $harga]
            );
        }
    }
}