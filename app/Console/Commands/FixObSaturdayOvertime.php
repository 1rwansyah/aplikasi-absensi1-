<?php

namespace App\Console\Commands;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Services\AdminAttendanceService;
use App\Support\AppTime;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FixObSaturdayOvertime extends Command
{
    protected $signature = 'attendance:fix-ob-saturday-overtime
                            {--from= : Tanggal duty mulai (Y-m-d)}
                            {--to= : Tanggal duty akhir (Y-m-d)}
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Koreksi bulk lembur hari Sabtu untuk OB (menghitung lembur mulai pukul 15:00)';

    public function handle(AdminAttendanceService $adminAttendance): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Attendance::query()
            ->where('type', AttendanceType::Regular)
            ->whereNotNull('clock_out_time')
            ->whereHas('user.employee', function ($employee) {
                $employee->where('staff', 'OB')
                    ->orWhere('position', 'OB');
            })
            ->with(['workSchedule', 'user.employee'])
            ->orderBy('date')
            ->orderBy('id');

        if ($from = $this->option('from')) {
            $query->whereDate('date', '>=', Carbon::parse($from, AppTime::timezone())->toDateString());
        }

        if ($to = $this->option('to')) {
            $query->whereDate('date', '<=', Carbon::parse($to, AppTime::timezone())->toDateString());
        }

        $updated = 0;
        $unchanged = 0;

        $this->info('Memulai koreksi lembur hari Sabtu untuk OB...');

        // We process all, but filter Saturdays in PHP
        $query->chunkById(100, function ($attendances) use (
            $adminAttendance,
            $dryRun,
            &$updated,
            &$unchanged,
        ) {
            foreach ($attendances as $attendance) {
                // Filter only Saturday
                if (!$attendance->date->isSaturday()) {
                    continue;
                }

                $previousOvertime = (float) $attendance->overtime_hours;
                $correctedOvertime = $adminAttendance->recalculateOvertimeHours($attendance);

                if (abs($previousOvertime - $correctedOvertime) < 0.01) {
                    $unchanged++;
                    continue;
                }

                $employeeName = $attendance->user?->employee?->name
                    ?? $attendance->user?->name
                    ?? "user:{$attendance->user_id}";

                $this->line(sprintf(
                    '%s #%d %s %s | masuk:%s pulang:%s | lembur %.2f → %.2f jam',
                    $dryRun ? '[dry-run]' : '[updated]',
                    $attendance->id,
                    $attendance->date->toDateString(),
                    $employeeName,
                    substr((string) $attendance->clock_in_time, 0, 5),
                    substr((string) $attendance->clock_out_time, 0, 5),
                    $previousOvertime,
                    $correctedOvertime
                ));

                if (!$dryRun) {
                    $attendance->overtime_hours = $correctedOvertime;
                    $attendance->saveQuietly();
                }

                $updated++;
            }
        });

        $this->info("Koreksi selesai. Diubah: {$updated}, Tidak berubah: {$unchanged}.");

        return 0;
    }
}
