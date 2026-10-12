<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PelangganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pelanggan')->insert([
            [
                'nama_cust' => 'Rina Anggraini',
                'no_telp'   => '081234567890',
                'email'     => 'rina@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_cust' => 'Dewi Lestari',
                'no_telp'   => '082198765432',
                'email'     => 'dewi.lestari@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_cust' => 'Siti Nurhaliza',
                'no_telp'   => '085712345678',
                'email'     => 'siti.nurhaliza@yahoo.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_cust' => 'Maya Sari',
                'no_telp'   => '081377788899',
                'email'     => 'maya.sari@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_cust' => 'Indah Permata',
                'no_telp'   => '089655544433',
                'email'     => 'indah.permata@gmail.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}