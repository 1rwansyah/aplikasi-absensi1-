<?php

namespace App\Services;

use App\Models\Setting;
use App\Support\TimeFormat;

class AttendanceSettingsService
{
    public function current(): Setting
    {
        return Setting::current();
    }

    public function update(array $data): void
    {
        Setting::current()->update([
            'office_start' => TimeFormat::storage($data['office_start']),
            'late_limit' => TimeFormat::storage($data['late_limit']),
            'clock_out_start' => TimeFormat::storage($data['clock_out_start']),
            'clock_out_limit' => TimeFormat::storage($data['clock_out_limit']),
            'night_detect_from' => TimeFormat::storage($data['night_detect_from']),
            'night_office_start' => TimeFormat::storage($data['night_office_start']),
            'night_late_limit' => TimeFormat::storage($data['night_late_limit']),
            'night_clock_out_start' => TimeFormat::storage($data['night_clock_out_start']),
            'night_clock_out_limit' => TimeFormat::storage($data['night_clock_out_limit']),
            'office_latitude' => $data['office_latitude'],
            'office_longitude' => $data['office_longitude'],
            'attendance_radius_meters' => (int) $data['attendance_radius_meters'],
        ]);
    }
}
