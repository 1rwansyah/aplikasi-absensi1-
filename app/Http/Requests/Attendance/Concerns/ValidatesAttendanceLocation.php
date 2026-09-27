<?php

namespace App\Http\Requests\Attendance\Concerns;

trait ValidatesAttendanceLocation
{
    protected function locationRules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric'],
            'device_type' => ['nullable', 'string', 'in:mobile,desktop'],
            'client_time' => ['nullable', 'numeric'],
            'attendance_location' => ['nullable', 'string', 'max:500'],
            'clock_in_location' => ['nullable', 'string', 'max:500'],
            'clock_out_location' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function locationMessages(): array
    {
        return [
            'latitude.required' => 'Lokasi GPS wajib untuk absensi.',
            'latitude.between' => 'Koordinat latitude tidak valid.',
            'longitude.required' => 'Lokasi GPS wajib untuk absensi.',
            'longitude.between' => 'Koordinat longitude tidak valid.',
            'attendance_location.max' => 'Alamat lokasi terlalu panjang.',
        ];
    }

    public function attendanceLocation(): ?string
    {
        $value = $this->validated('attendance_location')
            ?? $this->validated('clock_in_location')
            ?? $this->validated('clock_out_location');

        return filled($value) ? trim($value) : null;
    }

    public function clientTime(): ?int
    {
        return $this->validated('client_time');
    }
}
