<?php

namespace App\Services;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Exceptions\AdminAttendanceLockedException;
use App\Models\Attendance;
use App\Models\Payroll;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Support\AppTime;
use App\Support\TimeFormat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdminAttendanceService
{
    public function __construct(
        private readonly AttendanceService $attendanceService,
        private readonly EmployeeScheduleService $employeeSchedules,
    ) {}

    /**
     * @param  Collection<int, Attendance>  $attendances
     * @return list<int>
     */
    public function lockedAttendanceIds(Collection $attendances): array
    {
        return $attendances
            ->filter(fn (Attendance $attendance) => $this->isLockedByPaidPayroll($attendance))
            ->pluck('id')
            ->all();
    }

    public function isLockedByPaidPayroll(Attendance $attendance, ?Carbon $date = null): bool
    {
        return $this->isPaidPayrollLocked($attendance->user?->employee?->id, $date ?? $attendance->date);
    }

    public function isLockedByPaidPayrollForUser(User $user, Carbon $date): bool
    {
        return $this->isPaidPayrollLocked($user->employee?->id, $date);
    }

    public function assertCreateAllowed(User $user, Carbon $date): void
    {
        if ($this->isLockedByPaidPayrollForUser($user, $date)) {
            throw new AdminAttendanceLockedException(
                'Absensi manual tidak dapat ditambahkan karena payroll periode '
                .$date->translatedFormat('F Y')
                .' sudah ditandai lunas.'
            );
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function buildCreateProbe(array $data): Attendance
    {
        $date = Carbon::parse($data['date'], AppTime::timezone())->startOfDay();
        $type = AttendanceType::from($data['type']);
        $shift = isset($data['shift']) ? AttendanceShift::from($data['shift']) : AttendanceShift::Day;

        $workSchedule = $this->resolveWorkScheduleForDate(
            isset($data['user_id']) ? (int) $data['user_id'] : null,
            $date,
        );

        $probe = new Attendance([
            'user_id' => (int) $data['user_id'],
            'date' => $date->toDateString(),
            'type' => $type,
            'shift' => $shift,
            'work_schedule_id' => $workSchedule?->is_off ? null : $workSchedule?->id,
            'clock_in_time' => $data['clock_in_time'] ?? null,
            'clock_out_time' => $data['clock_out_time'] ?? null,
        ]);

        if ($workSchedule !== null && ! $workSchedule->is_off) {
            $probe->setRelation('workSchedule', $workSchedule);
        }

        return $probe;
    }

    private function resolveWorkScheduleForDate(?int $userId, Carbon $date): ?WorkSchedule
    {
        if ($userId === null) {
            return null;
        }

        $employee = User::query()->with('employee.defaultWorkSchedule')->find($userId)?->employee;

        if ($employee === null) {
            return null;
        }

        return $this->employeeSchedules->scheduleForAdminManualAttendance($employee, $date);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor, string $reason): Attendance
    {
        $employeeUser = User::query()->with('employee')->findOrFail($data['user_id']);
        $clockInDate = Carbon::parse($data['clock_in_date'], AppTime::timezone())->startOfDay();
        $clockOutDate = $this->parseOptionalDate($data['clock_out_date'] ?? null);

        $this->assertCreateAllowed($employeeUser, $clockInDate);

        $type = AttendanceType::from($data['type']);
        $shift = isset($data['shift']) ? AttendanceShift::from($data['shift']) : AttendanceShift::Day;

        $schedule = $employeeUser->employee
            ? $this->employeeSchedules->scheduleForAdminManualAttendance($employeeUser->employee, $clockInDate)
            : null;
        $workScheduleId = ($schedule !== null && ! $schedule->is_off) ? $schedule->id : null;

        $attendance = new Attendance([
            'user_id' => $employeeUser->id,
            'date' => $clockInDate->toDateString(),
            'type' => $type,
            'shift' => $shift,
            'work_schedule_id' => $workScheduleId,
            'clock_in_time' => $data['clock_in_time'] ?? null,
            'clock_out_time' => $data['clock_out_time'] ?? null,
            'status' => $data['status'],
            'clock_in_report' => $data['clock_in_report'] ?? null,
            'clock_out_report' => $data['clock_out_report'] ?? null,
            'leave_note' => $data['leave_note'] ?? null,
        ]);

        if ($attendance->isRegular()) {
            $attendance->overtime_hours = $this->recalculateOvertimeHours($attendance, $clockOutDate);
        } else {
            $attendance->overtime_hours = 0;
            $attendance->verification_status = VerificationStatus::Done;
            $attendance->verified_by = $actor->id;
            $attendance->verified_at = now();
        }

        $attendance->save();

        ActivityLogService::logDataChange(
            $actor,
            'create',
            "Tambah absensi manual {$employeeUser->name} ({$clockInDate->toDateString()})",
            $attendance,
            [],
            $this->snapshot($attendance, $clockOutDate?->toDateString()),
            $reason,
        );

        return $attendance;
    }

    private function isPaidPayrollLocked(?int $employeeId, Carbon $date): bool
    {
        if ($employeeId === null) {
            return false;
        }

        return Payroll::query()
            ->where('employee_id', $employeeId)
            ->where('period_month', $date->month)
            ->where('period_year', $date->year)
            ->where('status', 'paid')
            ->exists();
    }

    public function assertEditable(Attendance $attendance, ?Carbon $newDate = null): void
    {
        if ($this->isLockedByPaidPayroll($attendance)) {
            throw new AdminAttendanceLockedException(
                'Absensi tidak dapat diedit karena payroll periode '
                .$attendance->date->translatedFormat('F Y')
                .' sudah ditandai lunas.'
            );
        }

        if ($newDate !== null && ! $newDate->isSameDay($attendance->date)) {
            if ($this->isPaidPayrollLocked($attendance->user?->employee?->id, $newDate)) {
                throw new AdminAttendanceLockedException(
                    'Absensi tidak dapat dipindah ke tanggal '
                    .$newDate->translatedFormat('d F Y')
                    .' karena payroll periode '
                    .$newDate->translatedFormat('F Y')
                    .' sudah ditandai lunas.'
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Attendance $attendance, array $data, User $actor, string $reason): Attendance
    {
        $clockInDate = Carbon::parse($data['clock_in_date'], AppTime::timezone())->startOfDay();
        $clockOutDate = $this->parseOptionalDate($data['clock_out_date'] ?? null);

        $this->assertEditable($attendance, $clockInDate);

        $attendance->refresh();
        $before = $this->snapshot($attendance, $this->inferredClockOutDate($attendance));

        $type = AttendanceType::from($data['type']);

        $attributes = [
            'date' => $clockInDate->toDateString(),
            'type' => $type,
            'clock_in_time' => $data['clock_in_time'] ?? null,
            'clock_out_time' => $data['clock_out_time'] ?? null,
            'status' => $data['status'],
            'clock_in_report' => $data['clock_in_report'] ?? null,
            'clock_out_report' => $data['clock_out_report'] ?? null,
            'leave_note' => $data['leave_note'] ?? null,
        ];

        $attendance->fill($attributes);

        if ($attendance->isRegular()) {
            $attendance->overtime_hours = $this->recalculateOvertimeHours($attendance, $clockOutDate);
        } else {
            $attendance->overtime_hours = 0;
            $attendance->verification_status = VerificationStatus::Done;
            $attendance->verified_by = $actor->id;
            $attendance->verified_at = now();
        }

        $attendance->save();

        $after = $this->snapshot($attendance->fresh(), $clockOutDate?->toDateString());

        ActivityLogService::logDataChange(
            $actor,
            'update',
            "Koreksi absensi {$attendance->user?->name} ({$before['date']})",
            $attendance,
            $before,
            $after,
            $reason,
        );

        return $attendance;
    }

    public function inferredClockOutDate(Attendance $attendance): ?string
    {
        if (! $attendance->clock_out_time) {
            return null;
        }

        $moment = $this->attendanceService->clockOutMomentFor($attendance);

        return $moment?->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    public function schedulePreview(User $user, Carbon $date, ?Attendance $attendance = null): array
    {
        $workSchedule = $this->resolveScheduleForPreview($user, $date, $attendance);
        $shiftSchedule = $workSchedule->is_off ? null : $workSchedule->toShiftSchedule();

        $nextDayClockOut = false;
        $isOvernight = false;

        if ($shiftSchedule !== null) {
            $nextDayClockOut = $shiftSchedule->opensClockOutOnNextDutyDay();
            $isOvernight = $nextDayClockOut;
        }

        $clockIn = $workSchedule->clock_in_start !== null
            ? TimeFormat::display($workSchedule->clock_in_start)
            : null;
        $clockOut = $workSchedule->clock_out_start !== null
            ? TimeFormat::display($workSchedule->clock_out_start)
            : null;

        $clockOutDate = $date->toDateString();
        if ($nextDayClockOut) {
            $clockOutDate = $date->copy()->addDay()->toDateString();
        }

        $workHours = null;
        if ($clockIn !== null && $clockOut !== null) {
            $workHours = $nextDayClockOut
                ? "{$clockIn} - {$clockOut} (+1 hari)"
                : "{$clockIn} - {$clockOut}";
        }

        return [
            'schedule_name' => $workSchedule->name,
            'schedule_code' => $workSchedule->code,
            'is_off' => $workSchedule->is_off,
            'is_overnight' => $isOvernight,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'next_day_clock_out' => $nextDayClockOut,
            'shift_type' => $isOvernight ? 'Overnight' : 'Same Day',
            'work_hours' => $workHours,
            'defaults' => [
                'clock_in_time' => $clockIn,
                'clock_out_time' => $clockOut,
                'clock_out_date' => $clockOutDate,
            ],
        ];
    }

    private function resolveScheduleForPreview(User $user, Carbon $date, ?Attendance $attendance): WorkSchedule
    {
        if ($attendance?->work_schedule_id) {
            $stored = WorkSchedule::query()->find($attendance->work_schedule_id);

            if ($stored !== null) {
                return $stored;
            }
        }

        $employee = $user->employee;

        if ($employee === null) {
            return $this->employeeSchedules->regularSchedule();
        }

        return $this->employeeSchedules->scheduleForAdminManualAttendance($employee, $date);
    }

    /**
     * @return array<string, mixed>
     */
    public function editFormData(Attendance $attendance): array
    {
        $attendance->loadMissing('user.employee', 'workSchedule');

        $employee = $attendance->user?->employee;
        $employeeLabel = $employee
            ? "{$employee->name} ({$employee->employee_code})"
            : ($attendance->user?->name ?? '—');

        $clockOutDate = $this->inferredClockOutDate($attendance) ?? $attendance->date->toDateString();

        return [
            'id' => $attendance->id,
            'update_url' => route('admin.attendance.update', $attendance),
            'user_id' => $attendance->user_id,
            'employee_label' => $employeeLabel,
            'type' => $attendance->type->value,
            'status' => $attendance->status->value,
            'clock_in_date' => $attendance->date->toDateString(),
            'clock_in_time' => $attendance->clock_in_time ? substr((string) $attendance->clock_in_time, 0, 5) : null,
            'clock_out_date' => $attendance->clock_out_time ? $clockOutDate : null,
            'clock_out_time' => $attendance->clock_out_time ? substr((string) $attendance->clock_out_time, 0, 5) : null,
            'clock_in_report' => $attendance->clock_in_report,
            'clock_out_report' => $attendance->clock_out_report,
            'leave_note' => $attendance->leave_note,
            'work_schedule' => $attendance->workSchedule?->name,
        ];
    }

    public function recalculateOvertimeHours(Attendance $attendance, ?Carbon $clockOutDate = null): float
    {
        if (! $attendance->clock_out_time) {
            return 0.0;
        }

        $clockOutMoment = $this->attendanceService->clockOutMomentFor($attendance, $clockOutDate);

        if ($clockOutMoment === null) {
            return 0.0;
        }

        return $this->attendanceService->overtimeHoursFor($attendance, $clockOutMoment);
    }

    public function assertManualClockOutAfterClockIn(
        Carbon $clockInDate,
        ?string $clockInTime,
        ?Carbon $clockOutDate,
        ?string $clockOutTime,
    ): void {
        if ($clockInTime === null || $clockOutTime === null || $clockOutDate === null) {
            return;
        }

        if ($clockOutDate->lessThan($clockInDate)) {
            throw ValidationException::withMessages([
                'clock_out_date' => 'Tanggal pulang tidak boleh sebelum tanggal masuk.',
            ]);
        }

        $clockIn = Carbon::parse(
            $clockInDate->toDateString().' '.substr($clockInTime, 0, 8),
            AppTime::timezone(),
        );
        $clockOut = Carbon::parse(
            $clockOutDate->toDateString().' '.substr($clockOutTime, 0, 8),
            AppTime::timezone(),
        );

        if ($clockOut->lessThanOrEqualTo($clockIn)) {
            throw ValidationException::withMessages([
                'clock_out_time' => 'Waktu pulang harus setelah waktu masuk.',
            ]);
        }
    }

    public function assertClockOutAfterClockIn(Attendance $attendance): void
    {
        if (! $attendance->clock_in_time || ! $attendance->clock_out_time) {
            return;
        }

        $schedule = $this->attendanceService->clockOutScheduleFor($attendance);

        if ($schedule->opensClockOutOnNextDutyDay()) {
            $clockIn = $this->attendanceService->clockInMomentFor($attendance);
            $clockOut = $this->attendanceService->clockOutMomentFor($attendance);

            if ($clockOut !== null && $clockIn !== null && $clockOut->lessThanOrEqualTo($clockIn)) {
                throw ValidationException::withMessages([
                    'clock_out_time' => 'Jam pulang harus setelah jam masuk.',
                ]);
            }

            return;
        }

        $clockIn = substr((string) $attendance->clock_in_time, 0, 8);
        $clockOut = substr((string) $attendance->clock_out_time, 0, 8);

        if ($clockOut <= $clockIn) {
            throw ValidationException::withMessages([
                'clock_out_time' => 'Jam pulang harus setelah jam masuk.',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(Attendance $attendance, ?string $clockOutDate = null): array
    {
        return [
            'type' => $attendance->type->value,
            'date' => $attendance->date->toDateString(),
            'clock_in_time' => $attendance->clock_in_time ? substr((string) $attendance->clock_in_time, 0, 8) : null,
            'clock_out_date' => $clockOutDate,
            'clock_out_time' => $attendance->clock_out_time ? substr((string) $attendance->clock_out_time, 0, 8) : null,
            'status' => $attendance->status->value,
            'clock_in_report' => $attendance->clock_in_report,
            'clock_out_report' => $attendance->clock_out_report,
            'leave_note' => $attendance->leave_note,
            'overtime_hours' => $attendance->overtime_hours,
        ];
    }

    private function parseOptionalDate(?string $date): ?Carbon
    {
        if ($date === null || $date === '') {
            return null;
        }

        return Carbon::parse($date, AppTime::timezone())->startOfDay();
    }
}
