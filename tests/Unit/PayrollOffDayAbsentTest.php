<?php

namespace Tests\Unit;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\WorkCalendarType;
use App\Http\Controllers\PayrollController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Payroll;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkSchedule;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PayrollOffDayAbsentTest extends TestCase
{
    use RefreshDatabase;

    private const PERIOD_MONTH = 6;

    private const PERIOD_YEAR = 2026;

    private const WORK_DAY_ONE = '2026-06-02';

    private const WORK_DAY_TWO = '2026-06-03';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
        $this->seedWorkCalendarDays();
    }

    public function test_off_day_reduces_work_days_and_is_not_counted_as_absent(): void
    {
        $employee = $this->createActiveEmployee();
        $offSchedule = WorkSchedule::where('code', 'off')->firstOrFail();
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $offSchedule->id,
            'work_date' => self::WORK_DAY_ONE,
        ]);

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $securitySchedule->id,
            'work_date' => self::WORK_DAY_TWO,
        ]);

        $this->createPresentAttendance($employee->user_id, self::WORK_DAY_TWO);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(1.0, (float) $payroll->work_days);
        $this->assertSame(1, $payroll->present_days);
        $this->assertSame(0, $payroll->absent_days);
    }

    public function test_missing_attendance_counts_as_absent_when_no_off_schedule(): void
    {
        $employee = $this->createActiveEmployee();

        $this->createPresentAttendance($employee->user_id, self::WORK_DAY_ONE);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(2.0, (float) $payroll->work_days);
        $this->assertSame(1, $payroll->present_days);
        $this->assertSame(1, $payroll->absent_days);
    }

    public function test_late_out_attendance_counts_as_present_not_absent(): void
    {
        $employee = $this->createActiveEmployee();

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::WORK_DAY_ONE,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '19:00:00',
            'status' => AttendanceStatus::LateOut,
        ]);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(2.0, (float) $payroll->work_days);
        $this->assertSame(1, $payroll->present_days);
        $this->assertSame(1, $payroll->absent_days);
    }

    public function test_clock_in_without_clock_out_counts_as_present(): void
    {
        $employee = $this->createActiveEmployee();

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::WORK_DAY_ONE,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(1, $payroll->present_days);
        $this->assertSame(1, $payroll->absent_days);
    }

    public function test_early_out_attendance_counts_as_present_not_absent(): void
    {
        $employee = $this->createActiveEmployee();

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::WORK_DAY_ONE,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '16:00:00',
            'status' => AttendanceStatus::EarlyOut,
        ]);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(1, $payroll->present_days);
        $this->assertSame(1, $payroll->absent_days);
    }

    private function seedWorkCalendarDays(): void
    {
        WorkCalendar::create([
            'date' => self::WORK_DAY_ONE,
            'type' => WorkCalendarType::FullDay,
        ]);

        WorkCalendar::create([
            'date' => self::WORK_DAY_TWO,
            'type' => WorkCalendarType::FullDay,
        ]);
    }

    private function createActiveEmployee(): Employee
    {
        $user = User::factory()->create();

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => Employee::generateCode(),
            'name' => $user->name,
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);
    }

    private function createPresentAttendance(int $userId, string $date): void
    {
        Attendance::create([
            'user_id' => $userId,
            'date' => $date,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '07:00:00',
            'clock_out_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);
    }

    private function generatePayroll(): void
    {
        $request = Request::create('/payrolls/generate', 'POST', [
            'period_month' => self::PERIOD_MONTH,
            'period_year' => self::PERIOD_YEAR,
        ]);

        app(PayrollController::class)->generate($request);
    }
}
