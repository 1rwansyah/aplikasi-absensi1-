<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\WorkSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AssignSecurityWeekSchedules extends Command
{
    private const VALID_CODES = ['security', 'off'];

    private const DAYS_IN_WEEK = 7;

    protected $signature = 'schedules:assign-security-week
                            {week-start : Tanggal mulai minggu (YYYY-MM-DD), 7 hari berurutan dari tanggal ini}
                            {--employee=* : Kode karyawan:pola 7 hari, contoh ID-001:security,off,security,off,security,security,off}';

    protected $description = 'Assign jadwal security mingguan ke employee_schedules (security/off)';

    public function handle(): int
    {
        $employeeOptions = $this->option('employee');

        if ($employeeOptions === []) {
            $this->error('Minimal satu --employee wajib diisi.');

            return self::FAILURE;
        }

        try {
            $weekStart = Carbon::parse($this->argument('week-start'))->startOfDay();
        } catch (\Throwable) {
            $this->error('Tanggal mulai minggu tidak valid. Gunakan format YYYY-MM-DD.');

            return self::FAILURE;
        }

        $scheduleIds = WorkSchedule::query()
            ->whereIn('code', self::VALID_CODES)
            ->pluck('id', 'code');

        if ($scheduleIds->count() !== count(self::VALID_CODES)) {
            $this->error('Data work_schedules belum lengkap. Jalankan WorkScheduleSeeder terlebih dahulu.');

            return self::FAILURE;
        }

        $assignments = [];
        $errors = [];

        foreach ($employeeOptions as $index => $entry) {
            $label = 'Baris '.($index + 1);

            if (! str_contains($entry, ':')) {
                $errors[] = "{$label}: format --employee tidak valid \"{$entry}\". Gunakan KODE:security,off,...";

                continue;
            }

            [$employeeCode, $pattern] = explode(':', $entry, 2);
            $employeeCode = trim($employeeCode);
            $dayCodes = array_map(
                static fn (string $code) => strtolower(trim($code)),
                explode(',', $pattern),
            );

            if (count($dayCodes) !== self::DAYS_IN_WEEK) {
                $errors[] = "{$label} ({$employeeCode}): pola harus berisi tepat ".self::DAYS_IN_WEEK.' hari.';

                continue;
            }

            foreach ($dayCodes as $dayIndex => $code) {
                if (! in_array($code, self::VALID_CODES, true)) {
                    $errors[] = "{$label} ({$employeeCode}): kode jadwal tidak valid \"{$code}\" pada hari ke-".($dayIndex + 1)
                        .'. Hanya security atau off.';
                }
            }

            $employee = Employee::query()->where('employee_code', $employeeCode)->first();

            if ($employee === null) {
                $errors[] = "{$label}: karyawan dengan kode \"{$employeeCode}\" tidak ditemukan.";

                continue;
            }

            $assignments[] = [
                'employee' => $employee,
                'day_codes' => $dayCodes,
            ];
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $rows = [];

        DB::transaction(function () use ($assignments, $weekStart, $scheduleIds, &$rows): void {
            foreach ($assignments as $assignment) {
                /** @var Employee $employee */
                $employee = $assignment['employee'];

                foreach ($assignment['day_codes'] as $dayIndex => $code) {
                    $workDate = $weekStart->copy()->addDays($dayIndex);

                    $this->upsertEmployeeSchedule(
                        $employee->id,
                        $workDate,
                        $scheduleIds[$code],
                    );

                    $rows[] = [
                        $employee->employee_code,
                        $employee->name,
                        $workDate->toDateString(),
                        $code,
                    ];
                }
            }
        });

        $this->info('Jadwal security mingguan berhasil disimpan.');
        $this->table(
            ['Kode', 'Nama', 'Tanggal', 'Jadwal'],
            $rows,
        );

        return self::SUCCESS;
    }

    private function upsertEmployeeSchedule(int $employeeId, Carbon $workDate, int $workScheduleId): void
    {
        $assignment = EmployeeSchedule::query()
            ->where('employee_id', $employeeId)
            ->whereDate('work_date', $workDate)
            ->first();

        if ($assignment !== null) {
            $assignment->update(['work_schedule_id' => $workScheduleId]);

            return;
        }

        EmployeeSchedule::updateOrCreate(
            [
                'employee_id' => $employeeId,
                'work_date' => $workDate->toDateString(),
            ],
            [
                'work_schedule_id' => $workScheduleId,
            ],
        );
    }
}
