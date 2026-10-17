<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use Illuminate\Database\Seeder;

class PelangganSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            ['Rina Anggraini', '081234567890', 'rina@gmail.com'],
            ['Dewi Lestari',   '082198765432', 'dewi.lestari@gmail.com'],
            ['Siti Nurhaliza', '085712345678', 'siti.nurhaliza@yahoo.com'],
            ['Maya Sari',      '081377788899', 'maya.sari@gmail.com'],
            ['Indah Permata',  '089655544433', 'indah.permata@gmail.com'],
        ];

        foreach ($daftar as [$nama, $telp, $email]) {
            // updateOrCreate: aman dijalankan berulang, dan memperbaiki data yang sudah ada
            Pelanggan::updateOrCreate(
                ['email' => $email],
                [
                    'nama_cust' => $nama,
                    'no_telp'   => $telp,
                    'password'  => 'password123', // di-hash otomatis oleh model
                ]
            );
        }
    }
}