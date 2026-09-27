<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\EmployeeScheduleService;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeScheduleService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
        $this->service = app(EmployeeScheduleService::class);
    }

    public function test_falls_back_to_regular_when_no_assignment(): void
    {
        $employee = $this->createEmployee();

        $schedule = $this->service->getTodaySchedule($employee);

        $this->assertSame('regular', $schedule->code);
    }

    public function test_security_staff_falls_back_to_security_schedule_without_assignment(): void
    {
        $employee = $this->createEmployee(staff: 'Security');

        $schedule = $this->service->getTodaySchedule($employee);

        $this->assertSame('security', $schedule->code);
    }

    public function test_uses_daily_assignment_over_default_and_regular(): void
    {
        $employee = $this->createEmployee();
        $security = WorkSchedule::where('code', 'security')->firstOrFail();

        $employee->update([
            'default_work_schedule_id' => WorkSchedule::where('code', 'off')->value('id'),
        ]);

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $security->id,
            'work_date' => today(),
        ]);

        $schedule = $this->service->getTodaySchedule($employee);

        $this->assertSame('security', $schedule->code);
    }

    public function test_uses_default_work_schedule_when_no_daily_assignment(): void
    {
        $employee = $this->createEmployee();
        $security = WorkSchedule::where('code', 'security')->firstOrFail();

        $employee->update(['default_work_schedule_id' => $security->id]);

        $schedule = $this->service->getTodaySchedule($employee);

        $this->assertSame('security', $schedule->code);
    }

    public function test_detects_off_day(): void
    {
        $employee = $this->createEmployee();
        $off = WorkSchedule::where('code', 'off')->firstOrFail();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $off->id,
            'work_date' => today(),
        ]);

        $this->assertTrue($this->service->isOffDay($employee, today()));
    }

    private function createEmployee(string $staff = 'Office'): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => Employee::generateCode(),
            'name' => $user->name,
            'employment_status' => 'active',
            'staff' => $staff,
            'basic_salary' => 1000000,
        ]);
    }
}
