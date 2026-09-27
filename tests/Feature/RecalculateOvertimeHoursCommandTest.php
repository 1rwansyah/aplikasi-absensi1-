<?php

namespace Tests\Feature;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateOvertimeHoursCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
    }

    public function test_command_fixes_inflated_security_overtime(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $user = User::factory()->create();

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

        $this->artisan('attendance:recalculate-overtime', ['--security-only' => true])
            ->assertSuccessful();

        $attendance->refresh();
        $this->assertSame(0.0, $attendance->overtime_hours);
    }

    public function test_command_dry_run_does_not_persist_changes(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $securitySchedule->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '07:00:00',
            'clock_out_time' => '09:00:00',
            'status' => AttendanceStatus::OnTime,
            'overtime_hours' => 0.0,
        ]);

        $this->artisan('attendance:recalculate-overtime', [
            '--security-only' => true,
            '--dry-run' => true,
        ])->assertSuccessful();

        $attendance->refresh();
        $this->assertSame(0.0, $attendance->overtime_hours);
    }

    public function test_command_recalculates_security_one_hour_overtime(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $securitySchedule->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '07:00:00',
            'clock_out_time' => '09:00:00',
            'status' => AttendanceStatus::OnTime,
            'overtime_hours' => 23.0,
        ]);

        $this->artisan('attendance:recalculate-overtime', ['--security-only' => true])
            ->assertSuccessful();

        $attendance->refresh();
        $this->assertSame(1.0, $attendance->overtime_hours);
    }

    public function test_security_only_skips_regular_attendance(): void
    {
        $regularSchedule = WorkSchedule::where('code', 'regular')->firstOrFail();
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $regularSchedule->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::OnTime,
            'overtime_hours' => 5.0,
        ]);

        $this->artisan('attendance:recalculate-overtime', ['--security-only' => true])
            ->assertSuccessful();

        $attendance->refresh();
        $this->assertSame(5.0, $attendance->overtime_hours);
    }

    public function test_command_fixes_security_attendance_stored_as_regular(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $regularSchedule = WorkSchedule::where('code', 'regular')->firstOrFail();

        $user = User::factory()->create();
        $employee = \App\Models\Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'SEC-WIN',
            'name' => 'Winarno Test',
            'employment_status' => 'active',
            'staff' => 'Security',
            'basic_salary' => 1000000,
        ]);

        \App\Models\EmployeeSchedule::create([
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

        $this->artisan('attendance:recalculate-overtime')
            ->assertSuccessful();

        $attendance->refresh();
        $this->assertSame($securitySchedule->id, $attendance->work_schedule_id);
        $this->assertSame(0.0, $attendance->overtime_hours);
    }
}
