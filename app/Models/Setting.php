<?php

namespace App\Models;

use App\DTOs\ShiftSchedule;
use App\Enums\AttendanceShift;
use App\Support\GeoDistance;
use App\Support\TimeFormat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'office_start',
        'late_limit',
        'clock_out_start',
        'clock_out_limit',
        'night_detect_from',
        'night_office_start',
        'night_late_limit',
        'night_clock_out_start',
        'night_clock_out_limit',
        'office_latitude',
        'office_longitude',
        'attendance_radius_meters',
    ];

    protected $casts = [
        'office_latitude' => 'float',
        'office_longitude' => 'float',
        'attendance_radius_meters' => 'integer',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'office_start' => '07:00:00',
            'late_limit' => '09:30:00',
            'clock_out_start' => '17:00:00',
            'clock_out_limit' => '19:00:00',
            'night_detect_from' => '17:00:00',
            'night_office_start' => '17:00:00',
            'night_late_limit' => '22:00:00',
            'night_clock_out_start' => '22:00:00',
            'night_clock_out_limit' => '07:00:00',
            'office_latitude' => -6.200000,
            'office_longitude' => 106.816666,
            'attendance_radius_meters' => 1000,
        ]);
    }

    public function hasGeofence(): bool
    {
        return $this->office_latitude !== null
            && $this->office_longitude !== null
            && $this->attendance_radius_meters > 0;
    }

    public function isWithinAttendanceRadius(float $latitude, float $longitude): bool
    {
        return GeoDistance::isWithinRadius(
            $latitude,
            $longitude,
            (float) $this->office_latitude,
            (float) $this->office_longitude,
            (int) $this->attendance_radius_meters,
        );
    }

    public function formattedAttendanceRadius(): string
    {
        $meters = (int) $this->attendance_radius_meters;

        if ($meters >= 1000 && $meters % 1000 === 0) {
            return ($meters / 1000).' KM';
        }

        return $meters.' meter';
    }

    public function detectShift(Carbon $clockIn): AttendanceShift
    {
        $from = TimeFormat::normalized($this->night_detect_from);

        return $clockIn->format('H:i:s') >= $from
            ? AttendanceShift::Night
            : AttendanceShift::Day;
    }

    public function scheduleFor(AttendanceShift $shift): ShiftSchedule
    {
        return $shift === AttendanceShift::Night
            ? $this->nightSchedule()
            : $this->daySchedule();
    }

    public function daySchedule(): ShiftSchedule
    {
        return new ShiftSchedule(
            officeStart: (string) $this->office_start,
            lateLimit: (string) $this->late_limit,
            clockOutStart: (string) $this->clock_out_start,
            clockOutLimit: (string) $this->clock_out_limit,
        );
    }

    public function nightSchedule(): ShiftSchedule
    {
        return new ShiftSchedule(
            officeStart: (string) $this->night_office_start,
            lateLimit: (string) $this->night_late_limit,
            clockOutStart: (string) $this->night_clock_out_start,
            clockOutLimit: (string) $this->night_clock_out_limit,
        );
    }

    public function formattedOfficeStart(): string
    {
        return $this->daySchedule()->formattedOfficeStart();
    }

    public function formattedLateLimit(): string
    {
        return $this->daySchedule()->formattedLateLimit();
    }

    public function formattedClockOutStart(): string
    {
        return $this->daySchedule()->formattedClockOutStart();
    }

    public function formattedClockOutLimit(): string
    {
        return $this->daySchedule()->formattedClockOutLimit();
    }

    public function formattedNightDetectFrom(): string
    {
        return TimeFormat::display($this->night_detect_from);
    }

    public function formattedNightOfficeStart(): string
    {
        return $this->nightSchedule()->formattedOfficeStart();
    }

    public function formattedNightLateLimit(): string
    {
        return $this->nightSchedule()->formattedLateLimit();
    }

    public function formattedNightClockOutStart(): string
    {
        return $this->nightSchedule()->formattedClockOutStart();
    }

    public function formattedNightClockOutLimit(): string
    {
        return $this->nightSchedule()->formattedClockOutLimit();
    }

    public function clockOutWindowDescription(): string
    {
        return $this->daySchedule()->clockOutWindowDescription();
    }
}
