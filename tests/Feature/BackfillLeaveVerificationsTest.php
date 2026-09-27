<?php

namespace Tests\Feature;

use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\LeaveVerificationBackfillService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BackfillLeaveVerificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    private LeaveVerificationBackfillService $backfillService;

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

        $this->backfillService = app(LeaveVerificationBackfillService::class);

        Carbon::setTestNow(Carbon::parse('2026-06-10 08:00:00', 'Asia/Jakarta'));
    }

    public function test_dry_run_does_not_update_records(): void
    {
        $attendance = $this->createPendingLeave('2026-06-05');

        $result = $this->backfillService->backfill(
            Carbon::parse('2026-06-10', 'Asia/Jakarta'),
            dryRun: true,
        );

        $this->assertSame(1, $result['updated']);
        $attendance->refresh();
        $this->assertTrue($attendance->verification_status->isPending());
    }

    public function test_backfill_approves_pending_before_cutoff_only(): void
    {
        $oldLeave = $this->createPendingLeave('2026-06-05');
        $recentLeave = $this->createPendingLeave('2026-06-09');

        $result = $this->backfillService->backfill(
            Carbon::parse('2026-06-09', 'Asia/Jakarta'),
        );

        $this->assertSame(1, $result['updated']);

        $oldLeave->refresh();
        $recentLeave->refresh();

        $this->assertTrue($oldLeave->verification_status->isDone());
        $this->assertNull($oldLeave->verified_by);
        $this->assertNotNull($oldLeave->verified_at);
        $this->assertTrue($recentLeave->verification_status->isPending());
    }

    public function test_backfill_skips_already_verified_records(): void
    {
        $this->createPendingLeave('2026-06-05', VerificationStatus::Done);
        $pending = $this->createPendingLeave('2026-06-06');

        $result = $this->backfillService->backfill(
            Carbon::parse('2026-06-10', 'Asia/Jakarta'),
        );

        $this->assertSame(1, $result['updated']);
        $pending->refresh();
        $this->assertTrue($pending->verification_status->isDone());
    }

    public function test_artisan_command_runs_successfully(): void
    {
        $this->createPendingLeave('2026-06-05');

        $this->artisan('attendance:backfill-leave-verification', [
            '--before' => '2026-06-09',
            '--dry-run' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Dry run selesai. 1 pengajuan pending');
    }

    private function createPendingLeave(
        string $date,
        VerificationStatus $verificationStatus = VerificationStatus::Pending,
    ): Attendance {
        return Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => $date,
            'type' => AttendanceType::Sick,
            'status' => 'sick',
            'leave_note' => 'Sakit demam',
            'verification_status' => $verificationStatus,
        ]);
    }
}
