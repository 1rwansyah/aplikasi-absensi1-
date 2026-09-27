<?php

namespace App\Services;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\WorkCalendarType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSchedule;
use App\Support\AppTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MarkAbsentAlphaService
{
    /** @var list<string> */
    private const SKIP_SCHEDULE_CODES = ['security', 'engineering', 'off'];

    public function __construct(
        private readonly EmployeeScheduleService $employeeSchedules,
        private readonly WorkCalendarService $workCalendar,
    ) {}

    /**
     * @return array{created: int, skipped_existing: int, skipped_ineligible: int, dates: int}
     */
    public function markForDate(CarbonInterface $date): array
    {
        $day = Carbon::parse($date->toDateString(), AppTime::timezone())->startOfDay();

        if (! $this->isMarkableCalendarDay($day)) {
            return [
                'created' => 0,
                'skipped_existing' => 0,
                'skipped_ineligible' => 0,
                'dates' => 0,
            ];
        }

        $created = 0;
        $skippedExisting = 0;
        $skippedIneligible = 0;

        foreach ($this->activeEmployees() as $employee) {
            if (! $employee->user_id) {
                $skippedIneligible++;

                continue;
            }

            $schedule = $this->employeeSchedules->getScheduleForDate($employee, $day);

            if (! $this->isRegularDaySchedule($employee, $schedule)) {
                $skippedIneligible++;

                continue;
            }

            $existing = Attendance::query()
                ->forUserOnDate($employee->user_id, $day)
                ->first();

            if ($existing) {
                $skippedExisting++;

                continue;
            }

            Attendance::create([
                'user_id' => $employee->user_id,
                'date' => $day->toDateString(),
                'type' => AttendanceType::Regular,
                'shift' => AttendanceShift::Day,
                'work_schedule_id' => $schedule->id,
                'clock_in_time' => null,
                'clock_out_time' => null,
                'status' => AttendanceStatus::Alpha,
                'overtime_hours' => 0,
            ]);

            $created++;
        }

        return [
            'created' => $created,
            'skipped_existing' => $skippedExisting,
            'skipped_ineligible' => $skippedIneligible,
            'dates' => 1,
        ];
    }

    /**
     * @return array{created: int, skipped_existing: int, skipped_ineligible: int, dates: int}
     */
    public function markForRange(CarbonInterface $from, CarbonInterface $to): array
    {
        $start = Carbon::parse($from->toDateString(), AppTime::timezone())->startOfDay();
        $end = Carbon::parse($to->toDateString(), AppTime::timezone())->startOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $totals = [
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_ineligible' => 0,
            'dates' => 0,
        ];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $result = $this->markForDate($day);
            $totals['created'] += $result['created'];
            $totals['skipped_existing'] += $result['skipped_existing'];
            $totals['skipped_ineligible'] += $result['skipped_ineligible'];
            $totals['dates'] += $result['dates'];
        }

        return $totals;
    }

    public function isPastDailyCutoff(?CarbonInterface $now = null): bool
    {
        $now ??= AppTime::now();

        return $now->format('H:i:s') >= '23:00:00';
    }

    public function isMarkableCalendarDay(CarbonInterface $date): bool
    {
        $type = $this->workCalendar->dayTypeForDate(
            Carbon::parse($date->toDateString(), AppTime::timezone())
        );

        return in_array($type, [WorkCalendarType::FullDay, WorkCalendarType::HalfDay], true);
    }

    public function isRegularDaySchedule(Employee $employee, WorkSchedule $schedule): bool
    {
        if (strcasecmp((string) $employee->staff, 'Security') === 0) {
            return false;
        }

        if ($schedule->is_off) {
            return false;
        }

        if (in_array($schedule->code, self::SKIP_SCHEDULE_CODES, true)) {
            return false;
        }

        $shift = $schedule->toShiftSchedule();

        if ($shift->isOvernightClockOut() || $shift->opensClockOutOnNextDutyDay()) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<int, Employee>
     */
    private function activeEmployees(): Collection
    {
        return Employee::query()
            ->with('defaultWorkSchedule')
            ->where('employment_status', 'active')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->get();
    }
}
