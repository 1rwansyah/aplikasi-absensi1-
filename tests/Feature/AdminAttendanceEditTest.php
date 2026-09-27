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

class AdminAttendanceEditTest extends TestCase
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

    public function test_admin_can_update_regular_manual_attendance(): void
    {
        $attendance = $this->createRegularAttendance([
            'date' => '2026-06-06',
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
        ]);

        $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'clock_out_date' => '2026-06-06',
            'clock_out_time' => '18:30',
            'status' => AttendanceStatus::OnTime->value,
            'clock_in_report' => '<p>Laporan masuk karyawan hari ini sudah lengkap.</p>',
            'clock_out_report' => '<p>Laporan pulang karyawan hari ini sudah lengkap.</p>',
            'manual_reason' => 'Koreksi HR - update jam pulang manual',
        ])->assertRedirect(route('admin.attendance.index', ['date' => '2026-06-06']));

        $attendance->refresh();
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertSame('18:30:00', $attendance->clock_out_time);
    }

    public function test_edit_clock_out_recalculates_overtime_hours(): void
    {
        $attendance = $this->createRegularAttendance([
            'date' => '2026-06-02',
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'overtime_hours' => 0,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-02',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'clock_out_date' => '2026-06-02',
            'clock_out_time' => '20:30',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Koreksi HR - tambah jam pulang lembur',
        ])->assertRedirect();

        $attendance->refresh();
        $this->assertSame(1.5, $attendance->overtime_hours);
    }

    public function test_paid_payroll_blocks_attendance_edit(): void
    {
        $attendance = $this->createRegularAttendance([
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
        ]);

        Payroll::create([
            'employee_id' => $this->employeeUser->employee->id,
            'period_month' => 6,
            'period_year' => 2026,
            'work_days' => 1,
            'present_days' => 1,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-02',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'clock_out_date' => '2026-06-02',
            'clock_out_time' => '20:30',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Koreksi HR setelah payroll lunas',
        ]);

        $response->assertRedirect(route('admin.attendance.index', [
            'date' => '2026-06-02',
            'edit' => $attendance->id,
        ]));
        $response->assertSessionHas('error');

        $attendance->refresh();
        $this->assertSame('18:00:00', $attendance->clock_out_time);
    }

    public function test_cannot_edit_to_duplicate_user_and_date(): void
    {
        $existing = $this->createRegularAttendance([
            'date' => '2026-06-02',
            'clock_in_time' => '09:00:00',
        ]);

        $other = Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'clock_in_time' => '09:00:00',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.attendance.update', $other), [
            'clock_in_date' => '2026-06-02',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Percobaan pindah ke tanggal duplicate',
        ]);

        $response->assertRedirect(route('admin.attendance.index', [
            'date' => '2026-06-02',
            'edit' => $other->id,
        ]));
        $response->assertSessionHasErrors('clock_in_date');

        $existing->refresh();
        $other->refresh();
        $this->assertSame('2026-06-02', $existing->date->toDateString());
        $this->assertSame('2026-06-06', $other->date->toDateString());
    }

    public function test_edit_regular_rejects_clock_out_before_clock_in(): void
    {
        $attendance = $this->createRegularAttendance([
            'date' => '2026-06-06',
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'clock_out_date' => '2026-06-06',
            'clock_out_time' => '08:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Percobaan koreksi jam invalid',
        ]);

        $response->assertRedirect(route('admin.attendance.index', [
            'date' => '2026-06-06',
            'edit' => $attendance->id,
        ]));
        $response->assertSessionHasErrors('clock_out_time');

        $attendance->refresh();
        $this->assertSame('18:00:00', $attendance->clock_out_time);
    }

    public function test_admin_can_edit_sick_attendance_without_clock_times(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Sick,
            'status' => AttendanceStatus::Sick,
            'leave_note' => 'Demam ringan.',
        ]);

        $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Sick->value,
            'status' => AttendanceStatus::Sick->value,
            'leave_note' => 'Demam tinggi, surat dokter diserahkan ke HR.',
            'manual_reason' => 'Update keterangan sakit oleh HR',
        ])->assertRedirect();

        $attendance->refresh();
        $this->assertSame(AttendanceType::Sick, $attendance->type);
        $this->assertNull($attendance->clock_in_time);
        $this->assertNull($attendance->clock_out_time);
        $this->assertSame(0.0, $attendance->overtime_hours);
    }

    public function test_admin_can_edit_permission_attendance_without_clock_times(): void
    {
        $attendance = Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Permission,
            'status' => AttendanceStatus::Permission,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Permission->value,
            'status' => AttendanceStatus::Permission->value,
            'leave_note' => 'Urusan keluarga mendadak di luar kota.',
            'manual_reason' => 'Update keterangan izin oleh HR',
        ])->assertRedirect();

        $attendance->refresh();
        $this->assertSame(AttendanceType::Permission, $attendance->type);
        $this->assertNull($attendance->clock_in_time);
    }

    public function test_edit_security_manual_attendance_overnight_normal(): void
    {
        $securityUser = $this->createSecurityEmployee('Andi Security', 'SEC-001');
        $this->seedSecurityShift($securityUser->employee, '2026-06-06');

        $attendance = Attendance::create([
            'user_id' => $securityUser->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'work_schedule_id' => WorkSchedule::where('code', 'security')->value('id'),
            'clock_in_time' => '07:00:00',
            'clock_out_time' => '06:00:00',
            'overtime_hours' => 2,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '07:00',
            'clock_out_date' => '2026-06-07',
            'clock_out_time' => '07:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Koreksi HR shift security normal',
        ])->assertRedirect(route('admin.attendance.index', ['date' => '2026-06-06']));

        $attendance->refresh();
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertSame('07:00:00', $attendance->clock_out_time);
        $this->assertSame(0.0, $attendance->overtime_hours);
        $this->assertSame(1, Attendance::where('user_id', $securityUser->id)->count());
        $this->assertFalse(
            Attendance::query()
                ->where('user_id', $securityUser->id)
                ->whereDate('date', '2026-06-07')
                ->exists(),
        );
    }

    public function test_edit_security_manual_attendance_overnight_overtime(): void
    {
        $securityUser = $this->createSecurityEmployee('Rina Security', 'SEC-002');
        $this->seedSecurityShift($securityUser->employee, '2026-06-06');

        $attendance = Attendance::create([
            'user_id' => $securityUser->id,
            'date' => '2026-06-06',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'work_schedule_id' => WorkSchedule::where('code', 'security')->value('id'),
            'clock_in_time' => '07:00:00',
            'clock_out_time' => '07:00:00',
            'overtime_hours' => 0,
        ]);

        $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-06',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '07:00',
            'clock_out_date' => '2026-06-07',
            'clock_out_time' => '09:00',
            'status' => AttendanceStatus::OnTime->value,
            'manual_reason' => 'Koreksi HR pulang lembur security',
        ])->assertRedirect();

        $attendance->refresh();
        $this->assertSame('2026-06-06', $attendance->date->toDateString());
        $this->assertSame(1.0, $attendance->overtime_hours);
    }

    public function test_edit_writes_audit_log_with_before_after_and_reason(): void
    {
        $attendance = $this->createRegularAttendance([
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'clock_out_report' => '<p>Laporan pulang awal cukup panjang.</p>',
        ]);

        $this->actingAs($this->admin)->patch(route('admin.attendance.update', $attendance), [
            'clock_in_date' => '2026-06-02',
            'type' => AttendanceType::Regular->value,
            'clock_in_time' => '09:00',
            'clock_out_date' => '2026-06-02',
            'clock_out_time' => '19:00',
            'status' => AttendanceStatus::LateOut->value,
            'clock_out_report' => '<p>Laporan pulang setelah koreksi cukup panjang.</p>',
            'manual_reason' => 'Face verification gagal saat pulang',
        ])->assertRedirect();

        $log = ActivityLog::query()
            ->where('subject_type', Attendance::class)
            ->where('subject_id', $attendance->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertSame('update', $log->action);
        $this->assertStringContainsString('Face verification gagal saat pulang', $log->description);
        $this->assertStringContainsString('"before"', $log->description);
        $this->assertStringContainsString('"after"', $log->description);
        $this->assertStringContainsString('18:00:00', $log->description);
        $this->assertStringContainsString('19:00:00', $log->description);
        $this->assertStringContainsString('late_out', $log->description);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRegularAttendance(array $overrides = []): Attendance
    {
        return Attendance::create(array_merge([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'clock_in_report' => '<p>Laporan masuk karyawan hari ini sudah lengkap.</p>',
        ], $overrides));
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
