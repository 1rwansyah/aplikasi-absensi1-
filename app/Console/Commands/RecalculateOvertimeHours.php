<?php

namespace App\Console\Commands;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Services\AdminAttendanceService;
use App\Support\AppTime;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RecalculateOvertimeHours extends Command
{
    protected $signature = 'attendance:recalculate-overtime
                            {--security-only : Hanya absensi shift 24 jam / pola pulang H+1}
                            {--from= : Tanggal mulai duty (Y-m-d)}
                            {--to= : Tanggal akhir duty (Y-m-d)}
                            {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Hitung ulang kolom overtime_hours dari jam masuk/pulang dan jadwal kerja';

    public function handle(AdminAttendanceService $adminAttendance): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $securityOnly = (bool) $this->option('security-only');

        $query = Attendance::query()
            ->where('type', AttendanceType::Regular)
            ->whereNotNull('clock_out_time')
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
            $securityOnly,
            &$updated,
            &$unchanged,
            &$skipped,
        ) {
            foreach ($attendances as $attendance) {
                if ($securityOnly && ! $attendance->shiftSchedule()->opensClockOutOnNextDutyDay()) {
                    $skipped++;

                    continue;
                }

                $resolvedSchedule = $attendance->resolvedWorkSchedule();
                $scheduleChanged = $resolvedSchedule !== null
                    && $attendance->work_schedule_id !== $resolvedSchedule->id;

                $previous = (float) $attendance->overtime_hours;
                $recalculated = $adminAttendance->recalculateOvertimeHours($attendance);

                if ($previous === $recalculated && ! $scheduleChanged) {
                    $unchanged++;

                    continue;
                }

                if ($dryRun) {
                    $this->line(sprintf(
                        '[dry-run] #%d %s user:%d in:%s out:%s | %.2f → %.2f jam%s',
                        $attendance->id,
                        $attendance->date->toDateString(),
                        $attendance->user_id,
                        $attendance->clock_in_time,
                        $attendance->clock_out_time,
                        $previous,
                        $recalculated,
                        $scheduleChanged ? " | jadwal: {$attendance->workSchedule?->code} → {$resolvedSchedule?->code}" : '',
                    ));
                } else {
                    if ($scheduleChanged) {
                        $attendance->work_schedule_id = $resolvedSchedule->id;
                    }

                    $attendance->overtime_hours = $recalculated;
                    $attendance->save();
                }

                $updated++;
            }
        });

        $mode = $dryRun ? 'Akan diperbarui (dry-run)' : 'Diperbarui';
        $this->info("{$mode}: {$updated}");
        $this->info("Tidak berubah: {$unchanged}");

        if ($securityOnly) {
            $this->info("Dilewati (bukan shift H+1): {$skipped}");
        }

        return self::SUCCESS;
    }
}
