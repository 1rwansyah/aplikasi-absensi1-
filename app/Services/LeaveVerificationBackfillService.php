<?php

namespace App\Services;

use App\Enums\VerificationStatus;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LeaveVerificationBackfillService
{
    /**
     * @return array{updated: int, records: Collection<int, Attendance>}
     */
    public function backfill(?Carbon $before = null, bool $dryRun = false): array
    {
        $query = Attendance::query()
            ->with('user:id,name')
            ->pendingLeave()
            ->orderBy('date')
            ->orderBy('id');

        if ($before !== null) {
            $query->whereDate('date', '<', $before);
        }

        $records = $query->get();

        if ($dryRun || $records->isEmpty()) {
            return [
                'updated' => $records->count(),
                'records' => $records,
            ];
        }

        Attendance::query()
            ->whereIn('id', $records->pluck('id'))
            ->update([
                'verification_status' => VerificationStatus::Done,
                'verified_by' => null,
                'verified_at' => now(),
            ]);

        return [
            'updated' => $records->count(),
            'records' => $records,
        ];
    }
}
