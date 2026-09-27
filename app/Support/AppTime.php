<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Waktu aplikasi — selalu memakai timezone config (default Asia/Jakarta).
 */
final class AppTime
{
    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function today(): Carbon
    {
        return Carbon::today(self::timezone());
    }

    public static function currentTimeForStorage(): string
    {
        return self::now()->format('H:i:s');
    }

    public static function timezone(): string
    {
        return config('app.timezone', 'Asia/Jakarta');
    }
}
