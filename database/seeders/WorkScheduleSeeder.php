<?php

namespace Database\Seeders;

use App\Models\WorkSchedule;
use Illuminate\Database\Seeder;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        WorkSchedule::updateOrCreate(
            ['code' => 'regular'],
            [
                'name' => 'Reguler',
                'clock_in_start' => '09:00:00',
                'late_limit' => '09:15:00',
                'clock_out_start' => '18:00:00',
                'clock_out_limit' => '18:15:00',
                'is_off' => false,
            ]
        );

        WorkSchedule::updateOrCreate(
            ['code' => 'security'],
            [
                'name' => 'Security',
                'clock_in_start' => '07:00:00',
                'late_limit' => '07:15:00',
                'clock_out_start' => '07:00:00',
                'clock_out_limit' => null,
                'is_off' => false,
            ]
        );

        WorkSchedule::updateOrCreate(
            ['code' => 'off'],
            [
                'name' => 'Libur',
                'clock_in_start' => null,
                'late_limit' => null,
                'clock_out_start' => null,
                'clock_out_limit' => null,
                'is_off' => true,
            ]
        );

        WorkSchedule::updateOrCreate(
            ['code' => 'engineering'],
            [
                'name' => 'Staff Engineering',
                'clock_in_start' => '00:00:00',
                'late_limit' => '00:15:00',
                'clock_out_start' => '05:00:00',
                'clock_out_limit' => '05:15:00',
                'is_off' => false,
            ]
        );
    }
}
