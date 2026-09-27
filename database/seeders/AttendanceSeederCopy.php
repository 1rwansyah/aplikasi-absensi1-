<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeederCopy extends Seeder
{
    public function run(): void
    {
        $employees = Employee::whereNotNull('user_id')->get();

        if ($employees->count() < 5) {
            return;
        }

        $startDate = Carbon::create(2026, 5, 1);
        $totalDays = 21;

        $caseEmployees = $employees->shuffle()->take(5)->values();

        $cases = [
            // Telat 3 hari + Alfa 3 hari
            0 => [
                'late_days' => [2, 8, 12],
                'permission_days' => [],
                'alpha_days' => [14, 16, 18],
            ],

            // Telat 3 hari
            1 => [
                'late_days' => [1, 5, 9],
                'permission_days' => [],
                'alpha_days' => [],
            ],

            // Alfa 3 hari
            2 => [
                'late_days' => [],
                'permission_days' => [],
                'alpha_days' => [3, 10, 17],
            ],

            // Izin 3 hari
            3 => [
                'late_days' => [],
                'permission_days' => [4, 11, 19],
                'alpha_days' => [],
            ],

            // Normal
            4 => [
                'late_days' => [],
                'permission_days' => [],
                'alpha_days' => [],
            ],
        ];

        foreach ($employees as $employee) {
            $caseIndex = $caseEmployees->search(fn ($item) => $item->id === $employee->id);
            $case = $caseIndex !== false
                ? $cases[$caseIndex]
                : [
                    'late_days' => [],
                    'permission_days' => [],
                    'alpha_days' => [],
                ];

            for ($i = 0; $i < $totalDays; $i++) {
                $date = $startDate->copy()->addDays($i);

                if (in_array($i, $case['alpha_days'], true)) {
                    continue;
                }

                if (in_array($i, $case['permission_days'], true)) {
                    Attendance::updateOrCreate(
                        [
                            'user_id' => $employee->user_id,
                            'date' => $date->toDateString(),
                        ],
                        [
                            'type' => 'permission',
                            'clock_in_time' => null,
                            'clock_out_time' => null,
                            'clock_in_report' => 'Izin keperluan pribadi',
                            'clock_out_report' => null,
                            'status' => AttendanceStatus::Permission,
                        ]
                    );

                    continue;
                }

                $isLate = in_array($i, $case['late_days'], true);

                Attendance::updateOrCreate(
                    [
                        'user_id' => $employee->user_id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'type' => 'regular',
                        'clock_in_time' => $isLate
                            ? '09:30:00'
                            : fake()->randomElement([
                                '08:55:00',
                                '09:00:00',
                                '09:05:00',
                            ]),
                        'clock_out_time' => '18:00:00',
                        'clock_in_report' => 'Seeder attendance',
                        'clock_out_report' => 'Seeder attendance',
                        'status' => $isLate
                            ? AttendanceStatus::Late
                            : AttendanceStatus::OnTime,
                    ]
                );
            }
        }
    }
}
