<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Support\AppTime;
use Illuminate\Support\Carbon;

class LeaveVerificationExpiryService
{
    public function expirePendingBefore(?Carbon $beforeDate = null): int
    {
        $cutoff = ($beforeDate ?? AppTime::today())->copy()->startOfDay();

        $attendances = Attendance::query()
            ->pendingLeave()
            ->whereDate('date', '<', $cutoff)
            ->get();

        foreach ($attendances as $attendance) {
            $attendance->update([
                'verification_status' => VerificationStatus::NoDone,
                'verified_by' => null,
                'verified_at' => now(),
            ]);
        }

        return $attendances->count();
    }
}
