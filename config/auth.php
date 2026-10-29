<?php

use App\Models\Karyawan;
use App\Models\Pelanggan;
use App\Models\User;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'karyawan'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'karyawan'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'karyawan' => [
            'driver' => 'session',
            'provider' => 'karyawan',
        ],
        'pelanggan' => [
            'driver' => 'session',
            'provider' => 'pelanggan',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', User::class),
        ],
        'karyawan' => [
            'driver' => 'eloquent',
            'model' => Karyawan::class,
        ],
        'pelanggan' => [
            'driver' => 'eloquent',
            'model' => Pelanggan::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'karyawan' => [
            'provider' => 'karyawan',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
        'pelanggan' => [
            'provider' => 'pelanggan',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
