<?php

namespace Tests\Feature;

use App\DTOs\AttendanceReportRow;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use App\Support\AppTime;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class AdminAttendanceHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $budi;

    private User $ani;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->budi = User::factory()->create([
            'name' => 'Budi Karyawan',
            'role' => 'employee',
        ]);
        $this->budi->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $this->budi->id,
            'employee_code' => 'EMP-BUD',
            'name' => 'Budi Karyawan',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        $this->ani = User::factory()->create([
            'name' => 'Ani Karyawan',
            'role' => 'employee',
        ]);
        $this->ani->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $this->ani->id,
            'employee_code' => 'EMP-ANI',
            'name' => 'Ani Karyawan',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);
    }

    public function test_history_without_search_shows_multiple_users(): void
    {
        $month = AppTime::now()->format('Y-m');
        $dayOne = AppTime::now()->copy()->startOfMonth()->toDateString();
        $dayTwo = AppTime::now()->copy()->startOfMonth()->addDay()->toDateString();

        Attendance::create([
            'user_id' => $this->budi->id,
            'date' => $dayOne,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'clock_out_time' => '17:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        Attendance::create([
            'user_id' => $this->ani->id,
            'date' => $dayTwo,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:30:00',
            'clock_out_time' => '17:00:00',
            'status' => AttendanceStatus::Late,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.index', [
            'view' => 'history',
            'month' => $month,
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.attendance.index');
        $response->assertViewHas('view', 'history');
        $response->assertViewHas('historyRecords', function (LengthAwarePaginator $records) {
            $names = $records->getCollection()->map(fn (AttendanceReportRow $row) => $row->user->name)->all();

            return in_array('Budi Karyawan', $names, true)
                && in_array('Ani Karyawan', $names, true);
        });
    }

    public function test_history_search_limits_to_matching_employee(): void
    {
        $month = AppTime::now()->format('Y-m');
        $day = AppTime::now()->copy()->startOfMonth()->toDateString();

        Attendance::create([
            'user_id' => $this->budi->id,
            'date' => $day,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        Attendance::create([
            'user_id' => $this->ani->id,
            'date' => $day,
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:05:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.index', [
            'view' => 'history',
            'month' => $month,
            'search' => 'Ani',
        ]));

        $response->assertOk();
        $response->assertViewHas('historyRecords', function (LengthAwarePaginator $records) {
            $names = $records->getCollection()->map(fn (AttendanceReportRow $row) => $row->user->name)->all();

            return $names === ['Ani Karyawan'];
        });
    }

    public function test_history_month_and_status_filters_work(): void
    {
        $currentMonth = AppTime::now()->format('Y-m');
        $previous = AppTime::now()->copy()->subMonthNoOverflow();
        $previousMonth = $previous->format('Y-m');

        Attendance::create([
            'user_id' => $this->budi->id,
            'date' => AppTime::now()->copy()->startOfMonth()->toDateString(),
            'type' => AttendanceType::Regular,
            'clock_in_time' => '09:30:00',
            'status' => AttendanceStatus::Late,
        ]);

        Attendance::create([
            'user_id' => $this->ani->id,
            'date' => $previous->copy()->startOfMonth()->toDateString(),
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $byMonth = $this->actingAs($this->admin)->get(route('admin.attendance.index', [
            'view' => 'history',
            'month' => $previousMonth,
        ]));
        $byMonth->assertOk();
        $byMonth->assertViewHas('historyRecords', function (LengthAwarePaginator $records) {
            $names = $records->getCollection()->map(fn (AttendanceReportRow $row) => $row->user->name)->all();

            return $names === ['Ani Karyawan'];
        });

        $byStatus = $this->actingAs($this->admin)->get(route('admin.attendance.index', [
            'view' => 'history',
            'month' => $currentMonth,
            'status' => AttendanceStatus::Late->value,
        ]));
        $byStatus->assertOk();
        $byStatus->assertViewHas('historyRecords', function (LengthAwarePaginator $records) {
            $names = $records->getCollection()->map(fn (AttendanceReportRow $row) => $row->user->name)->all();

            return $names === ['Budi Karyawan'];
        });
    }

    public function test_history_date_filter_limits_to_that_day(): void
    {
        Attendance::create([
            'user_id' => $this->budi->id,
            'date' => '2026-09-12',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::Alpha,
        ]);

        Attendance::create([
            'user_id' => $this->ani->id,
            'date' => '2026-09-13',
            'type' => AttendanceType::Regular,
            'clock_in_time' => '08:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.index', [
            'view' => 'history',
            'date' => '2026-09-12',
            'status' => AttendanceStatus::Alpha->value,
        ]));

        $response->assertOk();
        $response->assertViewHas('historyRecords', function (LengthAwarePaginator $records) {
            $rows = $records->getCollection();

            return $rows->count() === 1
                && $rows->first()->user->name === 'Budi Karyawan'
                && $rows->first()->attendance?->status === AttendanceStatus::Alpha;
        });
        $response->assertViewHas('historyStatistics', fn (array $stats) => $stats['alpha'] === 1);
    }

    public function test_monitoring_mode_still_works(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $response->assertViewHas('view', 'monitoring');
        $response->assertSee('Monitoring Absensi');
        $response->assertSee('Kirim Rekap WA');
    }

    public function test_employee_cannot_access_admin_attendance_history(): void
    {
        $response = $this->actingAs($this->budi)
            ->get(route('admin.attendance.index', ['view' => 'history']));

        $response->assertRedirect();
    }
}
