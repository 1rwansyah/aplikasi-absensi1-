<?php

use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $obSchedule = WorkSchedule::query()->firstOrCreate(
            ['code' => 'ob'],
            [
                'name' => 'OB',
                'clock_in_start' => '07:30:00',
                'late_limit' => '07:30:00',
                'clock_out_start' => '18:00:00',
                'clock_out_limit' => '18:30:00',
                'is_off' => false,
            ]
        );

        $obSchedule->update([
            'clock_in_start' => '07:30:00',
            'late_limit' => '07:30:00',
            'clock_out_start' => '18:00:00',
            'clock_out_limit' => '18:30:00',
        ]);

        Employee::query()
            ->where(function ($query) {
                $query->where('staff', 'OB')
                    ->orWhere('position', 'OB');
            })
            ->update(['default_work_schedule_id' => $obSchedule->id]);
    }

    public function down(): void
    {
        WorkSchedule::query()
            ->where('code', 'ob')
            ->update([
                'clock_in_start' => '08:00:00',
                'late_limit' => '08:30:00',
                'clock_out_start' => '17:00:00',
                'clock_out_limit' => '17:30:00',
            ]);

        $obScheduleId = WorkSchedule::query()->where('code', 'ob')->value('id');

        if ($obScheduleId === null) {
            return;
        }

        Employee::query()
            ->where('default_work_schedule_id', $obScheduleId)
            ->where(function ($query) {
                $query->where('staff', 'OB')
                    ->orWhere('position', 'OB');
            })
            ->update(['default_work_schedule_id' => null]);
    }
};
