<?php

namespace App\Services;

use App\Enums\WorkCalendarType;
use App\Models\WorkCalendar;
use App\Support\AppTime;
use App\Support\TimeFormat;
use Illuminate\Support\Carbon;

class WorkCalendarService
{
    public const DAY_SHIFT_FULL_DAY_CLOCK_OUT = '18:00:00';

    public const DAY_SHIFT_HALF_DAY_CLOCK_OUT = '14:00:00';

    public function entryForDate(Carbon $date): ?WorkCalendar
    {
        return WorkCalendar::query()
            ->whereDate('date', $date->toDateString())
            ->first();
    }

    public function isHoliday(Carbon $date): bool
    {
        return $this->entryForDate($date)?->type === WorkCalendarType::Holiday;
    }

    public function holidayLabel(?Carbon $date = null): ?string
    {
        $date ??= AppTime::today();
        $entry = $this->entryForDate($date);

        if (! $entry || $entry->type !== WorkCalendarType::Holiday) {
            return null;
        }

        return $entry->name ?: WorkCalendarType::Holiday->label();
    }

    public function holidayEntry(?Carbon $date = null): ?WorkCalendar
    {
        $date ??= AppTime::today();
        $entry = $this->entryForDate($date);

        return ($entry && $entry->type === WorkCalendarType::Holiday) ? $entry : null;
    }

    public function dayTypeForDate(?Carbon $date = null): ?WorkCalendarType
    {
        $date ??= AppTime::today();

        return $this->entryForDate($date)?->type;
    }

    /**
     * Earliest clock-out start for Day/office shift from work calendar.
     * null = no calendar floor (holiday: use shift settings as-is).
     */
    public function dayShiftMinimumClockOutStart(?Carbon $date = null): ?string
    {
        $date ??= AppTime::today();

        return match ($this->dayTypeForDate($date)) {
            WorkCalendarType::FullDay => self::DAY_SHIFT_FULL_DAY_CLOCK_OUT,
            WorkCalendarType::HalfDay => self::DAY_SHIFT_HALF_DAY_CLOCK_OUT,
            WorkCalendarType::Holiday => null,
            default => self::DAY_SHIFT_FULL_DAY_CLOCK_OUT,
        };
    }

    public function isHalfDay(?Carbon $date = null): bool
    {
        return $this->dayTypeForDate($date) === WorkCalendarType::HalfDay;
    }

    public function halfDayNotice(?Carbon $date = null): ?string
    {
        if (! $this->isHalfDay($date)) {
            return null;
        }

        $opensAt = TimeFormat::display(self::DAY_SHIFT_HALF_DAY_CLOCK_OUT);

        return "Hari kerja setengah hari — absen pulang dibuka pukul {$opensAt}.";
    }
}
