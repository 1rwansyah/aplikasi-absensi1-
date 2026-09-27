<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollDetailChange;
use App\Models\User;
use RuntimeException;

class PayrollChangeLogService
{
    /**
     * @return array<string, mixed>
     */
    public static function snapshotDetail(PayrollDetail $detail): array
    {
        return [
            'name' => $detail->name,
            'type' => $detail->type,
            'amount' => (float) $detail->amount,
            'notes' => $detail->notes,
            'is_adjustment' => (bool) $detail->is_adjustment,
        ];
    }

    public static function recordCreate(PayrollDetail $detail, User $user): PayrollDetailChange
    {
        return PayrollDetailChange::create([
            'payroll_id' => $detail->payroll_id,
            'payroll_detail_id' => $detail->id,
            'user_id' => $user->id,
            'action' => 'create',
            'before' => null,
            'after' => self::snapshotDetail($detail),
            'summary' => "Menambahkan {$detail->name}",
        ]);
    }

    /**
     * @param  array<string, mixed>  $before
     */
    public static function recordUpdate(PayrollDetail $detail, array $before, User $user): PayrollDetailChange
    {
        return PayrollDetailChange::create([
            'payroll_id' => $detail->payroll_id,
            'payroll_detail_id' => $detail->id,
            'user_id' => $user->id,
            'action' => 'update',
            'before' => $before,
            'after' => self::snapshotDetail($detail),
            'summary' => "Mengubah {$before['name']}",
        ]);
    }

    public static function recordDelete(PayrollDetail $detail, User $user): PayrollDetailChange
    {
        return PayrollDetailChange::create([
            'payroll_id' => $detail->payroll_id,
            'payroll_detail_id' => $detail->id,
            'user_id' => $user->id,
            'action' => 'delete',
            'before' => self::snapshotDetail($detail),
            'after' => null,
            'summary' => "Menghapus {$detail->name}",
        ]);
    }

    public static function undoLast(Payroll $payroll, User $user): PayrollDetailChange
    {
        $change = PayrollDetailChange::query()
            ->where('payroll_id', $payroll->id)
            ->undoable()
            ->orderByDesc('created_at')
            ->first();

        if (! $change) {
            throw new RuntimeException('Tidak ada perubahan yang dapat dibatalkan.');
        }

        match ($change->action) {
            'create' => self::undoCreate($change),
            'update' => self::undoUpdate($change),
            'delete' => self::undoDelete($change),
            default => throw new RuntimeException('Tindakan undo tidak dikenali.'),
        };

        $change->update([
            'undone_at' => now(),
            'undone_by' => $user->id,
        ]);

        return $change;
    }

    public static function supersedeAll(Payroll $payroll): void
    {
        PayrollDetailChange::query()
            ->where('payroll_id', $payroll->id)
            ->undoable()
            ->update(['superseded_at' => now()]);
    }

    private static function undoCreate(PayrollDetailChange $change): void
    {
        $detail = PayrollDetail::withTrashed()->find($change->payroll_detail_id);

        if (! $detail) {
            throw new RuntimeException('Item yang ditambahkan sudah tidak ada.');
        }

        $detail->forceDelete();
    }

    private static function undoUpdate(PayrollDetailChange $change): void
    {
        $detail = PayrollDetail::withTrashed()->find($change->payroll_detail_id);

        if (! $detail) {
            throw new RuntimeException('Item yang diubah sudah tidak ada.');
        }

        if ($detail->trashed()) {
            $detail->restore();
        }

        $detail->update($change->before);
    }

    private static function undoDelete(PayrollDetailChange $change): void
    {
        $detail = PayrollDetail::withTrashed()->find($change->payroll_detail_id);

        if ($detail) {
            if ($detail->trashed()) {
                $duplicate = PayrollDetail::query()
                    ->where('payroll_id', $change->payroll_id)
                    ->where('type', $detail->type)
                    ->where('name', $detail->name)
                    ->where('id', '!=', $detail->id)
                    ->exists();

                if ($duplicate) {
                    throw new RuntimeException("Item {$detail->name} sudah ada. Tidak dapat memulihkan.");
                }

                $detail->restore();

                return;
            }

            throw new RuntimeException('Item yang dihapus masih aktif.');
        }

        if (! $change->before) {
            throw new RuntimeException('Data item yang dihapus tidak tersedia.');
        }

        PayrollDetail::create([
            'payroll_id' => $change->payroll_id,
            ...$change->before,
        ]);
    }
}
