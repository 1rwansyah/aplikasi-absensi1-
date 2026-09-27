<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Exports\AbsenceThresholdExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AbsenceThresholdMonitoringTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $alphaUser;

    private User $izinUser;

    private User $belowUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00', 'Asia/Jakarta'));

        $this->admin = User::factory()->admin()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->alphaUser = $this->makeEmployee('Budi Alfa', 'EMP-ALF');
        $this->izinUser = $this->makeEmployee('Siti Izin', 'EMP-IZN');
        $this->belowUser = $this->makeEmployee('Rina Below', 'EMP-BEL');
    }

    public function test_guest_is_redirected(): void
    {
        $this->get(route('admin.absence-threshold.index'))
            ->assertRedirect();
    }

    public function test_employee_cannot_access_monitoring_page(): void
    {
        $employee = User::factory()->create();
        $employee->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        $this->actingAs($employee)
            ->get(route('admin.absence-threshold.index'))
            ->assertRedirect();
    }

    public function test_page_lists_only_employees_meeting_separate_thresholds(): void
    {
        // Exactly 3 must NOT appear
        $exactThree = $this->makeEmployee('Tono Exact Three', 'EMP-EX3');
        $this->createAlphaDays($exactThree->id, ['2026-09-01', '2026-09-02', '2026-09-03']);
        $this->createApprovedPermissionDays($this->izinUser->id, ['2026-09-01', '2026-09-02', '2026-09-03']);

        // 4+ must appear
        $this->createAlphaDays($this->alphaUser->id, ['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04']);
        $this->createApprovedPermissionDays($this->izinUser->id, ['2026-09-04']); // now 4 izin total for Siti

        // Combined 3 but separate counts below threshold — must NOT appear
        $this->createAlphaDays($this->belowUser->id, ['2026-09-07']);
        $this->createApprovedPermissionDays($this->belowUser->id, ['2026-09-08', '2026-09-09']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.absence-threshold.index', ['month' => 9, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('Budi Alfa');
        $response->assertSee('Siti Izin');
        $response->assertDontSee('Tono Exact Three');
        $response->assertDontSee('Rina Below');
        $response->assertSee('01 Sep');
        $response->assertSee('04 Sep');
    }

    public function test_pending_permission_is_not_counted(): void
    {
        $this->createPendingPermissionDays($this->izinUser->id, ['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.absence-threshold.index', ['month' => 9, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('Tidak ada karyawan dengan alfa/izin ≥ 4 di bulan ini');
        $response->assertDontSee('Siti Izin');
    }

    public function test_empty_month_shows_empty_state(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.absence-threshold.index', ['month' => 8, 'year' => 2026]));

        $response->assertOk();
        $response->assertSee('Tidak ada karyawan dengan alfa/izin ≥ 4 di bulan ini');
    }

    public function test_admin_can_export_excel_rekap(): void
    {
        $this->createAlphaDays($this->alphaUser->id, ['2026-09-01', '2026-09-02', '2026-09-03', '2026-09-04']);

        Excel::fake();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.absence-threshold.export.excel', ['month' => 9, 'year' => 2026]));

        $response->assertOk();

        Excel::assertDownloaded('Rekap_Alfa_Izin_September_2026.xlsx', function (AbsenceThresholdExport $export) {
            $rows = $export->collection();

            return $rows->contains(fn (array $row) => $row['name'] === 'Budi Alfa' && $row['alpha_count'] === 4);
        });
    }

    public function test_employee_cannot_export_excel_rekap(): void
    {
        $employee = User::factory()->create();
        $employee->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        $this->actingAs($employee)
            ->get(route('admin.absence-threshold.export.excel', ['month' => 9, 'year' => 2026]))
            ->assertRedirect();
    }

    private function makeEmployee(string $name, string $code): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'role' => 'employee',
        ]);
        $user->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'name' => $name,
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        return $user;
    }

    /**
     * @param  list<string>  $dates
     */
    private function createAlphaDays(int $userId, array $dates): void
    {
        foreach ($dates as $date) {
            Attendance::create([
                'user_id' => $userId,
                'date' => $date,
                'type' => AttendanceType::Regular,
                'status' => AttendanceStatus::Alpha,
            ]);
        }
    }

    /**
     * @param  list<string>  $dates
     */
    private function createApprovedPermissionDays(int $userId, array $dates): void
    {
        foreach ($dates as $date) {
            Attendance::create([
                'user_id' => $userId,
                'date' => $date,
                'type' => AttendanceType::Permission,
                'status' => AttendanceStatus::Permission,
                'verification_status' => VerificationStatus::Done,
                'leave_note' => 'Izin keperluan keluarga',
            ]);
        }
    }

    /**
     * @param  list<string>  $dates
     */
    private function createPendingPermissionDays(int $userId, array $dates): void
    {
        foreach ($dates as $date) {
            Attendance::create([
                'user_id' => $userId,
                'date' => $date,
                'type' => AttendanceType::Permission,
                'status' => AttendanceStatus::Permission,
                'verification_status' => VerificationStatus::Pending,
                'leave_note' => 'Izin pending',
            ]);
        }
    }
}
