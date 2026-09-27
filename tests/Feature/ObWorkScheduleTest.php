<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\EmployeeScheduleService;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ObWorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([WorkScheduleSeeder::class, RoleSeeder::class]);

        WorkSchedule::query()->firstOrCreate(
            ['code' => 'ob'],
            [
                'name' => 'OB',
                'clock_in_start' => '07:30:00',
                'late_limit' => '07:30:00',
                'clock_out_start' => '18:00:00',
                'clock_out_limit' => '18:30:00',
                'is_off' => false,
            ]
        )->update([
            'clock_in_start' => '07:30:00',
            'late_limit' => '07:30:00',
            'clock_out_start' => '18:00:00',
            'clock_out_limit' => '18:30:00',
        ]);
    }

    public function test_ob_schedule_has_business_hours(): void
    {
        $ob = WorkSchedule::where('code', 'ob')->firstOrFail();

        $this->assertSame('07:30:00', $ob->clock_in_start);
        $this->assertSame('07:30:00', $ob->late_limit);
        $this->assertSame('18:00:00', $ob->clock_out_start);
        $this->assertSame('18:30:00', $ob->clock_out_limit);
    }

    public function test_ob_employee_uses_ob_schedule_when_linked(): void
    {
        $employee = $this->createObEmployee(linkSchedule: true);
        $schedule = app(EmployeeScheduleService::class)->getTodaySchedule($employee);

        $this->assertSame('ob', $schedule->code);
    }

    public function test_ob_employee_without_link_falls_back_to_regular(): void
    {
        $employee = $this->createObEmployee(linkSchedule: false);
        $schedule = app(EmployeeScheduleService::class)->getTodaySchedule($employee);

        $this->assertSame('regular', $schedule->code);
    }

    public function test_ob_clock_in_and_clock_out_windows(): void
    {
        $employee = $this->createObEmployee(linkSchedule: true);
        $shift = app(EmployeeScheduleService::class)->getTodaySchedule($employee)->toShiftSchedule();
        $date = Carbon::parse('2026-06-15');

        $this->assertFalse($shift->isLate(Carbon::parse('2026-06-15 07:25:00')));
        $this->assertTrue($shift->isLate(Carbon::parse('2026-06-15 07:35:00')));
        $this->assertTrue($shift->isEarlyClockOut(Carbon::parse('2026-06-15 17:50:00'), $date));
        $this->assertFalse($shift->isEarlyClockOut(Carbon::parse('2026-06-15 18:05:00'), $date));
    }

    public function test_store_employee_auto_assigns_ob_schedule_from_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->roles()->sync(
            \App\Models\Role::whereIn('name', ['admin'])->pluck('id')
        );

        $response = $this->actingAs($admin)->postJson(route('employees.store'), [
            'name' => 'OB Baru',
            'email' => 'ob-baru@example.com',
            'staff' => 'OB',
            'position' => 'Petugas Kebersihan',
            'employment_status' => 'active',
            'basic_salary' => 1000000,
        ]);

        $response->assertOk();

        $employee = Employee::where('email', 'ob-baru@example.com')->firstOrFail();
        $obId = WorkSchedule::where('code', 'ob')->value('id');

        $this->assertSame($obId, $employee->default_work_schedule_id);
    }

    public function test_store_employee_auto_assigns_ob_schedule_from_position(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->roles()->sync(
            \App\Models\Role::whereIn('name', ['admin'])->pluck('id')
        );

        $this->actingAs($admin)->postJson(route('employees.store'), [
            'name' => 'OB Position',
            'email' => 'ob-pos@example.com',
            'staff' => 'Operasional',
            'position' => 'OB',
            'employment_status' => 'active',
            'basic_salary' => 1000000,
        ])->assertOk();

        $employee = Employee::where('email', 'ob-pos@example.com')->firstOrFail();
        $obId = WorkSchedule::where('code', 'ob')->value('id');

        $this->assertSame($obId, $employee->default_work_schedule_id);
    }

    private function createObEmployee(bool $linkSchedule): Employee
    {
        $user = User::factory()->create();
        $obId = WorkSchedule::where('code', 'ob')->value('id');

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => Employee::generateCode(),
            'name' => $user->name,
            'staff' => 'OB',
            'position' => 'OB',
            'employment_status' => 'active',
            'basic_salary' => 1000000,
            'default_work_schedule_id' => $linkSchedule ? $obId : null,
        ]);
    }
}
