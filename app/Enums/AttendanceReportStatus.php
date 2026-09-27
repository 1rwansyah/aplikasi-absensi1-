<?php

namespace App\Enums;

use App\Models\Attendance;

enum AttendanceReportStatus: string
{
    case Alpha = 'alpha';
    case OnTime = 'on_time';
    case Late = 'late';
    case Working = 'working';
    case LateOut = 'late_out';
    case Sick = 'sick';
    case Permission = 'permission';

    public function label(): string
    {
        return match ($this) {
            self::Alpha => 'Alfa',
            self::OnTime => 'Hadir',
            self::Late => 'Telat',
            self::Working => 'Hadir (Belum Pulang)',
            self::LateOut => 'Hadir (Pulang Lewat)',
            self::Sick => 'Sakit',
            self::Permission => 'Izin',
        };
    }

    public function labelFor(?Attendance $attendance = null): string
    {
        if ($this === self::Sick && $attendance?->isRejectedLeave()) {
            return 'Sakit (Ditolak)';
        }

        return $this->label();
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Alpha => 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-500/30',
            self::Late, self::LateOut => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-500/30',
            self::Sick => 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20 dark:bg-blue-900/20 dark:text-blue-400 dark:ring-blue-500/30',
            self::Permission => 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20 dark:bg-purple-900/20 dark:text-purple-400 dark:ring-purple-500/30',
            self::Working => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-400 dark:ring-sky-500/30',
            default => 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-500/30',
        };
    }

    public static function fromAttendance(?Attendance $attendance): self
    {
        if (! $attendance) {
            return self::Alpha;
        }

        if ($attendance->status === AttendanceStatus::Alpha) {
            return self::Alpha;
        }

        if ($attendance->type === AttendanceType::Sick) {
            return self::Sick;
        }

        if ($attendance->type === AttendanceType::Permission) {
            return $attendance->verification_status === VerificationStatus::NoDone
                ? self::Alpha
                : self::Permission;
        }

        if (! $attendance->clock_out_time) {
            return $attendance->status === AttendanceStatus::Late
                ? self::Late
                : self::Working;
        }

        return match ($attendance->status) {
            AttendanceStatus::Late => self::Late,
            AttendanceStatus::LateOut => self::LateOut,
            default => self::OnTime,
        };
    }

    public function filterKey(): string
    {
        return match ($this) {
            self::OnTime, self::Working, self::LateOut => 'hadir',
            self::Late => 'telat',
            self::Alpha => 'alfa',
            self::Sick => 'sakit',
            self::Permission => 'izin',
        };
    }
}
