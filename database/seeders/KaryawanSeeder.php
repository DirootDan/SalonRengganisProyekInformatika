<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use Illuminate\Database\Seeder;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        $akun = [
            ['admin',      'Manajer Salon',    'admin@salon.test',      'Manajer',   'admin123'],
            ['kasir',      'Kasir Salon',      'kasir@salon.test',      'Kasir',     'kasir123'],
            ['stylist',    'Stylist Salon',    'stylist@salon.test',    'Stylist',   'stylist123'],
            ['stylist2',   'Sinta Maharani',   'sinta@salon.test',      'Stylist',   'stylist123'],
            ['therapist',  'Dewi Lestari',     'dewi@salon.test',       'Therapist', 'therapist123'],
            ['therapist2', 'Ayu Pratiwi',      'ayu@salon.test',        'Therapist', 'therapist123'],
        ];

        foreach ($akun as [$username, $nama, $email, $role, $password]) {
            Karyawan::updateOrCreate(
                ['username' => $username],
                [
                    'nama_staff' => $nama,
                    'email' => $email,
                    'role_jabatan' => $role,
                    'password' => $password,
                ]
            );
        }
    }
}
