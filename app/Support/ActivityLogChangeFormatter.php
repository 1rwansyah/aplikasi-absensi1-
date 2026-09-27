<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use Illuminate\Support\Str;

class ActivityLogChangeFormatter
{
    private const FIELD_LABELS = [
        'type' => 'Tipe',
        'date' => 'Tanggal',
        'clock_in_time' => 'Masuk',
        'clock_out_time' => 'Pulang',
        'status' => 'Status',
        'leave_note' => 'Catatan',
        'overtime_hours' => 'Lembur',
        'clock_in_report' => 'Laporan masuk',
        'clock_out_report' => 'Laporan pulang',
    ];

    private const FIELD_ORDER = [
        'type',
        'date',
        'clock_in_time',
        'clock_out_time',
        'status',
        'leave_note',
        'overtime_hours',
        'clock_in_report',
        'clock_out_report',
    ];

    /**
     * @param  array{reason?: string, before?: array<string, mixed>, after?: array<string, mixed>}  $payload
     */
    public function format(array $payload): string
    {
        $parts = [];

        if (! empty($payload['reason'])) {
            $parts[] = 'Alasan: '.$payload['reason'];
        }

        $before = $payload['before'] ?? [];
        $after = $payload['after'] ?? [];

        if ($before === []) {
            foreach ($this->orderedKeys($after) as $key) {
                $value = $after[$key] ?? null;
                if ($this->shouldSkipField($key, $value)) {
                    continue;
                }

                $parts[] = self::FIELD_LABELS[$key].': '.$this->formatValue($key, $value);
            }
        } else {
            foreach ($this->orderedKeys(array_merge($before, $after)) as $key) {
                $old = $before[$key] ?? null;
                $new = $after[$key] ?? null;

                if ($old == $new) {
                    continue;
                }

                $parts[] = self::FIELD_LABELS[$key].': '
                  .$this->formatValue($key, $old)
                  .' → '
                  .$this->formatValue($key, $new);
            }
        }

        return implode(' · ', $parts);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function orderedKeys(array $data): array
    {
        return array_values(array_filter(
            self::FIELD_ORDER,
            fn (string $key) => array_key_exists($key, $data),
        ));
    }

    private function shouldSkipField(string $key, mixed $value): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        if ($key === 'overtime_hours' && (float) $value === 0.0) {
            return true;
        }

        return false;
    }

    private function formatValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        return match ($key) {
            'type' => AttendanceType::tryFrom((string) $value)?->label() ?? (string) $value,
            'status' => AttendanceStatus::tryFrom((string) $value)?->label() ?? (string) $value,
            'clock_in_time', 'clock_out_time' => substr((string) $value, 0, 5),
            'overtime_hours' => (string) $value.' jam',
            'clock_in_report', 'clock_out_report' => Str::limit(trim(strip_tags((string) $value)), 40),
            default => (string) $value,
        };
    }
}
