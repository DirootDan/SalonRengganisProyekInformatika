<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jalankan evaluasi booking hangus & reset blokir setiap jam secara otomatis
Schedule::command('booking:evaluasi-hangus')->hourly()->withoutOverlapping();
