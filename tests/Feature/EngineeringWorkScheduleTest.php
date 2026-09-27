<?php

namespace Tests\Feature;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use App\Services\EmployeeScheduleService;
use App\Support\AppTime;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EngineeringWorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([WorkScheduleSeeder::class, RoleSeeder::class]);

        WorkSchedule::query()->firstOrCreate(
            ['code' => 'engineering'],
            [
                'name' => 'Staff Engineering',
                'clock_in_start' => '00:00:00',
                'late_limit' => '00:15:00',
                'clock_out_start' => '05:00:00',
                'clock_out_limit' => '05:15:00',
                'is_off' => false,
            ]
        )->update([
            'clock_in_start' => '00:00:00',
            'late_limit' => '00:15:00',
            'clock_out_start' => '05:00:00',
            'clock_out_limit' => '05:15:00',
        ]);
    }

    public function test_engineering_schedule_exists_with_hrd_default_hours(): void
    {
        $schedule = WorkSchedule::query()->where('code', 'engineering')->firstOrFail();

        $this->assertSame('Staff Engineering', $schedule->name);
        $this->assertSame('00:00:00', $schedule->clock_in_start);
        $this->assertSame('00:15:00', $schedule->late_limit);
        $this->assertSame('05:00:00', $schedule->clock_out_start);
    }

    public function test_sunday_is_off_day_for_engineering_employees(): void
    {
        $employee = $this->createEngineeringEmployee();
        $service = app(EmployeeScheduleService::class);

        $monday = Carbon::parse('2026-06-01');
        $sunday = Carbon::parse('2026-06-07');

        $this->assertFalse($service->isOffDay($employee, $monday));
        $this->assertTrue($service->isOffDay($employee, $sunday));
        $this->assertTrue($service->allowsAdminManualAttendance($employee, $sunday));
        $this->assertSame('engineering', $service->scheduleForAdminManualAttendance($employee, $sunday)->code);
    }

    public function test_admin_can_create_manual_attendance_for_engineering_on_sunday(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->roles()->sync(
            \App\Models\Role::whereIn('name', ['admin'])->pluck('id')
        );

        $employee = $this->createEngineeringEmployee();
        $sunday = '2026-06-07';

        $response = $this->actingAs($admin)->post(route('admin.attendance.store'), [
            'user_id' => $employee->user_id,
            'clock_in_date' => $sunday,
            'type' => AttendanceType::Regular->value,
            'shift' => 'day',
            'clock_in_time' => '09:00',
            'clock_out_date' => $sunday,
            'clock_out_time' => '15:00',
            'status' => AttendanceStatus::OnTime->value,
            'clock_in_report' => '<p>Laporan masuk lembur minggu cukup panjang.</p>',
            'clock_out_report' => '<p>Laporan pulang lembur minggu cukup panjang.</p>',
            'manual_reason' => 'Lembur minggu Staff Engineering sesuai instruksi HRD',
        ]);

        $response->assertRedirect(route('admin.attendance.index', ['date' => $sunday]));
        $response->assertSessionHas('success');

        $attendance = Attendance::where('user_id', $employee->user_id)
            ->whereDate('date', $sunday)
            ->firstOrFail();

        $this->assertSame(AttendanceStatus::OnTime, $attendance->status);
        $this->assertSame(
            WorkSchedule::query()->where('code', 'engineering')->value('id'),
            $attendance->work_schedule_id
        );
    }

    public function test_explicit_off_assignment_still_blocks_engineering_manual_on_sunday(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->roles()->sync(
            \App\Models\Role::whereIn('name', ['admin'])->pluck('id')
        );

        $employee = $this->createEngineeringEmployee();
        $sunday = '2026-06-07';
        $offId = WorkSchedule::query()->where('code', 'off')->value('id');

        \App\Models\EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $offId,
            'work_date' => $sunday,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.attendance.index', ['date' => $sunday]))
            ->post(route('admin.attendance.store'), [
                'user_id' => $employee->user_id,
                'clock_in_date' => $sunday,
                'type' => AttendanceType::Regular->value,
                'shift' => 'day',
                'clock_in_time' => '09:00',
                'clock_out_date' => $sunday,
                'clock_out_time' => '15:00',
                'status' => AttendanceStatus::OnTime->value,
                'clock_in_report' => '<p>Laporan masuk manual cukup panjang.</p>',
                'clock_out_report' => '<p>Laporan pulang manual cukup panjang.</p>',
                'manual_reason' => 'Coba absen di hari off eksplisit',
            ]);

        $response->assertRedirect(route('admin.attendance.index', ['date' => $sunday]));
        $response->assertSessionHasErrors('clock_in_date');
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $employee->user_id,
            'date' => $sunday,
        ]);
    }

    public function test_overtime_starts_at_six_after_five_am_clock_out(): void
    {
        $engineering = WorkSchedule::query()->where('code', 'engineering')->firstOrFail();
        $user = User::factory()->create();

        $dutyDate = Carbon::parse('2026-06-02');
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $engineering->id,
            'date' => $dutyDate,
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '00:05:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $service = app(AttendanceService::class);

        $atSix = Carbon::parse('2026-06-02 06:00:00', AppTime::timezone());
        $atSixThirty = Carbon::parse('2026-06-02 06:30:00', AppTime::timezone());

        $this->assertSame(0.0, $service->overtimeHoursFor($attendance, $atSix));
        $this->assertSame(0.5, $service->overtimeHoursFor($attendance, $atSixThirty));
    }

    public function test_store_employee_auto_assigns_engineering_schedule_from_staff(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->roles()->sync(
            \App\Models\Role::whereIn('name', ['admin'])->pluck('id')
        );

        $response = $this->actingAs($admin)->postJson(route('employees.store'), [
            'name' => 'Eng Staff',
            'email' => 'eng@example.com',
            'staff' => 'Engineering',
            'position' => 'Staff Engineering',
            'employment_status' => 'active',
            'basic_salary' => 1000000,
        ]);

        $response->assertOk();

        $employee = Employee::where('email', 'eng@example.com')->firstOrFail();
        $engineeringId = WorkSchedule::query()->where('code', 'engineering')->value('id');

        $this->assertSame($engineeringId, $employee->default_work_schedule_id);
    }

    private function createEngineeringEmployee(): Employee
    {
        $user = User::factory()->create();
        $engineeringId = WorkSchedule::query()->where('code', 'engineering')->value('id');

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => Employee::generateCode(),
            'name' => $user->name,
            'staff' => 'Engineering',
            'position' => 'Staff Engineering',
            'employment_status' => 'active',
            'basic_salary' => 1000000,
            'default_work_schedule_id' => $engineeringId,
        ]);
    }
}
