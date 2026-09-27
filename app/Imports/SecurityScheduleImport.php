<?php

namespace App\Imports;

use App\Exceptions\SecurityScheduleImportException;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class SecurityScheduleImport implements ToCollection
{
    private const DAY_COLUMNS = ['sen', 'sel', 'rab', 'kam', 'jum', 'sab', 'min'];

    /** @var array<int, string> */
    private array $errors = [];

    public function __construct(private readonly Carbon $weekStart) {}

    public function collection(Collection $rows): void
    {
        $workSchedules = WorkSchedule::query()
            ->whereIn('code', ['security', 'off'])
            ->get()
            ->keyBy('code');

        if ($workSchedules->count() < 2) {
            throw new SecurityScheduleImportException([
                'Data work_schedules belum lengkap. Jalankan WorkScheduleSeeder terlebih dahulu.',
            ]);
        }

        $pending = [];

        foreach ($rows as $index => $row) {
            $excelRow = $index + 1;

            if ($index === 0 && $this->isHeaderRow($row)) {
                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $name = trim((string) ($row[0] ?? ''));

            if ($name === '') {
                $this->errors[] = "Baris {$excelRow}: nama wajib diisi.";

                continue;
            }

            $employee = Employee::query()->where('name', $name)->first();

            if ($employee === null) {
                $this->errors[] = "Baris {$excelRow}: karyawan \"{$name}\" tidak ditemukan.";
            }

            foreach (self::DAY_COLUMNS as $dayIndex => $dayLabel) {
                $rawValue = trim((string) ($row[$dayIndex + 1] ?? ''));

                $mapped = $this->mapExcelValue($rawValue);

                if ($mapped === null) {
                    $this->errors[] = "Baris {$excelRow}: nilai jadwal \"{$rawValue}\" pada kolom {$dayLabel} tidak valid. Hanya L, PG, atau OFF.";

                    continue;
                }

                if ($employee === null) {
                    continue;
                }

                $workDate = $this->weekStart->copy()->addDays($dayIndex);

                $pending[] = [
                    'employee_id' => $employee->id,
                    'work_date' => $workDate,
                    'work_schedule_id' => $workSchedules[$mapped['schedule_code']]->id,
                    'post_location' => $mapped['post_location'],
                ];
            }
        }

        if ($this->errors !== []) {
            throw new SecurityScheduleImportException($this->errors);
        }

        if ($pending === []) {
            throw new SecurityScheduleImportException([
                'File Excel tidak memiliki data jadwal yang dapat diproses.',
            ]);
        }

        DB::transaction(function () use ($pending): void {
            foreach ($pending as $item) {
                $this->upsertEmployeeSchedule($item);
            }
        });
    }

    private function isHeaderRow(Collection $row): bool
    {
        return strtolower(trim((string) ($row[0] ?? ''))) === 'nama';
    }

    private function rowIsEmpty(Collection $row): bool
    {
        return $row->every(fn ($value) => trim((string) $value) === '');
    }

    /**
     * @return array{schedule_code: string, post_location: ?string}|null
     */
    private function mapExcelValue(string $value): ?array
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            $value = 'OFF';
        }

        return match ($value) {
            'L' => ['schedule_code' => 'security', 'post_location' => 'lobby'],
            'PG' => ['schedule_code' => 'security', 'post_location' => 'gate'],
            'OFF' => ['schedule_code' => 'off', 'post_location' => null],
            default => null,
        };
    }

    /**
     * @param  array{employee_id: int, work_date: Carbon, work_schedule_id: int, post_location: ?string}  $item
     */
    private function upsertEmployeeSchedule(array $item): void
    {
        $assignment = EmployeeSchedule::query()
            ->where('employee_id', $item['employee_id'])
            ->whereDate('work_date', $item['work_date'])
            ->first();

        if ($assignment !== null) {
            $assignment->update([
                'work_schedule_id' => $item['work_schedule_id'],
                'post_location' => $item['post_location'],
            ]);

            return;
        }

        EmployeeSchedule::updateOrCreate(
            [
                'employee_id' => $item['employee_id'],
                'work_date' => $item['work_date']->toDateString(),
            ],
            [
                'work_schedule_id' => $item['work_schedule_id'],
                'post_location' => $item['post_location'],
            ],
        );
    }
}
