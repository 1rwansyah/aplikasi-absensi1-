<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Carbon;

class WhatsappAttendanceReportService
{
    private const DIVISIONS = [
        'STAFF OFFICE' => [
            'Office',
            'Sekretaris Direktur',
        ],

        'LEADER' => [
            'Leader',
        ],

        'STAFF IT' => [
            'IT',
        ],

        'STAFF ADMIN MEDSOS' => [
            'Admin Medsos',
        ],

        'STAFF LAINNYA' => [
            null,
            'Design',
            'Editor',
            'Engineering',
            'Survey & Acara',
            'Content Creator',
            'TV Rakyat',
        ],

        'STAFF RESTO' => [
            'Kitchen',
        ],

        'OB, SECURITY & RECEPTIONIST' => [
            'OB',
            'Security',
            'Resepsionis',
        ],
    ];

    private const HIDE_POSITION_DIVISIONS = [
        'STAFF ADMIN MEDSOS',
    ];

    public function generate(?Carbon $date = null, string $type = 'masuk'): string
    {
        $date ??= today();

        $employees = Employee::with([
            'user.attendances' => fn ($query) => $query->whereDate('date', $date),
            'schedules.workSchedule' => fn ($query) => $query,
        ])
            ->where('employment_status', '!=', 'resigned')
            ->orderByRaw('employee_code + 0 asc')
            ->get();

        $titleType = $type === 'pulang' ? 'PULANG' : 'MASUK';

        $message = "📋 *ABSENSI {$titleType} KARYAWAN*\n";
        $message .= "*PT. AMAL BENCANA RAKYAT INDONESIA*\n";
        $message .= 'Hari/Tanggal: '.$date->translatedFormat('l, d-m-Y')."\n\n";
        $message .= "*Keterangan:*\n";
        $message .= "H = Hadir | I = Izin | S = Sakit | A = Alpha | T = Telat\n\n";

        $counter = 1;

        foreach (self::DIVISIONS as $title => $staffTypes) {
            $divisionEmployees = $employees
                ->filter(fn ($employee) => in_array($employee->staff, $staffTypes, true))
                ->values();

            if ($divisionEmployees->isEmpty()) {
                continue;
            }

            $message .= "*{$title}*\n";

            foreach ($divisionEmployees as $employee) {
                $attendance = $employee->user?->attendances->first();

                $status = $this->attendanceCode($employee, $attendance, $type, $date);

                $message .= $counter.'. '.$employee->name;

                if (
                    ! in_array($title, self::HIDE_POSITION_DIVISIONS, true)
                    && filled($employee->position)
                ) {
                    $message .= ' - '.$employee->position;
                }

                $message .= ' ('.$status.")\n";

                $counter++;
            }

            $message .= "---\n\n";
        }

        return trim($message);
    }

    private function attendanceCode(Employee $employee, $attendance, string $type, Carbon $date): string
    {
        $schedule = $employee->schedules
            ->first(fn ($schedule) => $schedule->work_date->isSameDay($date));

        if ($schedule?->workSchedule?->is_off) {
            return 'OFF';
        }

        if (! $attendance) {
            if ($type === 'masuk') {
                return '';
            }

            return now()->hour >= 14 ? 'A' : '';
        }

        $status = $attendance->status instanceof \BackedEnum
            ? $attendance->status->value
            : $attendance->status;

        if ($status === 'permission') {
            return 'I';
        }

        if ($status === 'sick') {
            return 'S';
        }

        if ($type === 'masuk') {
            if (! $attendance->clock_in_time) {
                return '';
            }

            return $status === 'late' ? 'T' : 'H';
        }

        if ($type === 'pulang') {
            if ($attendance->clock_out_time) {
                return $status === 'early_out' ? 'T' : 'H';
            }

            if ($attendance->clock_in_time) {
                return 'H';
            }

            return 'A';
        }

        return '';
    }
}
