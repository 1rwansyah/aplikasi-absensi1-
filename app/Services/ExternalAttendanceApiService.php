<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Support\AppTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ExternalAttendanceApiService
{
    public function parseDate(?string $date): Carbon
    {
        $value = filled($date) ? $date : AppTime::today()->toDateString();

        try {
            return Carbon::createFromFormat('Y-m-d', $value, AppTime::timezone())->startOfDay();
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'date' => 'The date must be a valid YYYY-MM-DD value.',
            ]);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function listForDate(Carbon $date): Collection
    {
        return Attendance::query()
            ->with(['user.employee'])
            ->whereDate('date', $date->toDateString())
            ->orderBy('id')
            ->get()
            ->map(fn (Attendance $attendance) => $this->transformAttendance($attendance));
    }

    /**
     * @return array{employee: Employee, payload: array<string, mixed>}
     */
    public function employeeStatus(string $identifier, Carbon $date): array
    {
        $employee = $this->findEmployee($identifier);

        if ($employee === null) {
            abort(404, 'Employee not found.');
        }

        $attendance = Attendance::query()
            ->with(['user.employee'])
            ->where('user_id', $employee->user_id)
            ->whereDate('date', $date->toDateString())
            ->first();

        if ($attendance === null) {
            return [
                'employee' => $employee,
                'payload' => $this->emptyPayload($employee, $date),
            ];
        }

        return [
            'employee' => $employee,
            'payload' => $this->transformAttendance($attendance),
        ];
    }

    public function findEmployee(string $identifier): ?Employee
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        return Employee::query()->with('user')->where('employee_code', $identifier)->first()
            ?? Employee::query()->with('user')->where('nik', $identifier)->first()
            ?? Employee::query()->with('user')->where('email', $identifier)->first()
            ?? Employee::query()
                ->with('user')
                ->whereHas('user', fn ($query) => $query->where('email', $identifier))
                ->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function transformAttendance(Attendance $attendance): array
    {
        $employee = $attendance->user?->employee;
        $status = $attendance->status?->value;
        $present = $status !== null && $status !== AttendanceStatus::Alpha->value;

        return [
            'employee_code' => $employee?->employee_code,
            'name' => $employee?->name ?? $attendance->user?->name,
            'email' => $attendance->user?->email ?? $employee?->email,
            'date' => $attendance->date?->toDateString(),
            'present' => $present,
            'status' => $status,
            'type' => $attendance->type?->value,
            'clock_in' => $this->formatTime($attendance->clock_in_time),
            'clock_out' => $this->formatTime($attendance->clock_out_time),
            'verification_status' => $attendance->verification_status?->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function emptyPayload(Employee $employee, Carbon $date): array
    {
        return [
            'employee_code' => $employee->employee_code,
            'name' => $employee->name,
            'email' => $employee->user?->email ?? $employee->email,
            'date' => $date->toDateString(),
            'present' => false,
            'status' => 'not_recorded',
            'type' => null,
            'clock_in' => null,
            'clock_out' => null,
            'verification_status' => null,
        ];
    }

    private function formatTime(mixed $time): ?string
    {
        if ($time === null || $time === '') {
            return null;
        }

        $value = (string) $time;

        if (preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) === 1) {
            return strlen($value) === 5 ? $value.':00' : substr($value, 0, 8);
        }

        try {
            return Carbon::parse($value, AppTime::timezone())->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
