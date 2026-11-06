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

// FR-24: pengingat H-3 dan H-1 jatuh pukul 07:00 zona gunungnya, yang dalam UTC selalu
// tepat di awal jam (00:00, 23:00, atau 22:00 UTC), jadi pemeriksaan tiap jam cukup.
// Klaim atomik di TripReminderScheduler yang mencegah kirim ganda; kunci tumpang
// tindih ini hanya menghemat kerja, dan kedaluwarsa sebelum jam berikutnya.
Schedule::command('trips:kirim-pengingat')->hourly()->withoutOverlapping(50);
