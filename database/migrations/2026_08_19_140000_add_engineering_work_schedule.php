<?php

use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        WorkSchedule::query()->updateOrCreate(
            ['code' => 'engineering'],
            [
                'name' => 'Staff Engineering',
                'clock_in_start' => '00:00:00',
                'late_limit' => '00:15:00',
                'clock_out_start' => '05:00:00',
                'clock_out_limit' => '05:15:00',
                'is_off' => false,
            ],
        );

        $engineeringId = WorkSchedule::query()->where('code', 'engineering')->value('id');

        if ($engineeringId) {
            Employee::query()
                ->where(function ($query) {
                    $query->whereRaw('UPPER(staff) = ?', ['ENGINEERING'])
                        ->orWhereRaw('UPPER(position) LIKE ?', ['%ENGINEERING%']);
                })
                ->update(['default_work_schedule_id' => $engineeringId]);
        }
    }

    public function down(): void
    {
        WorkSchedule::query()->where('code', 'engineering')->delete();
    }
};
