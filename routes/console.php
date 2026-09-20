<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// BMKG publishes forecast updates twice a day; browsers never call BMKG directly (PRD §45).
Schedule::command('weather:refresh')->twiceDaily(6, 18)->withoutOverlapping();

// PRD §98: status resmi yang kedaluwarsa membuat jalurnya kembali ke UNKNOWN tanpa
// suara, jadi kemundurannya perlu dicatat setiap hari, bukan menunggu ada yang membuka
// halaman admin.
Schedule::command('data:freshness-check')->dailyAt('07:00')->withoutOverlapping();
