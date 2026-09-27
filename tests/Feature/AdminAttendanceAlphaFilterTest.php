<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Services\AttendanceReportService;
use App\Support\AppTime;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceAlphaFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $markedAlpha;

    private User $noRecord;

    private User $present;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-09-18 10:00:00', 'Asia/Jakarta'));

        $this->admin = User::factory()->admin()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->markedAlpha = $this->makeEmployee('Budi Marked Alpha');
        $this->noRecord = $this->makeEmployee('Ani Tanpa Record');
        $this->present = $this->makeEmployee('Citra Hadir');

        Attendance::create([
            'user_id' => $this->markedAlpha->id,
            'date' => AppTime::today()->toDateString(),
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::Alpha,
        ]);

        Attendance::create([
            'user_id' => $this->present->id,
            'date' => AppTime::today()->toDateString(),
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);
    }

    public function test_monitoring_alfa_filter_includes_marked_alpha_rows(): void
    {
        $rows = app(AttendanceReportService::class)
            ->dailyReport(AppTime::today(), 'alfa')
            ->getCollection();

        $names = $rows->map(fn ($row) => $row->user->name)->all();

        $this->assertContains('Budi Marked Alpha', $names);
        $this->assertContains('Ani Tanpa Record', $names);
        $this->assertNotContains('Citra Hadir', $names);
    }

    public function test_history_shows_marked_alpha_for_searched_user(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.index', [
            'view' => 'history',
            'month' => '2026-09',
            'search' => 'Budi Marked',
            'status' => 'alpha',
        ]));

        $response->assertOk();
        $response->assertSee('Budi Marked Alpha');
        $response->assertSee('Alfa');
    }

    public function test_rejected_permission_still_counts_as_alfa_in_monitoring_filter(): void
    {
        $rejected = $this->makeEmployee('Dedi Izin Ditolak');

        Attendance::create([
            'user_id' => $rejected->id,
            'date' => AppTime::today()->toDateString(),
            'type' => AttendanceType::Permission,
            'status' => AttendanceStatus::Permission,
            'verification_status' => VerificationStatus::NoDone,
            'leave_note' => 'Ditolak',
        ]);

        $rows = app(AttendanceReportService::class)
            ->dailyReport(AppTime::today(), 'alfa')
            ->getCollection();

        $names = $rows->map(fn ($row) => $row->user->name)->all();

        $this->assertContains('Dedi Izin Ditolak', $names);
    }

    public function test_monitoring_excludes_resigned_and_orphan_users_from_alfa(): void
    {
        $resigned = $this->makeEmployee('Eka Resign', 'resigned');

        $orphan = User::factory()->create([
            'name' => 'Fajar Orphan',
            'role' => 'employee',
        ]);
        $orphan->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        $rows = app(AttendanceReportService::class)
            ->dailyReport(AppTime::today(), 'alfa')
            ->getCollection();

        $names = $rows->map(fn ($row) => $row->user->name)->all();

        $this->assertNotContains('Eka Resign', $names);
        $this->assertNotContains('Fajar Orphan', $names);
        $this->assertContains('Ani Tanpa Record', $names);
    }

    public function test_daily_summary_excludes_inactive_employees(): void
    {
        $this->makeEmployee('Gita Inactive', 'inactive');

        $summary = app(AttendanceReportService::class)->summaryFor(AppTime::today());

        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['alfa']);
        $this->assertSame(1, $summary['hadir']);
    }

    private function makeEmployee(string $name, string $employmentStatus = 'active'): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'employee',
        ]);
        $user->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-'.substr(md5($name), 0, 5),
            'name' => $name,
            'employment_status' => $employmentStatus,
            'basic_salary' => 5_000_000,
        ]);

        return $user;
    }
}
