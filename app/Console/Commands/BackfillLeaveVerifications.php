<?php

namespace App\Console\Commands;

use App\Services\LeaveVerificationBackfillService;
use App\Support\AppTime;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class BackfillLeaveVerifications extends Command
{
    protected $signature = 'attendance:backfill-leave-verification
                            {--before= : Hanya izin/sakit dengan tanggal sebelum Y-m-d}
                            {--dry-run : Tampilkan preview tanpa mengubah database}';

    protected $description = 'Setujui otomatis pengajuan izin/sakit pending (untuk data lama sebelum fitur verifikasi)';

    public function handle(LeaveVerificationBackfillService $backfillService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $beforeOption = $this->option('before');

        $before = null;
        if (filled($beforeOption)) {
            try {
                $before = Carbon::parse($beforeOption, AppTime::timezone())->startOfDay();
            } catch (\Throwable) {
                $this->error('Format --before tidak valid. Gunakan Y-m-d, contoh: --before=2026-06-30');

                return self::FAILURE;
            }
        }

        if ($before === null && ! $dryRun) {
            if (! $this->confirm('Tidak ada --before. Semua pengajuan pending akan disetujui. Lanjutkan?')) {
                $this->warn('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $result = $backfillService->backfill($before, $dryRun);
        $records = $result['records'];

        if ($records->isNotEmpty()) {
            $this->table(
                ['ID', 'Tanggal', 'Karyawan', 'Jenis', 'Status'],
                $records->map(fn ($attendance) => [
                    $attendance->id,
                    $attendance->date->format('d/m/Y'),
                    $attendance->user?->name ?? '-',
                    $attendance->type->label(),
                    $attendance->verification_status->label(),
                ]),
            );
        }

        $scope = $before
            ? "sebelum {$before->toDateString()}"
            : 'semua tanggal';

        if ($dryRun) {
            $this->info("Dry run selesai. {$result['updated']} pengajuan pending ({$scope}) akan disetujui.");

            return self::SUCCESS;
        }

        $this->info("Selesai. {$result['updated']} pengajuan pending ({$scope}) disetujui (done).");

        return self::SUCCESS;
    }
}
