<?php

namespace App\Console\Commands;

use App\Services\LeaveVerificationExpiryService;
use Illuminate\Console\Command;

class ExpirePendingLeaveVerifications extends Command
{
    protected $signature = 'attendance:expire-pending-leaves';

    protected $description = 'Tolak otomatis pengajuan izin/sakit yang masih menunggu verifikasi dari hari sebelumnya';

    public function handle(LeaveVerificationExpiryService $expiryService): int
    {
        $expired = $expiryService->expirePendingBefore();

        $this->info("Pengajuan otomatis ditolak: {$expired}");

        return self::SUCCESS;
    }
}
