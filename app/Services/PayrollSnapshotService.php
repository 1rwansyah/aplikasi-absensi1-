<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollSystemSnapshot;
use RuntimeException;

class PayrollSnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public static function buildSnapshotPayload(Payroll $payroll): array
    {
        $payroll->load('details');

        $headerFields = [
            'work_days',
            'present_days',
            'absent_days',
            'sick_days',
            'leave_days',
            'late_days',
            'basic_salary',
            'prorate_salary',
            'full_monthly_salary',
            'total_allowance',
            'total_deduction',
            'gross_salary',
            'net_salary',
            'rounding_amount',
        ];

        $header = [];
        foreach ($headerFields as $field) {
            $header[$field] = $payroll->{$field};
        }

        $details = $payroll->details->map(fn (PayrollDetail $detail) => [
            'name' => $detail->name,
            'type' => $detail->type,
            'amount' => (float) $detail->amount,
            'notes' => $detail->notes,
            'is_adjustment' => (bool) $detail->is_adjustment,
        ])->values()->all();

        return [
            'header' => $header,
            'details' => $details,
        ];
    }

    public static function capture(Payroll $payroll): PayrollSystemSnapshot
    {
        return PayrollSystemSnapshot::updateOrCreate(
            ['payroll_id' => $payroll->id],
            [
                'snapshot' => self::buildSnapshotPayload($payroll),
                'generated_at' => now(),
            ]
        );
    }

    public static function resetToSystem(Payroll $payroll): void
    {
        $snapshot = PayrollSystemSnapshot::where('payroll_id', $payroll->id)->first();

        if (! $snapshot) {
            throw new RuntimeException('Snapshot sistem belum tersedia. Jalankan Proses Gaji terlebih dahulu.');
        }

        $payload = $snapshot->snapshot;

        PayrollDetail::withTrashed()
            ->where('payroll_id', $payroll->id)
            ->forceDelete();

        foreach ($payload['details'] ?? [] as $row) {
            PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'name' => $row['name'],
                'type' => $row['type'],
                'amount' => $row['amount'],
                'notes' => $row['notes'] ?? null,
                'is_adjustment' => (bool) ($row['is_adjustment'] ?? false),
            ]);
        }

        $payroll->update($payload['header'] ?? []);

        PayrollChangeLogService::supersedeAll($payroll);
    }
}
