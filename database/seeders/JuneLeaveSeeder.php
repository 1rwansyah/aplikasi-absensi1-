<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * June 2026 Leave/Sick Test Data
 * ─────────────────────────────────────────────────────────────────────────────
 * Random leave/sick records for testing payroll report format
 * ─────────────────────────────────────────────────────────────────────────────
 */
class JuneLeaveSeeder extends Seeder
{
    // Sample leave dates for different employees
    private const LEAVE_DATA = [
        // Rahmat Tomy - Sakit 2 hari
        ['email' => 'rahmat@example.com', 'dates' => ['2026-06-03', '2026-06-04'], 'type' => 'sick'],
        // Budi Santoso - Izin 1 hari
        ['email' => 'budi@example.com', 'dates' => ['2026-06-10'], 'type' => 'permission'],
        // Siti Aminah - Sakit 1 hari
        ['email' => 'siti@example.com', 'dates' => ['2026-06-15'], 'type' => 'sick'],
        // Andi Pratama - Izin 2 hari
        ['email' => 'andi@example.com', 'dates' => ['2026-06-20', '2026-06-21'], 'type' => 'permission'],
        // Dewi Lestari - Sakit 1 hari
        ['email' => 'dewi@example.com', 'dates' => ['2026-06-25'], 'type' => 'sick'],
    ];

    public function run(): void
    {
        $period_year = 2026;
        $period_month = 6;

        // Get work days from WorkCalendar
        $workDays = $this->getWorkDates($period_year, $period_month);

        foreach (self::LEAVE_DATA as $leaveData) {
            $employee = Employee::where('email', $leaveData['email'])->first();
            if (!$employee || !$employee->user_id) {
                continue;
            }

            foreach ($workDays as $dateStr) {
                $this->seedAttendance($employee->user_id, $dateStr, $leaveData);
            }
        }
    }

    private function seedAttendance(int $userId, string $dateStr, array $leaveData): void
    {
        $isLeaveDay = in_array($dateStr, $leaveData['dates']);
        $type = $leaveData['type'];

        if ($isLeaveDay) {
            $attendanceType = $type === 'sick' ? AttendanceType::Sick : AttendanceType::Permission;
            $status = $type === 'sick' ? AttendanceStatus::Sick : AttendanceStatus::Permission;

            Attendance::updateOrCreate(
                ['user_id' => $userId, 'date' => $dateStr],
                [
                    'type' => $attendanceType,
                    'status' => $status,
                    'clock_in_time' => null,
                    'clock_out_time' => null,
                    'clock_in_report' => $type === 'sick' ? 'Sakit - seeder' : 'Izin - seeder',
                    'clock_out_report' => null,
                    'overtime_hours' => 0,
                    'verification_status' => VerificationStatus::Done,
                    'leave_note' => $type === 'sick' ? 'Sakit tidak masuk kerja' : 'Izin keperluan pribadi',
                ]
            );
        } else {
            // Regular attendance for non-leave days
            Attendance::updateOrCreate(
                ['user_id' => $userId, 'date' => $dateStr],
                [
                    'type' => AttendanceType::Regular,
                    'status' => AttendanceStatus::OnTime,
                    'clock_in_time' => '09:00:00',
                    'clock_out_time' => '18:00:00',
                    'clock_in_report' => 'Hadir reguler',
                    'clock_out_report' => 'Hadir reguler',
                    'overtime_hours' => 0,
                ]
            );
        }
    }

    private function getWorkDates(int $year, int $month): array
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $dates = [];
        while ($startDate->lte($endDate)) {
            $dayOfWeek = $startDate->dayOfWeek; // 0 = Sunday, 6 = Saturday
            $dateStr = $startDate->toDateString();

            // Check if it's a work day (Mon-Sat, not Sunday, and not a holiday)
            if ($dayOfWeek !== 0) {
                $isHoliday = WorkCalendar::where('date', $dateStr)
                    ->where('type', 'holiday')
                    ->exists();

                if (!$isHoliday) {
                    $dates[] = $dateStr;
                }
            }

            $startDate->addDay();
        }

        return $dates;
    }
}
