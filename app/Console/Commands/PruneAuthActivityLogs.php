<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;

class PruneAuthActivityLogs extends Command
{
    protected $signature = 'activity-log:prune-login-logout
                            {--dry-run : Tampilkan jumlah tanpa menghapus}
                            {--force : Hapus tanpa konfirmasi}';

    protected $description = 'Hapus log aktivitas login dan logout dari activity_logs';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $query = ActivityLog::query()->whereIn('action', ['login', 'logout']);
        $count = $query->count();

        if ($count === 0) {
            $this->info('Tidak ada log login/logout yang perlu dihapus.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("Dry run: {$count} log login/logout akan dihapus.");

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm("Hapus {$count} log login/logout?")) {
            $this->warn('Dibatalkan.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Selesai. {$deleted} log login/logout dihapus.");

        return self::SUCCESS;
    }
}
