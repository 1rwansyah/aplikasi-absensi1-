<?php

namespace App\Support;

final class TimeFormat
{
    public static function display(mixed $value): string
    {
        return substr((string) $value, 0, 5);
    }

    public static function storage(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }

    public static function normalized(mixed $value): string
    {
        $time = (string) $value;

        return self::storage($time);
    }
}
