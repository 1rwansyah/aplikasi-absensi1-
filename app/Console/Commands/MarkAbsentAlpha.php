<?php

namespace App\Console\Commands;

use App\Services\MarkAbsentAlphaService;
use App\Support\AppTime;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class MarkAbsentAlpha extends Command
{
    protected $signature = 'attendance:mark-alpha
                            {--date= : Tanggal tunggal Y-m-d (backfill, abaikan cutoff 23:00)}
                            {--from= : Awal rentang Y-m-d (backfill)}
                            {--to= : Akhir rentang Y-m-d (backfill)}';

    protected $description = 'Buat row absensi status=alpha untuk karyawan jam reguler yang tidak clock-in (setelah 23:00 WIB / backfill)';

    public function handle(MarkAbsentAlphaService $service): int
    {
        $dateOption = $this->option('date');
        $fromOption = $this->option('from');
        $toOption = $this->option('to');

        $hasDate = filled($dateOption);
        $hasFrom = filled($fromOption);
        $hasTo = filled($toOption);

        if ($hasFrom xor $hasTo) {
            $this->error('Gunakan --from dan --to bersama-sama.');

            return self::FAILURE;
        }

        if ($hasDate && ($hasFrom || $hasTo)) {
            $this->error('Jangan gabungkan --date dengan --from/--to.');

            return self::FAILURE;
        }

        try {
            if ($hasFrom && $hasTo) {
                $from = $this->parseDate((string) $fromOption, '--from');
                $to = $this->parseDate((string) $toOption, '--to');
                $result = $service->markForRange($from, $to);
            } elseif ($hasDate) {
                $date = $this->parseDate((string) $dateOption, '--date');
                $result = $service->markForDate($date);
            } else {
                $now = AppTime::now();

                if (! $service->isPastDailyCutoff($now)) {
                    $this->info('No-op: sebelum pukul 23:00 '.AppTime::timezone().' (sekarang '.$now->format('Y-m-d H:i:s').').');

                    return self::SUCCESS;
                }

                $result = $service->markForDate(AppTime::today());
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Selesai. tanggal=%d dibuat=%d sudah_ada=%d tidak_eligible=%d',
            $result['dates'],
            $result['created'],
            $result['skipped_existing'],
            $result['skipped_ineligible'],
        ));

        return self::SUCCESS;
    }

    private function parseDate(string $value, string $option): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            throw new \InvalidArgumentException("{$option} harus format Y-m-d.");
        }

        return Carbon::createFromFormat('Y-m-d', $value, AppTime::timezone())->startOfDay();
    }
}
