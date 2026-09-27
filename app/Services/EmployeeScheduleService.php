<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\CarbonInterface;

class EmployeeScheduleService
{
    public function getScheduleForDate(Employee $employee, CarbonInterface $date): WorkSchedule
    {
        $assignment = $employee->schedules()
            ->with('workSchedule')
            ->whereDate('work_date', $date)
            ->first();

        if ($assignment?->workSchedule) {
            return $assignment->workSchedule;
        }

        if ($employee->default_work_schedule_id) {
            $defaultSchedule = $employee->defaultWorkSchedule;

            if ($defaultSchedule) {
                if ($defaultSchedule->code === 'engineering' && $date->isSunday()) {
                    return WorkSchedule::query()->where('code', 'off')->firstOrFail();
                }

                return $defaultSchedule;
            }
        }

        if (strcasecmp((string) $employee->staff, 'Security') === 0) {
            return WorkSchedule::where('code', 'security')->firstOrFail();
        }

        return $this->regularSchedule();
    }

    public function getTodaySchedule(Employee $employee): WorkSchedule
    {
        return $this->getScheduleForDate($employee, today());
    }

    public function isOffDay(Employee $employee, CarbonInterface $date): bool
    {
        return $this->getScheduleForDate($employee, $date)->is_off;
    }

    /**
     * Admin may log Sunday overtime for Engineering even though self-service treats Sunday as off.
     */
    public function allowsAdminManualAttendance(Employee $employee, CarbonInterface $date): bool
    {
        if (! $this->isOffDay($employee, $date)) {
            return true;
        }

        return $this->isImplicitEngineeringSundayOff($employee, $date);
    }

    /**
     * Prefer Engineering schedule (not off) when admin records Sunday overtime.
     */
    public function scheduleForAdminManualAttendance(Employee $employee, CarbonInterface $date): WorkSchedule
    {
        $schedule = $this->getScheduleForDate($employee, $date);

        if ($schedule->is_off && $this->isImplicitEngineeringSundayOff($employee, $date)) {
            $engineering = $employee->defaultWorkSchedule;

            if ($engineering?->code === 'engineering') {
                return $engineering;
            }

            return WorkSchedule::query()->where('code', 'engineering')->firstOrFail();
        }

        return $schedule;
    }

    /**
     * Sunday off that comes only from the Engineering default rule (no explicit assignment).
     */
    public function isImplicitEngineeringSundayOff(Employee $employee, CarbonInterface $date): bool
    {
        if (! $date->isSunday()) {
            return false;
        }

        $hasExplicitAssignment = $employee->schedules()
            ->whereDate('work_date', $date)
            ->exists();

        if ($hasExplicitAssignment) {
            return false;
        }

        $employee->loadMissing('defaultWorkSchedule');

        return $employee->defaultWorkSchedule?->code === 'engineering';
    }

    public function regularSchedule(): WorkSchedule
    {
        return WorkSchedule::where('code', 'regular')->firstOrFail();
    }
}
