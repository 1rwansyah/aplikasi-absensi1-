<?php

namespace Tests\Feature;

use App\Enums\AttendanceReportStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\LeaveVerificationExpiryService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpirePendingLeaveVerificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    private LeaveVerificationExpiryService $expiryService;

    private AttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->employeeUser = User::factory()->create(['name' => 'Budi Karyawan']);
        $this->employeeUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'EMP-001',
            'name' => 'Budi Karyawan',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        $this->expiryService = app(LeaveVerificationExpiryService::class);
        $this->attendanceService = app(AttendanceService::class);
    }

    public function test_pending_leave_from_previous_day_is_auto_rejected(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        Carbon::setTestNow(Carbon::parse('2026-06-07 00:10:00', 'Asia/Jakarta'));

        $expired = $this->expiryService->expirePendingBefore();

        $this->assertSame(1, $expired);

        $attendance = Attendance::first();
        $this->assertSame(VerificationStatus::NoDone, $attendance->verification_status);
        $this->assertNull($attendance->verified_by);
        $this->assertNotNull($attendance->verified_at);
        $this->assertSame(AttendanceReportStatus::Sick, AttendanceReportStatus::fromAttendance($attendance));
    }

    public function test_pending_leave_from_today_is_not_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        $expired = $this->expiryService->expirePendingBefore();

        $this->assertSame(0, $expired);
        $this->assertSame(VerificationStatus::Pending, Attendance::first()->verification_status);
    }

    public function test_artisan_command_expires_pending_leaves(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        Carbon::setTestNow(Carbon::parse('2026-06-07 00:10:00', 'Asia/Jakarta'));

        $this->artisan('attendance:expire-pending-leaves')
            ->expectsOutput('Pengajuan otomatis ditolak: 1')
            ->assertSuccessful();

        $this->assertSame(VerificationStatus::NoDone, Attendance::first()->verification_status);
    }

    public function test_expired_sick_counts_as_sick_in_monthly_statistics(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        Carbon::setTestNow(Carbon::parse('2026-06-07 00:10:00', 'Asia/Jakarta'));
        $this->expiryService->expirePendingBefore();

        $statistics = $this->attendanceService->monthlyStatisticsFor($this->employeeUser, '2026-06');

        $this->assertSame(1, $statistics['sakit']);
        $this->assertSame(0, $statistics['alpha']);
    }

    public function test_employee_can_resubmit_after_auto_expiry(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-06 08:00:00', 'Asia/Jakarta'));
        $this->submitSickLeave();

        Carbon::setTestNow(Carbon::parse('2026-06-07 08:00:00', 'Asia/Jakarta'));
        $this->expiryService->expirePendingBefore();

        $response = $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'permission',
            'leave_note' => 'Urusan keluarga mendesak hari ini',
            'doctor_note' => UploadedFile::fake()->create('bukti-izin.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('attendances', 2);
    }

    private function submitSickLeave(): void
    {
        $this->actingAs($this->employeeUser)->post(route('attendance.leave'), [
            'type' => 'sick',
            'leave_note' => 'Demam tinggi sejak pagi hari ini',
            'doctor_note' => UploadedFile::fake()->image('surat-dokter.jpg'),
        ]);
    }
}
