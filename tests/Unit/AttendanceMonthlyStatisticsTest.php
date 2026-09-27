<?php

namespace Tests\Unit;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use App\Support\AppTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceMonthlyStatisticsTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AttendanceService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_late_out_counts_as_hadir_not_telat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-22 12:00:00', AppTime::timezone()));

        $user = User::factory()->create();
        $month = '2026-06';

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-22',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:30:00',
            'status' => AttendanceStatus::LateOut,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-21',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '09:30:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::Late,
        ]);

        $stats = $this->service->monthlyStatisticsFor($user, $month);

        $this->assertSame(1, $stats['hadir']);
        $this->assertSame(1, $stats['telat']);
    }

    public function test_null_or_empty_month_counts_all_months_including_alpha(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 12:00:00', AppTime::timezone()));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'status' => AttendanceStatus::Alpha,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-07-01',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $allMonths = $this->service->monthlyStatisticsFor($user, null);
        $this->assertSame(1, $allMonths['alpha']);
        $this->assertSame(1, $allMonths['hadir']);

        $emptyString = $this->service->monthlyStatisticsFor($user, '');
        $this->assertSame(1, $emptyString['alpha']);
        $this->assertSame(1, $emptyString['hadir']);
    }

    public function test_month_filter_limits_statistics_to_that_month(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-03 12:00:00', AppTime::timezone()));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'status' => AttendanceStatus::Alpha,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-07-01',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $june = $this->service->monthlyStatisticsFor($user, '2026-06');
        $this->assertSame(1, $june['alpha']);
        $this->assertSame(0, $june['hadir']);

        $july = $this->service->monthlyStatisticsFor($user, '2026-07');
        $this->assertSame(0, $july['alpha']);
        $this->assertSame(1, $july['hadir']);
    }

    public function test_type_and_status_filters_apply_to_statistics(): void
    {
        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-02',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'status' => AttendanceStatus::Alpha,
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-06-03',
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '09:00:00',
            'clock_out_time' => '18:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $alphaOnly = $this->service->monthlyStatisticsFor(
            $user,
            '2026-06',
            AttendanceType::Regular,
            AttendanceStatus::Alpha,
        );

        $this->assertSame(1, $alphaOnly['alpha']);
        $this->assertSame(0, $alphaOnly['hadir']);
    }
}