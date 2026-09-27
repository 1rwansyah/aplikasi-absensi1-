<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case OnTime = 'on_time';
    case Late = 'late';
    case EarlyOut = 'early_out';
    case LateOut = 'late_out';
    case Sick = 'sick';
    case Permission = 'permission';
    case Alpha = 'alpha';

    public function label(): string
    {
        return match ($this) {
            self::OnTime => 'Tepat Waktu',
            self::Late => 'Telat',
            self::EarlyOut => 'Pulang Cepat',
            self::LateOut => 'Pulang Lewat',
            self::Sick => 'Sakit',
            self::Permission => 'Izin',
            self::Alpha => 'Alfa',
        };
    }

    public static function forLeave(AttendanceType $type): self
    {
        return match ($type) {
            AttendanceType::Sick => self::Sick,
            AttendanceType::Permission => self::Permission,
            AttendanceType::Regular => self::OnTime,
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Late, self::EarlyOut, self::LateOut => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-500/30',
            self::Sick => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400 dark:ring-blue-500/30',
            self::Permission => 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-900/20 dark:text-purple-400 dark:ring-purple-500/30',
            self::Alpha => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-500/30',
            default => 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-500/30',
        };
    }
}
