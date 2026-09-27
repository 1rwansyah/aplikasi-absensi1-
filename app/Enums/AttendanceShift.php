<?php

namespace App\Enums;

enum AttendanceShift: string
{
    case Day = 'day';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Day, self::Night => 'Jam Kerja Reguler',
        };
    }
}
