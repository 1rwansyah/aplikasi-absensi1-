<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\WorkCalendarType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use App\Services\AttendanceVerificationPhotoService;
use App\Services\FaceVerificationService;
use App\Services\MarkAbsentAlphaService;
use App\Support\AppTime;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MarkAbsentAlphaTest extends TestCase
{
    use RefreshDatabase;

    private MarkAbsentAlphaService $service;

    private WorkSchedule $regular;

    private WorkSchedule $security;

    private WorkSchedule $engineering;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(WorkScheduleSeeder::class);
        $this->service = app(MarkAbsentAlphaService::class);
        $this->regular = WorkSchedule::where('code', 'regular')->firstOrFail();
        $this->security = WorkSchedule::where('code', 'security')->firstOrFail();
        $this->engineering = WorkSchedule::where('code', 'engineering')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_run_before_cutoff_is_noop(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-02 22:59:00', AppTime::timezone()));
        $this->seedCalendarDay('2026-06-02', WorkCalendarType::FullDay);
        $this->createRegularEmployee('EMP-REG');

        $this->artisan('attendance:mark-alpha')
            ->expectsOutputToContain('No-op')
            ->assertSuccessful();

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_daily_run_after_cutoff_marks_regular_employee(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-02 23:05:00', AppTime::timezone()));
        $this->seedCalendarDay('2026-06-02', WorkCalendarType::FullDay);
        $employee = $this->createRegularEmployee('EMP-REG');

        $this->artisan('attendance:mark-alpha')->assertSuccessful();

        $this->assertTrue(
            Attendance::query()
                ->where('user_id', $employee->user_id)
                ->whereDate('date', '2026-06-02')
                ->where('status', AttendanceStatus::Alpha)
                ->whereNull('clock_in_time')
                ->whereNull('clock_out_time')
                ->exists()
        );
    }

    public function test_skips_security_engineering_and_existing_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-03 23:30:00', AppTime::timezone()));
        $this->seedCalendarDay('2026-06-03', WorkCalendarType::FullDay);

        $regular = $this->createRegularEmployee('EMP-REG');
        $security = $this->createEmployee('EMP-SEC', 'Security', $this->security);
        $engineering = $this->createEmployee('EMP-ENG', 'Engineering', $this->engineering);

        Attendance::create([
            'user_id' => $regular->user_id,
            'date' => '2026-06-03',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::OnTime,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
        ]);

        $result = $this->service->markForDate(Carbon::parse('2026-06-03', AppTime::timezone()));

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['skipped_existing']);
        $this->assertGreaterThanOrEqual(2, $result['skipped_ineligible']);

        $this->assertDatabaseMissing('attendances', ['user_id' => $security->user_id]);
        $this->assertDatabaseMissing('attendances', ['user_id' => $engineering->user_id]);
        $this->assertTrue(
            Attendance::query()
                ->where('user_id', $regular->user_id)
                ->whereDate('date', '2026-06-03')
                ->where('status', AttendanceStatus::OnTime)
                ->exists()
        );
    }

    public function test_skips_holiday_and_marks_half_day(): void
    {
        $this->seedCalendarDay('2026-06-07', WorkCalendarType::Holiday); // Sunday-ish holiday
        $this->seedCalendarDay('2026-06-06', WorkCalendarType::HalfDay);
        $employee = $this->createRegularEmployee('EMP-REG');

        $holidayResult = $this->service->markForDate(Carbon::parse('2026-06-07', AppTime::timezone()));
        $this->assertSame(0, $holidayResult['dates']);
        $this->assertFalse(
            Attendance::query()->where('user_id', $employee->user_id)->whereDate('date', '2026-06-07')->exists()
        );

        $halfDayResult = $this->service->markForDate(Carbon::parse('2026-06-06', AppTime::timezone()));
        $this->assertSame(1, $halfDayResult['created']);
        $this->assertTrue(
            Attendance::query()
                ->where('user_id', $employee->user_id)
                ->whereDate('date', '2026-06-06')
                ->where('status', AttendanceStatus::Alpha)
                ->exists()
        );
    }

    public function test_backfill_ignores_cutoff(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-10 10:00:00', AppTime::timezone()));
        $this->seedCalendarDay('2026-06-02', WorkCalendarType::FullDay);
        $this->seedCalendarDay('2026-06-03', WorkCalendarType::FullDay);
        $employee = $this->createRegularEmployee('EMP-REG');

        $this->artisan('attendance:mark-alpha', [
            '--from' => '2026-06-02',
            '--to' => '2026-06-03',
        ])->assertSuccessful();

        $this->assertTrue(
            Attendance::query()
                ->where('user_id', $employee->user_id)
                ->whereDate('date', '2026-06-02')
                ->where('status', AttendanceStatus::Alpha)
                ->exists()
        );
        $this->assertTrue(
            Attendance::query()
                ->where('user_id', $employee->user_id)
                ->whereDate('date', '2026-06-03')
                ->where('status', AttendanceStatus::Alpha)
                ->exists()
        );
    }

    public function test_monthly_statistics_count_alpha_rows(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-15 12:00:00', AppTime::timezone()));
        $employee = $this->createRegularEmployee('EMP-REG');

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::Alpha,
        ]);

        $stats = app(AttendanceService::class)->monthlyStatisticsFor($employee->user, '2026-06');

        $this->assertSame(1, $stats['alpha']);
    }

    public function test_clock_in_rejected_after_23_for_regular_and_when_alpha_exists(): void
    {
        \App\Models\Setting::query()->delete();
        \App\Models\Setting::current()->update([
            'office_latitude' => null,
            'office_longitude' => null,
            'attendance_radius_meters' => 0,
        ]);

        $this->mock(FaceVerificationService::class, function ($mock): void {
            $mock->shouldReceive('validateDescriptorPayload')->andReturn(null);
            $mock->shouldReceive('verifyForUser')->andReturn(['success' => true, 'distance' => 0.1]);
        });
        $this->mock(AttendanceVerificationPhotoService::class, function ($mock): void {
            $mock->shouldReceive('storeBase64WithOverlay')->andReturn('attendance-verification/test.jpg');
        });

        Carbon::setTestNow(Carbon::parse('2026-06-02 23:10:00', AppTime::timezone()));
        $this->seedCalendarDay('2026-06-02', WorkCalendarType::FullDay);
        $employee = $this->createRegularEmployee('EMP-REG');
        $attendanceService = app(AttendanceService::class);

        $result = $attendanceService->clockIn(
            $employee->user,
            'Laporan',
            [0.1, 0.2, 0.3],
            1,
            $this->tinyJpegBase64(),
            -6.2,
            106.8,
        );

        $this->assertFalse($result->success);
        $this->assertStringContainsString('23:00', $result->message);

        Attendance::create([
            'user_id' => $employee->user_id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'status' => AttendanceStatus::Alpha,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-02 12:00:00', AppTime::timezone()));

        $blocked = $attendanceService->clockIn(
            $employee->user,
            'Laporan',
            [0.1, 0.2, 0.3],
            1,
            $this->tinyJpegBase64(),
            -6.2,
            106.8,
        );

        $this->assertFalse($blocked->success);
        $this->assertStringContainsString('alfa', strtolower($blocked->message));
    }

    private function seedCalendarDay(string $date, WorkCalendarType $type): void
    {
        WorkCalendar::updateOrCreate(
            ['date' => $date],
            ['type' => $type, 'name' => $type->label()],
        );
    }

    private function createRegularEmployee(string $code): Employee
    {
        return $this->createEmployee($code, 'Staff', $this->regular);
    }

    private function createEmployee(string $code, string $staff, WorkSchedule $schedule): Employee
    {
        $user = User::factory()->create(['name' => $code]);

        return Employee::create([
            'user_id' => $user->id,
            'employee_code' => $code,
            'name' => $code,
            'staff' => $staff,
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
            'default_work_schedule_id' => $schedule->id,
        ]);
    }

    private function tinyJpegBase64(): string
    {
        $jpeg = base64_decode(
            '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAn/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAGfAP/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAQUCf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQMBAT8Bf//EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQIBAT8Bf//Z'
        );

        return 'data:image/jpeg;base64,'.base64_encode($jpeg ?: 'x');
    }
}
