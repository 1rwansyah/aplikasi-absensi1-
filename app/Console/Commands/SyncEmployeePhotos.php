<?php

namespace App\Console\Commands;

use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;

class SyncEmployeePhotos extends Command
{
    protected $signature = 'employees:sync-photos';

    protected $description = 'Sync employee photos from source folder with randomized storage names';

    public function handle(): int
    {
        $employees = Employee::query()->get();

        foreach ($employees as $employee) {
            if ($employee->profile_photo && Storage::disk('public')->exists($employee->profile_photo)) {
                continue;
            }

            $sourcePath = $this->findSourcePhoto($employee->name);

            if (! $sourcePath) {
                $this->warn("Foto tidak ditemukan: {$employee->name}");

                continue;
            }

            $storedPath = Storage::disk('public')->putFile(
                'employee-photos',
                new File(Storage::disk('public')->path($sourcePath))
            );

            $employee->update([
                'profile_photo' => $storedPath,
            ]);

            $this->info("Foto berhasil disync: {$employee->employee_code} - {$employee->name}");
        }

        return self::SUCCESS;
    }

    private function findSourcePhoto(string $name): ?string
    {
        $normalizedName = strtolower(trim($name));

        $files = Storage::disk('public')->files('employees-source');

        foreach ($files as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);

            if (strtolower(trim($filename)) === $normalizedName) {
                return $file;
            }
        }

        return null;
    }
}
