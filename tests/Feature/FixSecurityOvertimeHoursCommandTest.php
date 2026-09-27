<?php

namespace Tests\Feature;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixSecurityOvertimeHoursCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
    }

    public function test_command_only_updates_overtime_and_schedule_not_clock_times(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $regularSchedule = WorkSchedule::where('code', 'regular')->firstOrFail();

        $user = User::factory()->create();
        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'SEC-001',
            'name' => 'Winarno',
            'employment_status' => 'active',
            'staff' => 'Security',
            'basic_salary' => 1000000,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $securitySchedule->id,
            'work_date' => '2026-06-30',
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $regularSchedule->id,
            'date' => '2026-06-30',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '07:15:00',
            'clock_out_time' => '07:15:00',
            'status' => AttendanceStatus::OnTime,
            'overtime_hours' => 12.25,
        ]);

        $this->artisan('attendance:fix-security-overtime')
            ->assertSuccessful();

        $attendance->refresh();
        $this->assertSame('07:15:00', $attendance->clock_in_time);
        $this->assertSame('07:15:00', $attendance->clock_out_time);
        $this->assertSame($securitySchedule->id, $attendance->work_schedule_id);
        $this->assertSame(0.0, $attendance->overtime_hours);
    }

    public function test_command_skips_non_security_employees(): void
    {
        $regularSchedule = WorkSchedule::where('code', 'regular')->firstOrFail();
        $user = User::factory()->create();
        Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'OFF-001',
            'name' => 'Budi Office',
            'employment_status' => 'active',
            'staff' => 'Office',
            'basic_salary' => 1000000,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $regularSchedule->id,
            'date' => '2026-06-30',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::OnTime,
            'overtime_hours' => 5.0,
        ]);

        $this->artisan('attendance:fix-security-overtime')
            ->assertSuccessful();

        $attendance->refresh();
        $this->assertSame(5.0, $attendance->overtime_hours);
    }

    public function test_dry_run_does_not_persist(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $user = User::factory()->create();
        Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'SEC-002',
            'name' => 'Andi',
            'employment_status' => 'active',
            'staff' => 'Security',
            'basic_salary' => 1000000,
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $securitySchedule->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '07:00:00',
            'clock_out_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
            'overtime_hours' => 23.0,
        ]);

        $this->artisan('attendance:fix-security-overtime', ['--dry-run' => true])
            ->assertSuccessful();

        $attendance->refresh();
        $this->assertSame(23.0, $attendance->overtime_hours);
    }
}
