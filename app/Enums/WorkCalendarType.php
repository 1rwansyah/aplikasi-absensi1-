<?php

namespace App\Enums;

enum WorkCalendarType: string
{
    case FullDay = 'full_day';
    case HalfDay = 'half_day';
    case Holiday = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::FullDay => 'Full Day',
            self::HalfDay => 'Half Day',
            self::Holiday => 'Libur',
        };
    }

    public function weight(): float
    {
        return match ($this) {
            self::FullDay => 1.0,
            self::HalfDay => 1.0,
            self::Holiday => 0.0,
        };
    }
}
