<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignSecurityWeekSchedulesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
    }

    public function test_assigns_security_week_schedules_for_employee(): void
    {
        $employee = $this->createEmployee('ID-001');

        $this->artisan('schedules:assign-security-week', [
            'week-start' => '2026-06-02',
            '--employee' => [
                'ID-001:security,off,security,off,security,security,off',
            ],
        ])->assertSuccessful();

        $securityId = WorkSchedule::where('code', 'security')->value('id');
        $offId = WorkSchedule::where('code', 'off')->value('id');

        $this->assertSame(
            $securityId,
            EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', '2026-06-02')->value('work_schedule_id'),
        );
        $this->assertSame(
            $offId,
            EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', '2026-06-03')->value('work_schedule_id'),
        );
        $this->assertSame(
            $offId,
            EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', '2026-06-08')->value('work_schedule_id'),
        );

        $this->assertSame(7, EmployeeSchedule::where('employee_id', $employee->id)->count());
    }

    public function test_updates_existing_assignment_with_update_or_create(): void
    {
        $employee = $this->createEmployee('ID-002');
        $offId = WorkSchedule::where('code', 'off')->value('id');

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $offId,
            'work_date' => '2026-06-02',
        ]);

        $this->artisan('schedules:assign-security-week', [
            'week-start' => '2026-06-02',
            '--employee' => [
                'ID-002:security,off,security,off,security,security,off',
            ],
        ])->assertSuccessful();

        $this->assertSame(
            WorkSchedule::where('code', 'security')->value('id'),
            EmployeeSchedule::where('employee_id', $employee->id)->whereDate('work_date', '2026-06-02')->value('work_schedule_id'),
        );
        $this->assertSame(7, EmployeeSchedule::where('employee_id', $employee->id)->count());
    }

    public function test_fails_when_schedule_code_is_invalid(): void
    {
        $this->createEmployee('ID-003');

        $this->artisan('schedules:assign-security-week', [
            'week-start' => '2026-06-02',
            '--employee' => [
                'ID-003:security,day,off,security,off,security,security',
            ],
        ])->assertFailed();

        $this->assertSame(0, EmployeeSchedule::count());
    }

    public function test_fails_when_employee_not_found(): void
    {
        $this->artisan('schedules:assign-security-week', [
            'week-start' => '2026-06-02',
            '--employee' => [
                'ID-999:security,off,security,off,security,security,off',
            ],
        ])->assertFailed();

        $this->assertSame(0, EmployeeSchedule::count());
    }

    public function test_fails_when_pattern_does_not_have_seven_days(): void
    {
        $this->createEmployee('ID-004');

        $this->artisan('schedules:assign-security-week', [
            'week-start' => '2026-06-02',
            '--employee' => [
                'ID-004:security,off,security',
            ],
        ])->assertFailed();

        $this->assertSame(0, EmployeeSchedule::count());
    }

    private function createEmployee(string $code): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'name' => $user->name,
            'employment_status' => 'active',
            'basic_salary' => 1000000,
        ]);
    }
}
