<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Support\AppTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    private string $token = 'test-attendance-api-token';

    private User $employeeUser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.attendance_api.token' => $this->token]);

        $this->employeeUser = User::factory()->create([
            'name' => 'Budi Karyawan',
            'email' => 'budi@mail.com',
        ]);

        Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_code' => 'ID-001',
            'name' => 'Budi Karyawan',
            'email' => 'budi@mail.com',
            'nik' => '3201010101010001',
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);
    }

    public function test_rejects_request_without_token(): void
    {
        $this->getJson('/api/attendance/today')
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized.',
            ]);
    }

    public function test_rejects_request_with_invalid_token(): void
    {
        $this->withToken('wrong-token')
            ->getJson('/api/attendance/today')
            ->assertUnauthorized();
    }

    public function test_lists_today_attendance_with_valid_token(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', AppTime::timezone()));

        Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-09-11',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'clock_in_time' => '09:05:00',
            'clock_out_time' => '18:02:00',
        ]);

        $this->withToken($this->token)
            ->getJson('/api/attendance/today')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'sadar')
            ->assertJsonPath('date', '2026-09-11')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.employee_code', 'ID-001')
            ->assertJsonPath('data.0.present', true)
            ->assertJsonPath('data.0.status', 'on_time')
            ->assertJsonPath('data.0.clock_in', '09:05:00')
            ->assertJsonPath('data.0.clock_out', '18:02:00');
    }

    public function test_lists_attendance_for_custom_date(): void
    {
        Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-09-08',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::Late,
            'clock_in_time' => '09:20:00',
        ]);

        $this->withToken($this->token)
            ->getJson('/api/attendance?date=2026-09-08')
            ->assertOk()
            ->assertJsonPath('date', '2026-09-08')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.status', 'late');
    }

    public function test_checks_employee_by_employee_code(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-11 10:00:00', AppTime::timezone()));

        Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-09-11',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'clock_in_time' => '09:01:00',
        ]);

        $this->withToken($this->token)
            ->getJson('/api/attendance/employee/ID-001')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('source', 'sadar')
            ->assertJsonPath('data.employee_code', 'ID-001')
            ->assertJsonPath('data.present', true)
            ->assertJsonPath('data.status', 'on_time');
    }

    public function test_employee_without_attendance_returns_not_recorded(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/attendance/employee/ID-001?date=2026-09-10')
            ->assertOk()
            ->assertJsonPath('data.present', false)
            ->assertJsonPath('data.status', 'not_recorded')
            ->assertJsonPath('data.clock_in', null)
            ->assertJsonPath('data.clock_out', null);
    }

    public function test_employee_not_found_returns_404(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/attendance/employee/UNKNOWN-999')
            ->assertNotFound();
    }

    public function test_alpha_status_is_not_present(): void
    {
        Attendance::create([
            'user_id' => $this->employeeUser->id,
            'date' => '2026-09-09',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::Alpha,
        ]);

        $this->withToken($this->token)
            ->getJson('/api/attendance/employee/ID-001?date=2026-09-09')
            ->assertOk()
            ->assertJsonPath('data.present', false)
            ->assertJsonPath('data.status', 'alpha');
    }

    public function test_invalid_date_returns_validation_error(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/attendance?date=11-09-2026')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_cors_preflight_allows_super_origin(): void
    {
        config([
            'cors.paths' => ['api/*'],
            'cors.allowed_origins' => ['https://example.com'],
            'cors.allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],
            'cors.allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'Origin', 'X-Requested-With'],
            'cors.supports_credentials' => false,
        ]);

        $this->options('/api/attendance/today', [
            'Origin' => 'https://example.com',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'authorization',
        ])->assertSuccessful()
            ->assertHeader('Access-Control-Allow-Origin', 'https://example.com');
    }

    public function test_cors_preflight_rejects_unknown_origin(): void
    {
        config([
            'cors.paths' => ['api/*'],
            'cors.allowed_origins' => ['https://example.com'],
            'cors.allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],
            'cors.allowed_headers' => ['Authorization', 'Content-Type', 'Accept', 'Origin', 'X-Requested-With'],
            'cors.supports_credentials' => false,
        ]);

        $response = $this->options('/api/attendance/today', [
            'Origin' => 'https://evil.example.com',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'authorization',
        ]);

        $this->assertNotSame(
            'https://evil.example.com',
            $response->headers->get('Access-Control-Allow-Origin'),
        );
    }
}
