<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Payroll Test Cases — Mei 2026
 * ─────────────────────────────────────────────────────────────────────────────
 * Work days Mei 2026  : 26 (Mon–Sat, excl. Sundays)
 * Shift setting       : office_start=09:00, late_limit=09:15
 *                       clock_out_start=18:00, clock_out_limit=18:00
 * Overtime starts     : after 18:00
 *
 * Case A — Rahmat Tomy   : Telat 7 hari (05-04..08, 05-11..12) → SP2 (50% Gaji Pokok)
 * Case B — Budi Santoso  : Alfa 2 hari (05-07, 05-14) → Potongan Absen+Izin
 * Case C — Siti Aminah   : Izin 2 hari (05-11, 05-18) → Potongan Absen+Izin
 * Case D — Andi Pratama  : Telat 3 hari (05-04, 05-05, 05-06) → SP1 (25%)
 * Case E — Dewi Lestari  : Lembur 2 hari (05-13, 05-20) clock_out=20:30
 *                          → Lembur allowance (~1.5 jam/hari × 2 = 3 jam total)
 * ─────────────────────────────────────────────────────────────────────────────
 */
class AttendanceSeeder extends Seeder
{
    // Tanggal telat untuk Case A (Rahmat) — 7 hari telat
    private const RAHMAT_LATE_DATES = ['2026-05-04', '2026-05-05', '2026-05-06', '2026-05-07', '2026-05-08', '2026-05-11', '2026-05-12'];

    // Tanggal alfa (tidak ada row attendance sama sekali) untuk Case B
    private const ALFA_DATES = ['2026-05-07', '2026-05-14'];

    // Tanggal izin untuk Case C
    private const IZIN_DATES = ['2026-05-11', '2026-05-18'];

    // Tanggal telat untuk Case D
    private const LATE_DATES = ['2026-05-04', '2026-05-05', '2026-05-06'];

    // Tanggal lembur untuk Case E — clock out 20:30, overtime starts 19:00 (1h buffer)
    // Overtime per hari = (20:30 - 19:00) = 1j 30m = 1.5 jam
    private const OVERTIME_DATES = ['2026-05-13', '2026-05-20'];

    public function run(): void
    {
        $period_year = 2026;
        $period_month = 5;

        // Ambil work days dari WorkCalendar (sudah di-seed sebelumnya)
        $workDays = $this->getWorkDates($period_year, $period_month);

        // Petakan 5 karyawan pertama ke test case berdasarkan email (deterministik)
        $cases = [
            'rahmat@example.com' => 'A',
            'budi@example.com' => 'B',
            'siti@example.com' => 'C',
            'andi@example.com' => 'D',
            'dewi@example.com' => 'E',
        ];

        foreach ($cases as $email => $case) {
            $employee = Employee::where('email', $email)->first();
            if (! $employee || ! $employee->user_id) {
                continue;
            }

            foreach ($workDays as $dateStr) {
                $this->seedAttendance($employee->user_id, $dateStr, $case);
            }
        }
    }

    private function seedAttendance(int $userId, string $dateStr, string $case): void
    {
        match ($case) {
            'A' => $this->caseA($userId, $dateStr),
            'B' => $this->caseB($userId, $dateStr),
            'C' => $this->caseC($userId, $dateStr),
            'D' => $this->caseD($userId, $dateStr),
            'E' => $this->caseE($userId, $dateStr),
        };
    }

    /**
     * Case A — Rahmat Tomy: hadir penuh, telat 5 hari.
     * Expected: SP1 = 25% dari Gaji Pokok (late >= 3).
     */
    private function caseA(int $userId, string $dateStr): void
    {
        $isLate = in_array($dateStr, self::RAHMAT_LATE_DATES);

        Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $dateStr],
            [
                'type' => AttendanceType::Regular,
                'status' => $isLate ? AttendanceStatus::Late : AttendanceStatus::OnTime,
                'clock_in_time' => $isLate ? '09:30:00' : '09:00:00',
                'clock_out_time' => '18:00:00',
                'clock_in_report' => 'Seeder Case A',
                'clock_out_report' => 'Seeder Case A',
                'overtime_hours' => 0,
            ]
        );
    }

    /**
     * Case B — Alfa 2 hari (tidak ada row sama sekali untuk tanggal tersebut).
     * Expected: Potongan Absen+Izin = (TotalGross - Lembur - UangMakan) / 26 × 2
     */
    private function caseB(int $userId, string $dateStr): void
    {
        // Skip — tidak buat row attendance untuk tanggal alfa
        if (in_array($dateStr, self::ALFA_DATES)) {
            return;
        }

        Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $dateStr],
            [
                'type' => AttendanceType::Regular,
                'status' => AttendanceStatus::OnTime,
                'clock_in_time' => '09:00:00',
                'clock_out_time' => '18:00:00',
                'clock_in_report' => 'Seeder Case B',
                'clock_out_report' => 'Seeder Case B',
                'overtime_hours' => 0,
            ]
        );
    }

    /**
     * Case C — Izin 2 hari (type=permission, clock_in/out null).
     * Expected: Potongan Absen+Izin = (TotalGross - Lembur - UangMakan) / 26 × 2
     */
    private function caseC(int $userId, string $dateStr): void
    {
        if (in_array($dateStr, self::IZIN_DATES)) {
            Attendance::updateOrCreate(
                ['user_id' => $userId, 'date' => $dateStr],
                [
                    'type' => AttendanceType::Permission,
                    'status' => AttendanceStatus::Permission,
                    'clock_in_time' => null,
                    'clock_out_time' => null,
                    'clock_in_report' => 'Izin keperluan pribadi',
                    'clock_out_report' => null,
                    'overtime_hours' => 0,
                ]
            );

            return;
        }

        Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $dateStr],
            [
                'type' => AttendanceType::Regular,
                'status' => AttendanceStatus::OnTime,
                'clock_in_time' => '09:00:00',
                'clock_out_time' => '18:00:00',
                'clock_in_report' => 'Seeder Case C',
                'clock_out_report' => 'Seeder Case C',
                'overtime_hours' => 0,
            ]
        );
    }

    /**
     * Case D — Telat 3 hari (clock_in=09:30, status=late).
     * Expected: SP1 = 25% dari Gaji Pokok. Tidak ada Potongan Absen+Izin.
     */
    private function caseD(int $userId, string $dateStr): void
    {
        $isLate = in_array($dateStr, self::LATE_DATES);

        Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $dateStr],
            [
                'type' => AttendanceType::Regular,
                'status' => $isLate ? AttendanceStatus::Late : AttendanceStatus::OnTime,
                'clock_in_time' => $isLate ? '09:30:00' : '09:00:00',
                'clock_out_time' => '18:00:00',
                'clock_in_report' => 'Seeder Case D',
                'clock_out_report' => 'Seeder Case D',
                'overtime_hours' => 0,
            ]
        );
    }

    /**
     * Case E — Lembur 2 hari. Clock out 20:30, overtime starts after 19:00 (1h buffer).
     * Overtime per hari = (20:30 - 19:00) = 1j 30m = 1.5 jam.
     * Total overtime = 3 jam.
     * Expected: Lembur allowance muncul di payroll.
     */
    private function caseE(int $userId, string $dateStr): void
    {
        $isOvertime = in_array($dateStr, self::OVERTIME_DATES);

        Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $dateStr],
            [
                'type' => AttendanceType::Regular,
                'status' => AttendanceStatus::OnTime,
                'clock_in_time' => '09:00:00',
                'clock_out_time' => $isOvertime ? '20:30:00' : '18:00:00',
                'clock_in_report' => 'Seeder Case E',
                'clock_out_report' => 'Seeder Case E',
                'overtime_hours' => $isOvertime ? 1.5 : 0,
            ]
        );
    }

    /**
     * Ambil semua tanggal kerja dari WorkCalendar (FullDay + HalfDay) untuk bulan tertentu.
     * Jika WorkCalendar belum di-seed, fallback ke Senin–Sabtu.
     *
     * @return string[]
     */
    private function getWorkDates(int $year, int $month): array
    {
        $calendarDays = WorkCalendar::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        if ($calendarDays->isNotEmpty()) {
            return $calendarDays
                ->filter(fn ($d) => $d->type->weight() > 0)
                ->pluck('date')
                ->map(fn ($d) => $d instanceof Carbon ? $d->toDateString() : (string) $d)
                ->values()
                ->all();
        }

        // Fallback: Mon–Sat
        $dates = [];
        $cursor = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $cursor->copy()->endOfMonth();

        while ($cursor->lte($end)) {
            if ($cursor->dayOfWeek !== Carbon::SUNDAY) {
                $dates[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $dates;
    }
}
