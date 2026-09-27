<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ImportEmployees extends Command
{
    protected $signature = 'employees:import {file=employees.csv}';

    protected $description = 'Import employees from CSV';

    public function handle(): int
    {
        $path = storage_path('app/'.$this->argument('file'));

        if (! file_exists($path)) {
            $this->error("File tidak ditemukan: {$path}");

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {

            $row = array_map(fn ($value) => is_string($value) ? trim($value) : $value, $row);

            $no = $row[0] ?? '';
            $name = $row[1] ?? '';
            $email = trim($row[2] ?? '');

            $lowerRow = strtolower(implode(' ', $row));

            if (
                str_contains($lowerRow, 'nama karyawan') ||
                str_contains($lowerRow, 'perusahaan') ||
                str_contains($lowerRow, 'staff office') ||
                str_contains($lowerRow, 'tanggal lahir') ||
                (str_contains($lowerRow, 'pendidikan') && str_contains($lowerRow, 'pengalaman'))
            ) {
                continue;
            }

            if ($name === '' || ! is_numeric($no)) {
                continue;
            }

            $employeeCode = 'ID-'.str_pad((string) $no, 3, '0', STR_PAD_LEFT);

            [$birthPlace, $birthDate] = $this->parseBirthData($row[8] ?? null);

            // 🔥 FOTO PROCESS (VS CODE → Laravel STYLE)
            $photo = $this->findAndStorePhoto($name);

            $employee = Employee::where('employee_code', $employeeCode)->first();

            $data = [
                'name' => $name,
                'position' => $row[3] ?? null,
                'education' => $row[4] ?? null,
                'staff' => $row[5] ?? null,
                'work_experience' => $row[6] ?? null,
                'nik' => $row[7] ?? null,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'address' => $row[9] ?? null,
                'join_date' => $this->parseIndonesianDate($row[10] ?? null),
                'bank_account_number' => $row[11] ?? null,
                'bank_name' => $row[12] ?? null,
                'bank_account_name' => $row[13] ?? null,
                'basic_salary' => 1000000,

                // 🔥 FINAL PHOTO PATH (SAME AS WEB UPLOAD)
                'profile_photo' => $photo,
            ];

            if ($email !== '') {
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'password' => Hash::make('ChangeMe123!'),
                        'role' => 'employee',
                    ]
                );

                $employeeRole = Role::where('name', 'employee')->first();

                if ($employeeRole) {
                    $user->roles()->syncWithoutDetaching([$employeeRole->id]);
                }

                $data['email'] = $email;
                $data['user_id'] = $user->id;
                $data['employment_status'] = 'active';
            } else {
                if (! $employee) {
                    $data['email'] = null;
                    $data['user_id'] = null;
                    $data['employment_status'] = 'inactive';
                }
            }

            Employee::updateOrCreate(
                ['employee_code' => $employeeCode],
                $data
            );
        }

        fclose($handle);

        $this->info('Import karyawan selesai.');

        return self::SUCCESS;
    }

    /**
     * 🔥 INI YANG BIKIN HASIL SAMA PERSIS DENGAN UPLOAD WEB
     */
    private function findAndStorePhoto(string $name): ?string
    {
        $name = trim($name);

        foreach (['jpg', 'jpeg', 'png'] as $ext) {

            $originalPath = "employees/{$name}.{$ext}";

            if (Storage::disk('public')->exists($originalPath)) {

                // ambil file lama
                $file = new File(
                    storage_path("app/public/{$originalPath}")
                );

                // simpan pakai Laravel (AUTO HASH NAME SAMA DENGAN WEB UPLOAD)
                return Storage::disk('public')->putFile('employee-photos', $file);
            }
        }

        return null;
    }

    private function parseBirthData(?string $value): array
    {
        if (! $value) {
            return [null, null];
        }

        $parts = explode(',', $value, 2);

        return [
            trim($parts[0] ?? ''),
            $this->parseIndonesianDate($parts[1] ?? null),
        ];
    }

    private function parseIndonesianDate(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);

        if (
            $value === '' ||
            str_contains(strtolower($value), 'tanggal') ||
            str_contains(strtolower($value), 'lahir') ||
            str_contains(strtolower($value), 'masuk')
        ) {
            return null;
        }

        $months = [
            'Januari' => 'January',
            'Februari' => 'February',
            'Maret' => 'March',
            'April' => 'April',
            'Mei' => 'May',
            'Juni' => 'June',
            'Juli' => 'July',
            'Agustus' => 'August',
            'September' => 'September',
            'Oktober' => 'October',
            'November' => 'November',
            'Desember' => 'December',
        ];

        $value = str_replace(array_keys($months), array_values($months), $value);

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
