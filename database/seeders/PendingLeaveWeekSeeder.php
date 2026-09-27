<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Support\AppTime;
use Illuminate\Database\Seeder;

/**
 * Seed izin/sakit status Menunggu untuk 7 hari ke belakang (testing verifikasi admin / auto-tolak).
 *
 * Jalankan:
 *   php artisan db:seed --class=PendingLeaveWeekSeeder
 *
 * Atau salin isi method run() ke tinker.
 */
class PendingLeaveWeekSeeder extends Seeder
{
    public function run(): void
    {
        $employeeCode = 'ID-180'; // contoh: 'ID-001' — null = semua karyawan aktif

        $employees = Employee::query()
            ->whereNotNull('user_id')
            ->where('employment_status', 'active')
            ->when($employeeCode, fn ($q) => $q->where('employee_code', $employeeCode))
            ->get();

        if ($employees->isEmpty()) {
            $this->command?->warn('Tidak ada karyawan aktif dengan akun user.');

            return;
        }

        $created = 0;
        $skipped = 0;

        foreach (range(1, 7) as $daysAgo) {
            $date = AppTime::today()->subDays($daysAgo);
            $type = $daysAgo % 2 === 0 ? AttendanceType::Permission : AttendanceType::Sick;

            foreach ($employees as $employee) {
                $exists = Attendance::query()
                    ->where('user_id', $employee->user_id)
                    ->whereDate('date', $date)
                    ->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                Attendance::create([
                    'user_id' => $employee->user_id,
                    'date' => $date,
                    'type' => $type,
                    'leave_note' => "Data uji — {$type->label()} {$date->translatedFormat('d M Y')}",
                    'status' => AttendanceStatus::forLeave($type),
                    'verification_status' => VerificationStatus::Pending,
                    'verified_by' => null,
                    'verified_at' => null,
                ]);

                $created++;
            }
        }

        $this->command?->info("Selesai. Dibuat: {$created}, dilewati (sudah ada absensi): {$skipped}");
    }
}
