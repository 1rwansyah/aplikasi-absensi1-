<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Enums\WorkCalendarType;
use App\Http\Controllers\PayrollController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\User;
use App\Models\WorkCalendar;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RejectedSickPayrollTest extends TestCase
{
    use RefreshDatabase;

    private const WORK_DAY = '2026-06-02';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        WorkCalendar::create([
            'date' => self::WORK_DAY,
            'type' => WorkCalendarType::FullDay,
        ]);
    }

    public function test_rejected_sick_has_no_meal_allowance_and_no_alpha_deduction(): void
    {
        $employee = $this->createEmployee();

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::WORK_DAY,
            'type' => AttendanceType::Sick,
            'status' => AttendanceStatus::Sick,
            'verification_status' => VerificationStatus::NoDone,
            'leave_note' => 'Demam tinggi',
        ]);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(0, $payroll->present_days);
        $this->assertSame(0, $payroll->absent_days);
        $this->assertSame(1, $payroll->sick_days);

        $mealDetail = PayrollDetail::where('payroll_id', $payroll->id)->where('name', 'Uang Makan')->first();
        $this->assertNotNull($mealDetail);
        $this->assertSame(0.0, (float) $mealDetail->amount);
        $this->assertStringContainsString('sakit ditolak', (string) $mealDetail->notes);

        $this->assertNull(
            PayrollDetail::where('payroll_id', $payroll->id)->where('name', 'Potongan Alfa')->first()
        );
    }

    public function test_present_day_earns_meal_allowance_compared_to_rejected_sick(): void
    {
        $presentEmployee = $this->createEmployee();
        $rejectedEmployee = $this->createEmployee();

        Attendance::create([
            'user_id' => $presentEmployee->user_id,
            'date' => self::WORK_DAY,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        Attendance::create([
            'user_id' => $rejectedEmployee->user_id,
            'date' => self::WORK_DAY,
            'type' => AttendanceType::Sick,
            'status' => AttendanceStatus::Sick,
            'verification_status' => VerificationStatus::NoDone,
            'leave_note' => 'Demam tinggi',
        ]);

        $this->generatePayroll();

        $presentMeal = (float) PayrollDetail::whereHas('payroll', fn ($q) => $q->where('employee_id', $presentEmployee->id))
            ->where('name', 'Uang Makan')
            ->value('amount');

        $rejectedMeal = (float) PayrollDetail::whereHas('payroll', fn ($q) => $q->where('employee_id', $rejectedEmployee->id))
            ->where('name', 'Uang Makan')
            ->value('amount');

        $this->assertSame(20000.0, $presentMeal);
        $this->assertSame(0.0, $rejectedMeal);
        $this->assertSame(20000.0, $presentMeal - $rejectedMeal);
    }

    public function test_approved_sick_earns_meal_allowance(): void
    {
        $employee = $this->createEmployee();

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::WORK_DAY,
            'type' => AttendanceType::Sick,
            'status' => AttendanceStatus::Sick,
            'verification_status' => VerificationStatus::Done,
            'leave_note' => 'Demam dengan surat dokter',
        ]);

        $this->generatePayroll();

        $mealDetail = PayrollDetail::whereHas('payroll', fn ($q) => $q->where('employee_id', $employee->id))
            ->where('name', 'Uang Makan')
            ->firstOrFail();

        $this->assertSame(20000.0, (float) $mealDetail->amount);
        $this->assertStringContainsString('sakit disetujui', (string) $mealDetail->notes);
        $this->assertStringNotContainsString('sakit ditolak', (string) $mealDetail->notes);
    }

    private function createEmployee(): Employee
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

    private function generatePayroll(): void
    {
        $request = Request::create('/payrolls/generate', 'POST', [
            'period_month' => 6,
            'period_year' => 2026,
        ]);

        $this->actingAs(User::factory()->create());
        app(PayrollController::class)->generate($request);
    }
}
