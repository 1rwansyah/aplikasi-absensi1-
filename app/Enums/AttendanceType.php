<?php

namespace App\Enums;

enum AttendanceType: string
{
    case Regular = 'regular';
    case Sick = 'sick';
    case Permission = 'permission';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Reguler',
            self::Sick => 'Sakit',
            self::Permission => 'Izin',
        };
    }

    public function isLeave(): bool
    {
        return $this !== self::Regular;
    }
}
