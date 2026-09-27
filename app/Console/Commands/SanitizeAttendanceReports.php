<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Support\RichTextSanitizer;
use Illuminate\Console\Command;

class SanitizeAttendanceReports extends Command
{
    protected $signature = 'attendances:sanitize-reports {--dry-run : Preview changes without writing to the database}';

    protected $description = 'Clean legacy &nbsp; and empty CKEditor HTML from attendance report fields';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        Attendance::query()
            ->where(function ($query) {
                $query->whereNotNull('clock_in_report')
                    ->orWhereNotNull('clock_out_report')
                    ->orWhereNotNull('leave_note');
            })
            ->orderBy('id')
            ->chunkById(100, function ($attendances) use ($dryRun, &$updated) {
                foreach ($attendances as $attendance) {
                    $changes = [];

                    foreach ([
                        'clock_in_report' => 'sanitizeHtml',
                        'clock_out_report' => 'sanitizeHtml',
                        'leave_note' => 'sanitizeHtml',
                    ] as $field => $method) {
                        $original = $attendance->{$field};

                        if ($original === null) {
                            continue;
                        }

                        $clean = RichTextSanitizer::{$method}($original);

                        if ($clean !== $original) {
                            $changes[$field] = $clean;
                        }
                    }

                    if ($changes === []) {
                        continue;
                    }

                    $updated++;

                    $this->line(sprintf(
                        'Attendance #%d (%s): %s',
                        $attendance->id,
                        $attendance->date?->toDateString() ?? '-',
                        implode(', ', array_keys($changes)),
                    ));

                    if (! $dryRun) {
                        $attendance->update($changes);
                    }
                }
            });

        $this->info($dryRun
            ? "Dry run complete. {$updated} record(s) would be updated."
            : "Done. {$updated} record(s) updated.");

        return self::SUCCESS;
    }
}
