<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\WorkCalendarType;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\Payroll;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkSchedule;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceCreateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, WorkScheduleSeeder::class]);

        $this->admin = User::factory()->admin()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->employeeUser = User::factory()->create(['name' => 'Budi Karyawan']);
        $this->employeeUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-001',
            'name' => 'Budi Karyawan',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        WorkCalendar::create([
            'date' => '2026-06-02',
            'type' => WorkCalendarType::FullDay,
        ]);

        WorkCalendar::create([
            'date' => '2026-06-06',
            'type' => WorkCalendarType::FullDay,
        ]);
    }

    public function test_admin_can_create_regular_manual_attendance(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
            'user_id' => $this->employeeUser->id,
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'shift' => 'day',
            'clock_in_time' => '09:00',
            'clock_out_date' => '2026-06-06',
            'clock_out_time' => '18:00',
            'status' => AttendanceStatus::OnTime->value,
            'clock_in_report' => '<p>Laporan masuk manual cukup panjang.</p>',
            'clock_out_report' => '<p>Laporan pulang manual cukup panjang.</p>',
            'manual_reason' => 'Lupa absen masuk dan pulang karena server bermasalah',
        ]);

        $response->assertRedirect(route('admin.attendance.index', ['date' => '2026-06-06']));
        $response->assertSessionHas('success');

        $attendance = Attendance::where('user_id', $this->employeeUser->id)
            ->whereDate('date', '2026-06-06')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertSame('09:00:00', $attendance->clock_in_time);
        $this->assertSame('18:00:00', $attendance->clock_out_time);
        $this->assertNotNull($attendance->work_schedule_id);
    }

    public function test_cannot_create_duplicate_attendance_for_user_and_date(): void
    {
        Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'clock_in_time' => '09:00:00',
        ]);

        $response = $this->actingAs($this->admin)->from(route('admin.attendance.index', ['date' => '2026-06-06']))
            ->post(route('admin.attendance.store'), [
                'user_id' => $this->employeeUser->id,
                'clock_in_date' => '2026-06-06',
                'type' => AttendanceType::Regular->value,
                'clock_in_time' => '10:00',
                'status' => AttendanceStatus::OnTime->value,
                'manual_reason' => 'Percobaan duplicate attendance record',
            ]);

        $response->assertRedirect(route('admin.attendance.index', ['date' => '2026-06-06']));
        $response->assertSessionHasErrors('clock_in_date');
        $this->assertSame(1, Attendance::where('user_id', $this->employeeUser->id)->count());
    }

    public function test_cannot_create_attendance_when_payroll_is_paid(): void
    {
        Payroll::create([
            'employee_id' => $this->employeeUser->employee->id,
            'period_month' => 6,
            'period_year' => 2026,
            'work_days' => 1,
            'present_days' => 0,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
            'user_id' => $this->employeeUser->id,
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Koreksi HR setelah payroll lunas',
        ]);

        $response->assertRedirect(route('admin.attendance.index', ['date' => '2026-06-06', 'manual' => 1]));
        $response->assertSessionHas('error');
        $this->assertSame(0, Attendance::count());
    }

    public function test_admin_can_create_sick_attendance_without_clock_times(): void
    {
        $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
            'user_id' => $this->employeeUser->id,
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Sick->value,
            'status' => AttendanceStatus::Sick->value,
            'leave_note' => 'Demam tinggi, surat dokter diserahkan ke HR.',
            'manual_reason' => 'Input manual sakit oleh HR',
        ])->assertRedirect();

        $attendance = Attendance::where('user_id', $this->employeeUser->id)->firstOrFail();
        $this->assertSame(AttendanceType::Sick, $attendance->type);
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertNull($attendance->clock_in_time);
        $this->assertNull($attendance->clock_out_time);
        $this->assertSame(0.0, $attendance->overtime_hours);
    }

    public function test_admin_can_create_permission_attendance_without_clock_times(): void
    {
        $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
            'user_id' => $this->employeeUser->id,
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Permission->value,
            'status' => AttendanceStatus::Permission->value,
            'leave_note' => 'Urusan keluarga mendadak di luar kota.',
            'manual_reason' => 'Input manual izin oleh HR',
        ])->assertRedirect();

        $attendance = Attendance::where('user_id', $this->employeeUser->id)->firstOrFail();
        $this->assertSame(AttendanceType::Permission, $attendance->type);
        $this->assertNull($attendance->clock_in_time);
    }

    public function test_admin_can_create_security_manual_attendance_on_shift_start_date(): void
    {
        $securityUser = $this->createSecurityEmployee('Andi Security', 'SEC-001');
        $this->seedSecurityShift($securityUser->employee, '2026-06-06');

        $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
            'user_id' => $securityUser->id,
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'shift' => 'day',
            'clock_in_time' => '07:00',
            'clock_out_date' => '2026-06-07',
            'clock_out_time' => '07:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Input manual shift security 24 jam',
        ])->assertRedirect(route('admin.attendance.index', ['date' => '2026-06-06']));

        $attendance = Attendance::where('user_id', $securityUser->id)->first();
        $this->assertNotNull($attendance);
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertSame('07:00:00', $attendance->clock_in_time);
        $this->assertSame('07:00:00', $attendance->clock_out_time);
        $this->assertSame(
            WorkSchedule::where('code', 'security')->value('id'),
            $attendance->work_schedule_id,
        );
        $this->assertSame(0.0, $attendance->overtime_hours);
        $this->assertSame(1, Attendance::where('user_id', $securityUser->id)->count());
        $this->assertFalse(
            Attendance::query()
                ->where('user_id', $securityUser->id)
                ->whereDate('date', '2026-06-07')
                ->exists(),
        );
    }

    public function test_security_manual_attendance_calculates_overnight_overtime(): void
    {
        $securityUser = $this->createSecurityEmployee('Rina Security', 'SEC-002');
        $this->seedSecurityShift($securityUser->employee, '2026-06-06');

        $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
            'user_id' => $securityUser->id,
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '07:00',
            'clock_out_date' => '2026-06-07',
            'clock_out_time' => '09:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Koreksi HR pulang lembur security',
        ])->assertRedirect();

        $attendance = Attendance::where('user_id', $securityUser->id)->firstOrFail();
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertSame(1.0, $attendance->overtime_hours);
        $this->assertSame(0, Attendance::whereDate('date', '2026-06-07')->count());
    }

    public function test_regular_manual_attendance_rejects_clock_out_before_clock_in(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.attendance.index', ['date' => '2026-06-06']))
            ->post(route('admin.attendance.store'), [
                'user_id' => $this->employeeUser->id,
                'clock_in_date' => '2026-06-06',
                'type' => AttendanceType::Regular->value,
                'clock_in_time' => '09:00',
                'clock_out_date' => '2026-06-06',
                'clock_out_time' => '08:00',
                'status' => AttendanceStatus::OnTime->value,
                'manual_reason' => 'Percobaan jam pulang sebelum masuk',
            ]);

        $response->assertRedirect(route('admin.attendance.index', ['date' => '2026-06-06']));
        $response->assertSessionHasErrors('clock_out_time');
        $this->assertSame(0, Attendance::count());
    }

    public function test_create_writes_activity_log(): void
    {
        $this->actingAs($this->admin)->post(route('admin.attendance.store'), [
            'user_id' => $this->employeeUser->id,
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'clock_out_date' => '2026-06-06',
            'clock_out_time' => '18:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Face verification gagal saat absen',
        ])->assertRedirect();

        $attendance = Attendance::firstOrFail();

        $log = ActivityLog::query()
            ->where('subject_type', Attendance::class)
            ->where('subject_id', $attendance->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('create', $log->action);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertStringContainsString('Face verification gagal saat absen', $log->description);
        $this->assertStringContainsString('"after"', $log->description);
        $this->assertStringContainsString('clock_out_date', $log->description);
        $this->assertStringContainsString('Budi Karyawan', $log->description);
    }

    private function createSecurityEmployee(string $name, string $code): User
    {
        $user = User::factory()->create(['name' => $name]);
        $user->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'name' => $name,
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
            'default_work_schedule_id' => WorkSchedule::where('code', 'security')->value('id'),
        ]);

        $user->setRelation('employee', $employee);

        return $user;
    }

    private function seedSecurityShift(Employee $employee, string $shiftStartDate): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();

        EmployeeSchedule::create([
            'employee_id' => $employee->id,
            'work_schedule_id' => $securitySchedule->id,
            'work_date' => $shiftStartDate,
        ]);
    }
}
