<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule Laravel tidak diandalkan di Hostinger — pakai cron langsung ke artisan.
// Contoh auto-alpha (jam 23:00 WIB / 16:00 UTC):
//   /usr/bin/php /path/to/absensi-app/artisan attendance:mark-alpha
// Jangan masukkan --from/--to ke cron harian.

// Schedule::command('attendance:send-whatsapp-report masuk')
//     ->cron('0 11 * * 1-6')
//     ->timezone('Asia/Jakarta');

// Schedule::command('attendance:expire-pending-leaves')
//     ->dailyAt('00:00')
//     ->timezone('Asia/Jakarta');

// Schedule::command('attendance:mark-alpha')
//     ->dailyAt('23:00')
//     ->timezone('Asia/Jakarta');
