<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use Illuminate\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        Karyawan::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama_staff'   => 'Administrator',
                'email'        => 'admin@salon.test',
                'role_jabatan' => 'Admin',
                'password'     => 'admin123',
            ]
        );
    }
}