<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Setting::firstOrCreate([], [
            'office_start' => '09:00:00',
            'late_limit' => '09:15:00',
            'clock_out_start' => '18:00:00',
            'clock_out_limit' => '18:15:00',
            'night_detect_from' => '17:00:00',
            'night_office_start' => '17:00:00',
            'night_late_limit' => '22:00:00',
            'night_clock_out_start' => '22:00:00',
            'night_clock_out_limit' => '07:00:00',
            'office_latitude' => -6.200000,
            'office_longitude' => 106.816666,
            'attendance_radius_meters' => 1000,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('ChangeMe123!'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            RoleSeeder::class,
            SalaryComponentSeeder::class,
            WorkCalendarSeeder::class,
            WorkScheduleSeeder::class,
        ]);
    }
}
