<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\WorkCalendarType;
use App\Models\Attendance;
use App\Models\EmployeeSchedule;
use App\Models\Payroll;
use App\Models\WorkCalendar;
use App\Support\AppTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PayrollAttendanceDateBreakdown
{
    /**
     * @return array{
     *     present: list<array{date: string, label: string, note: string|null}>,
     *     late: list<array{date: string, label: string, note: string|null}>,
     *     absent: list<array{date: string, label: string, note: string|null}>,
     *     sick: list<array{date: string, label: string, note: string|null}>,
     *     leave: list<array{date: string, label: string, note: string|null}>
     * }
     */
    public function forPayroll(Payroll $payroll): array
    {
        $payroll->loadMissing('employee');

        $employee = $payroll->employee;
        $userId = $employee?->user_id;
        $month = (int) $payroll->period_month;
        $year = (int) $payroll->period_year;

        if (! $employee || ! $userId) {
            return $this->empty();
        }

        $base = Attendance::query()
            ->where('user_id', $userId)
            ->whereMonth('date', $month)
            ->whereYear('date', $year);

        $present = (clone $base)
            ->where('type', AttendanceType::Regular)
            ->whereNotNull('clock_in_time')
            ->orderBy('date')
            ->get(['date']);

        $late = (clone $base)
            ->where('status', AttendanceStatus::Late)
            ->orderBy('date')
            ->get(['date']);

        $approvedSick = (clone $base)
            ->approvedLeave()
            ->where('type', AttendanceType::Sick)
            ->orderBy('date')
            ->get(['date']);

        $rejectedSick = (clone $base)
            ->rejectedSick()
            ->orderBy('date')
            ->get(['date']);

        $leave = (clone $base)
            ->approvedLeave()
            ->where('type', AttendanceType::Permission)
            ->orderBy('date')
            ->get(['date']);

        $coveredDates = collect()
            ->merge($present->map(fn ($row) => $row->date->toDateString()))
            ->merge($approvedSick->map(fn ($row) => $row->date->toDateString()))
            ->merge($rejectedSick->map(fn ($row) => $row->date->toDateString()))
            ->merge($leave->map(fn ($row) => $row->date->toDateString()))
            ->unique()
            ->values();

        $expectedWorkDates = $this->expectedWorkDates($employee->id, $month, $year);
        $absentDates = $expectedWorkDates
            ->reject(fn (string $date) => $coveredDates->contains($date))
            ->values();

        return [
            'present' => $this->mapAttendanceDates($present),
            'late' => $this->mapAttendanceDates($late),
            'absent' => $absentDates->map(fn (string $date) => $this->entry($date))->all(),
            'sick' => collect(array_merge(
                $this->mapAttendanceDates($approvedSick, 'Disetujui'),
                $this->mapAttendanceDates($rejectedSick, 'Ditolak'),
            ))
                ->sortBy('date')
                ->values()
                ->all(),
            'leave' => $this->mapAttendanceDates($leave),
        ];
    }

    /**
     * @return Collection<int, string>
     */
    private function expectedWorkDates(int $employeeId, int $month, int $year): Collection
    {
        $calendarDates = WorkCalendar::query()
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->whereIn('type', [WorkCalendarType::FullDay, WorkCalendarType::HalfDay])
            ->orderBy('date')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date, AppTime::timezone())->toDateString());

        $offDates = EmployeeSchedule::query()
            ->where('employee_id', $employeeId)
            ->whereYear('work_date', $year)
            ->whereMonth('work_date', $month)
            ->whereHas('workSchedule', fn ($query) => $query->where('is_off', true))
            ->pluck('work_date')
            ->map(fn ($date) => Carbon::parse($date, AppTime::timezone())->toDateString())
            ->all();

        return $calendarDates
            ->reject(fn (string $date) => in_array($date, $offDates, true))
            ->values();
    }

    /**
     * @param  Collection<int, Attendance>  $rows
     * @return list<array{date: string, label: string, note: string|null}>
     */
    private function mapAttendanceDates(Collection $rows, ?string $note = null): array
    {
        return $rows
            ->map(fn (Attendance $row) => $this->entry($row->date->toDateString(), $note))
            ->values()
            ->all();
    }

    /**
     * @return array{date: string, label: string, note: string|null}
     */
    private function entry(string $date, ?string $note = null): array
    {
        return [
            'date' => $date,
            'label' => Carbon::parse($date, AppTime::timezone())->translatedFormat('j F Y'),
            'note' => $note,
        ];
    }

    /**
     * @return array{
     *     present: list<array{date: string, label: string, note: string|null}>,
     *     late: list<array{date: string, label: string, note: string|null}>,
     *     absent: list<array{date: string, label: string, note: string|null}>,
     *     sick: list<array{date: string, label: string, note: string|null}>,
     *     leave: list<array{date: string, label: string, note: string|null}>
     * }
     */
    private function empty(): array
    {
        return [
            'present' => [],
            'late' => [],
            'absent' => [],
            'sick' => [],
            'leave' => [],
        ];
    }
}
