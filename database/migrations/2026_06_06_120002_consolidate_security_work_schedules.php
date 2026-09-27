<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        $securityAttributes = [
            'name' => 'Security',
            'clock_in_start' => '07:00:00',
            'late_limit' => '07:15:00',
            'clock_out_start' => '07:00:00',
            'clock_out_limit' => null,
            'is_off' => false,
            'updated_at' => $timestamp,
        ];

        $existingSecurity = DB::table('work_schedules')->where('code', 'security')->first();

        if ($existingSecurity) {
            DB::table('work_schedules')->where('id', $existingSecurity->id)->update($securityAttributes);
        } else {
            DB::table('work_schedules')->insert([
                'code' => 'security',
                ...$securityAttributes,
                'created_at' => $timestamp,
            ]);
        }

        $securityId = DB::table('work_schedules')->where('code', 'security')->value('id');

        if ($securityId === null) {
            return;
        }

        $legacyIds = DB::table('work_schedules')
            ->whereIn('code', ['day', 'night'])
            ->pluck('id')
            ->all();

        if ($legacyIds === []) {
            return;
        }

        DB::table('employee_schedules')
            ->whereIn('work_schedule_id', $legacyIds)
            ->update(['work_schedule_id' => $securityId]);

        DB::table('employees')
            ->whereIn('default_work_schedule_id', $legacyIds)
            ->update(['default_work_schedule_id' => $securityId]);
    }

    public function down(): void
    {
        // Legacy day/night rows are preserved for historical attendances; no rollback.
    }
};
