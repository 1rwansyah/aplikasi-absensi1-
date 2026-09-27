<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceSchedulePreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $regularUser;

    private User $securityUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, WorkScheduleSeeder::class]);

        $this->admin = User::factory()->admin()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->regularUser = User::factory()->create(['name' => 'Budi Regular']);
        $this->regularUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());
        Employee::create([
            'user_id' => $this->regularUser->id,
            'employee_code' => 'EMP-001',
            'name' => 'Budi Regular',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        $this->securityUser = User::factory()->create(['name' => 'Andi Security']);
        $this->securityUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());
        Employee::create([
            'user_id' => $this->securityUser->id,
            'employee_code' => 'SEC-001',
            'name' => 'Andi Security',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
            'default_work_schedule_id' => WorkSchedule::where('code', 'security')->value('id'),
        ]);
    }

    public function test_schedule_preview_returns_regular_schedule(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.attendance.schedule-preview', [
            'user_id' => $this->regularUser->id,
            'date' => '2026-06-06',
        ]));

        $response->assertOk();
        $response->assertJson([
            'schedule_name' => 'Reguler',
            'schedule_code' => 'regular',
            'is_off' => false,
            'is_overnight' => false,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'next_day_clock_out' => false,
            'shift_type' => 'Same Day',
            'work_hours' => '09:00 - 18:00',
        ]);
    }

    public function test_schedule_preview_returns_security_schedule(): void
    {
        EmployeeSchedule::create([
            'employee_id' => $this->securityUser->employee->id,
            'work_schedule_id' => WorkSchedule::where('code', 'security')->value('id'),
            'work_date' => '2026-06-06',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.attendance.schedule-preview', [
            'user_id' => $this->securityUser->id,
            'date' => '2026-06-06',
        ]));

        $response->assertOk();
        $response->assertJson([
            'schedule_name' => 'Security',
            'schedule_code' => 'security',
            'is_off' => false,
            'is_overnight' => true,
            'clock_in' => '07:00',
            'clock_out' => '07:00',
            'next_day_clock_out' => true,
            'shift_type' => 'Overnight',
            'work_hours' => '07:00 - 07:00 (+1 hari)',
        ]);
    }

    public function test_security_schedule_preview_defaults_clock_out_date_to_next_day(): void
    {
        EmployeeSchedule::create([
            'employee_id' => $this->securityUser->employee->id,
            'work_schedule_id' => WorkSchedule::where('code', 'security')->value('id'),
            'work_date' => '2026-06-06',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.attendance.schedule-preview', [
            'user_id' => $this->securityUser->id,
            'date' => '2026-06-06',
        ]));

        $response->assertOk();
        $response->assertJsonPath('defaults.clock_in_time', '07:00');
        $response->assertJsonPath('defaults.clock_out_time', '07:00');
        $response->assertJsonPath('defaults.clock_out_date', '2026-06-07');
    }

    public function test_edit_schedule_preview_uses_attendance_work_schedule_first(): void
    {
        $regularSchedule = WorkSchedule::where('code', 'regular')->firstOrFail();
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        EmployeeSchedule::create([
            'employee_id' => $this->securityUser->employee->id,
            'work_schedule_id' => $securitySchedule->id,
            'work_date' => '2026-06-06',
        ]);

        $attendance = Attendance::create([
            'user_id' => $this->securityUser->id,
            'date' => '2026-06-06',
            'type' => 'regular',
            'status' => 'on_time',
            'work_schedule_id' => $regularSchedule->id,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.attendance.schedule-preview', [
            'user_id' => $this->securityUser->id,
            'date' => '2026-06-06',
            'attendance_id' => $attendance->id,
        ]));

        $response->assertOk();
        $response->assertJson([
            'schedule_code' => 'regular',
            'is_overnight' => false,
            'next_day_clock_out' => false,
            'shift_type' => 'Same Day',
        ]);
    }

    public function test_regular_schedule_preview_defaults_same_day_clock_out_date(): void
    {
        $response = $this->actingAs($this->admin)->getJson(route('admin.attendance.schedule-preview', [
            'user_id' => $this->regularUser->id,
            'date' => '2026-06-06',
        ]));

        $response->assertOk();
        $response->assertJsonPath('defaults.clock_in_time', '09:00');
        $response->assertJsonPath('defaults.clock_out_time', '18:00');
        $response->assertJsonPath('defaults.clock_out_date', '2026-06-06');
    }

    public function test_night_schedule_preview_defaults_clock_out_date_to_next_day(): void
    {
        $nightSchedule = WorkSchedule::updateOrCreate(
            ['code' => 'night'],
            [
                'name' => 'Security Malam',
                'clock_in_start' => '19:00:00',
                'late_limit' => '19:15:00',
                'clock_out_start' => '07:00:00',
                'clock_out_limit' => '07:15:00',
                'is_off' => false,
            ],
        );

        EmployeeSchedule::create([
            'employee_id' => $this->securityUser->employee->id,
            'work_schedule_id' => $nightSchedule->id,
            'work_date' => '2026-06-06',
        ]);

        $response = $this->actingAs($this->admin)->getJson(route('admin.attendance.schedule-preview', [
            'user_id' => $this->securityUser->id,
            'date' => '2026-06-06',
        ]));

        $response->assertOk();
        $response->assertJsonPath('next_day_clock_out', true);
        $response->assertJsonPath('defaults.clock_out_date', '2026-06-07');
    }
}
