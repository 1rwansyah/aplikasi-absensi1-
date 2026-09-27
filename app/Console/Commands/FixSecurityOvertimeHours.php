<?php

namespace App\Console\Commands;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Services\AdminAttendanceService;
use App\Support\AppTime;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FixSecurityOvertimeHours extends Command
{
    protected $signature = 'attendance:fix-security-overtime
                            {--from= : Tanggal duty mulai (Y-m-d)}
                            {--to= : Tanggal duty akhir (Y-m-d)}
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Koreksi bulk lembur petugas security (hanya overtime_hours & work_schedule_id, jam masuk/pulang tidak diubah)';

    public function handle(AdminAttendanceService $adminAttendance): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $query = Attendance::query()
            ->where('type', AttendanceType::Regular)
            ->whereNotNull('clock_out_time')
            ->whereHas('user.employee', fn ($employee) => $employee->where('staff', 'Security'))
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
        $skipped = 0;

        $query->chunkById(100, function ($attendances) use (
            $adminAttendance,
            $dryRun,
            &$updated,
            &$unchanged,
            &$skipped,
        ) {
            foreach ($attendances as $attendance) {
                if (! $attendance->shiftSchedule()->opensClockOutOnNextDutyDay()) {
                    $skipped++;

                    continue;
                }

                $resolvedSchedule = $attendance->resolvedWorkSchedule();
                $scheduleChanged = $resolvedSchedule !== null
                    && $attendance->work_schedule_id !== $resolvedSchedule->id;

                $previousOvertime = (float) $attendance->overtime_hours;
                $correctedOvertime = $adminAttendance->recalculateOvertimeHours($attendance);

                if ($previousOvertime === $correctedOvertime && ! $scheduleChanged) {
                    $unchanged++;

                    continue;
                }

                $employeeName = $attendance->user?->employee?->name
                    ?? $attendance->user?->name
                    ?? "user:{$attendance->user_id}";

                if ($dryRun) {
                    $this->line(sprintf(
                        '[dry-run] #%d %s %s | masuk:%s pulang:%s | lembur %.2f → %.2f jam%s',
                        $attendance->id,
                        $attendance->date->toDateString(),
                        $employeeName,
                        substr((string) $attendance->clock_in_time, 0, 5),
                        substr((string) $attendance->clock_out_time, 0, 5),
                        $previousOvertime,
                        $correctedOvertime,
                        $scheduleChanged
                            ? " | jadwal {$attendance->workSchedule?->code} → {$resolvedSchedule?->code}"
                            : '',
                    ));
                } else {
                    if ($scheduleChanged) {
                        $attendance->work_schedule_id = $resolvedSchedule->id;
                    }

                    $attendance->overtime_hours = $correctedOvertime;
                    $attendance->saveQuietly();
                }

                $updated++;
            }
        });

        $this->info($dryRun ? "Akan dikoreksi (dry-run): {$updated}" : "Dikoreksi: {$updated}");
        $this->info("Sudah benar: {$unchanged}");
        $this->info("Dilewati (bukan shift H+1): {$skipped}");
        $this->comment('Jam masuk/pulang tidak diubah — hanya overtime_hours dan work_schedule_id bila perlu.');

        return self::SUCCESS;
    }
}
