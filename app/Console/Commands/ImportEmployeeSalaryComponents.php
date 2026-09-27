<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use Illuminate\Console\Command;

class ImportEmployeeSalaryComponents extends Command
{
    protected $signature = 'employees:import-salary {file=employee-salary-components.csv}';

    protected $description = 'Import employee salary components from CSV';

    public function handle(): int
    {
        $path = storage_path('app/'.$this->argument('file'));

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');

        $rowNumber = 0;

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNumber++;

            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);

            if ($rowNumber === 1) {
                continue;
            }

            $employeeCode = 'ID-'.str_pad((string) ($row[0] ?? ''), 3, '0', STR_PAD_LEFT);

            if ($employeeCode === '') {
                continue;
            }

            $employee = Employee::where('employee_code', $employeeCode)->first();

            if (! $employee) {
                $this->warn("Karyawan tidak ditemukan: {$employeeCode}");

                continue;
            }

            $componentColumns = [
                1 => 'Jabatan',
                2 => 'Transport',
                3 => 'Keluarga',
                4 => 'Lembur',
                5 => 'Pulsa',
                6 => 'Tempat Tinggal',
                7 => 'BPJS Kesehatan',
                8 => 'BPJS TK',
                9 => 'Asuransi Pendidikan',
            ];

            foreach ($componentColumns as $index => $componentName) {
                $amount = $this->parseMoney($row[$index] ?? null);

                $component = SalaryComponent::where('name', $componentName)->first();

                if (! $component) {
                    $this->warn("Komponen tidak ditemukan: {$componentName}");

                    continue;
                }

                EmployeeSalaryComponent::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'salary_component_id' => $component->id,
                    ],
                    [
                        'amount' => $amount,
                        'is_active' => $amount > 0,
                    ]
                );
            }

            $this->info("Komponen gaji berhasil diimport: {$employee->name}");
        }

        fclose($handle);

        $this->info('Import komponen gaji selesai.');

        return self::SUCCESS;
    }

    private function parseMoney(?string $value): int
    {
        if (! $value) {
            return 0;
        }

        $value = trim($value);

        if (
            $value === '' ||
            $value === '-' ||
            $value === '0' ||
            $value === '0.00' ||
            $value === '0,00'
        ) {
            return 0;
        }

        $value = str_replace(',', '', $value);

        return (int) round((float) $value);
    }
}
