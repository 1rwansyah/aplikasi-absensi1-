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
use App\Models\Role;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Services\PayrollAttendanceDateBreakdown;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PayrollAttendanceDatesTest extends TestCase
{
    use RefreshDatabase;

    private const PERIOD_MONTH = 6;

    private const PERIOD_YEAR = 2026;

    private const DAY_PRESENT = '2026-06-02';

    private const DAY_LATE = '2026-06-03';

    private const DAY_SICK_APPROVED = '2026-06-04';

    private const DAY_SICK_REJECTED = '2026-06-05';

    private const DAY_LEAVE = '2026-06-06';

    private const DAY_ABSENT = '2026-06-09';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        foreach ([
            self::DAY_PRESENT,
            self::DAY_LATE,
            self::DAY_SICK_APPROVED,
            self::DAY_SICK_REJECTED,
            self::DAY_LEAVE,
            self::DAY_ABSENT,
        ] as $date) {
            WorkCalendar::create([
                'date' => $date,
                'type' => WorkCalendarType::FullDay,
            ]);
        }
    }

    public function test_attendance_date_breakdown_matches_payroll_counts_and_dates(): void
    {
        $employee = $this->createEmployee();

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::DAY_PRESENT,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'clock_out_time' => '17:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::DAY_LATE,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:30:00',
            'clock_out_time' => '17:00:00',
            'status' => AttendanceStatus::Late,
        ]);

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::DAY_SICK_APPROVED,
            'type' => AttendanceType::Sick,
            'status' => AttendanceStatus::Sick,
            'verification_status' => VerificationStatus::Done,
            'leave_note' => 'Sakit dengan surat dokter',
        ]);

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::DAY_SICK_REJECTED,
            'type' => AttendanceType::Sick,
            'status' => AttendanceStatus::Sick,
            'verification_status' => VerificationStatus::NoDone,
            'leave_note' => 'Sakit tanpa bukti',
        ]);

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::DAY_LEAVE,
            'type' => AttendanceType::Permission,
            'status' => AttendanceStatus::Permission,
            'verification_status' => VerificationStatus::Done,
            'leave_note' => 'Keperluan keluarga',
        ]);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(2, $payroll->present_days);
        $this->assertSame(1, $payroll->late_days);
        $this->assertSame(2, $payroll->sick_days);
        $this->assertSame(1, $payroll->leave_days);
        $this->assertSame(1, $payroll->absent_days);

        $dates = app(PayrollAttendanceDateBreakdown::class)->forPayroll($payroll);

        $this->assertSame(
            [self::DAY_PRESENT, self::DAY_LATE],
            array_column($dates['present'], 'date')
        );
        $this->assertSame([self::DAY_LATE], array_column($dates['late'], 'date'));
        $this->assertSame(
            [self::DAY_SICK_APPROVED, self::DAY_SICK_REJECTED],
            array_column($dates['sick'], 'date')
        );
        $this->assertSame([self::DAY_LEAVE], array_column($dates['leave'], 'date'));
        $this->assertSame([self::DAY_ABSENT], array_column($dates['absent'], 'date'));

        $this->assertCount($payroll->present_days, $dates['present']);
        $this->assertCount($payroll->late_days, $dates['late']);
        $this->assertCount($payroll->sick_days, $dates['sick']);
        $this->assertCount($payroll->leave_days, $dates['leave']);
        $this->assertCount($payroll->absent_days, $dates['absent']);

        $this->assertSame('2 Juni 2026', $dates['present'][0]['label']);
        $this->assertSame('Disetujui', $dates['sick'][0]['note']);
        $this->assertSame('Ditolak', $dates['sick'][1]['note']);
    }

    public function test_payroll_show_page_includes_clickable_attendance_dates(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $employee = $this->createEmployee();

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::DAY_PRESENT,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => self::DAY_LEAVE,
            'type' => AttendanceType::Permission,
            'status' => AttendanceStatus::Permission,
            'verification_status' => VerificationStatus::Done,
            'leave_note' => 'Izin',
        ]);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();

        $response = $this->actingAs($admin)->get(route('payrolls.show', $payroll));

        $response->assertOk();
        $response->assertViewHas('attendanceDates', function (array $attendanceDates) {
            return array_column($attendanceDates['leave'], 'date') === [self::DAY_LEAVE]
                && array_column($attendanceDates['absent'], 'date') === [
                    self::DAY_LATE,
                    self::DAY_SICK_APPROVED,
                    self::DAY_SICK_REJECTED,
                    self::DAY_ABSENT,
                ];
        });
        $response->assertSee('data-attendance-dates-trigger', false);
        $response->assertSee('Tanggal Izin', false);
        $response->assertSee('6 Juni 2026', false);
        $response->assertSee('attendance-dates-modal', false);
    }

    public function test_my_payroll_show_includes_attendance_dates_for_owner(): void
    {
        $user = User::factory()->employee()->create();
        $user->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => Employee::generateCode(),
            'name' => $user->name,
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => self::DAY_SICK_APPROVED,
            'type' => AttendanceType::Sick,
            'status' => AttendanceStatus::Sick,
            'verification_status' => VerificationStatus::Done,
            'leave_note' => 'Sakit',
        ]);

        $this->generatePayroll();

        $payroll = Payroll::where('employee_id', $employee->id)->firstOrFail();
        $payroll->update(['status' => 'paid']);

        $response = $this->actingAs($user)->get(route('my-payrolls.show', $payroll));

        $response->assertOk();
        $response->assertViewHas('attendanceDates', function (array $attendanceDates) {
            return array_column($attendanceDates['sick'], 'date') === [self::DAY_SICK_APPROVED];
        });
        $response->assertSee('Tanggal Sakit', false);
        $response->assertSee('4 Juni 2026', false);
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
            'period_month' => self::PERIOD_MONTH,
            'period_year' => self::PERIOD_YEAR,
        ]);

        $this->actingAs(User::factory()->create());
        app(PayrollController::class)->generate($request);
    }
}
