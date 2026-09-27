<?php

namespace Tests\Unit;

use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\WorkCalendarType;
use App\Models\Attendance;
use App\Models\Setting;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use App\Support\AppTime;
use Database\Seeders\WorkScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceWorkScheduleTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkScheduleSeeder::class);
        $this->service = app(AttendanceService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_clock_out_uses_stored_work_schedule_for_security_shift(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $date = Carbon::parse('2026-06-02');
        $attendance = $this->createAttendance($date, AttendanceShift::Day, $securitySchedule->id);

        $schedule = $this->service->clockOutScheduleFor($attendance);

        $this->assertSame('07:00:00', $schedule->clockOutStart);
        $this->assertNull($schedule->clockOutLimit);
    }

    public function test_security_clock_out_opens_next_morning_without_late_out_limit(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $date = Carbon::parse('2026-06-02');
        $attendance = $this->createAttendance($date, AttendanceShift::Day, $securitySchedule->id);
        $schedule = $this->service->clockOutScheduleFor($attendance);

        $this->assertTrue($schedule->isEarlyClockOut(Carbon::parse('2026-06-02 20:00:00'), $date));
        $this->assertTrue($schedule->isEarlyClockOut(Carbon::parse('2026-06-03 06:59:00'), $date));
        $this->assertFalse($schedule->isEarlyClockOut(Carbon::parse('2026-06-03 07:00:00'), $date));
        $this->assertFalse($schedule->isLateClockOut(Carbon::parse('2026-06-03 10:00:00')));
        $this->assertSame(
            AttendanceStatus::OnTime,
            $schedule->resolveClockOutStatus(Carbon::parse('2026-06-03 10:00:00'), AttendanceStatus::OnTime),
        );
    }

    public function test_legacy_attendance_without_work_schedule_still_uses_setting(): void
    {
        Setting::query()->delete();
        Setting::current()->update([
            'clock_out_start' => '17:00:00',
            'clock_out_limit' => '19:00:00',
        ]);

        $date = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::Holiday]);
        $attendance = $this->createAttendance($date, AttendanceShift::Day, null);

        $schedule = $this->service->clockOutScheduleFor($attendance);

        $this->assertSame('17:00:00', $schedule->clockOutStart);
    }

    public function test_security_shift_skips_work_calendar_half_day_floor(): void
    {
        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $date = Carbon::parse('2026-06-06');

        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::HalfDay]);

        $attendance = $this->createAttendance($date, AttendanceShift::Day, $securitySchedule->id);

        Carbon::setTestNow(Carbon::parse($date->toDateString().' 14:00:00', AppTime::timezone()));

        $schedule = $this->service->clockOutScheduleFor($attendance);

        $this->assertSame('07:00:00', $schedule->clockOutStart);
        $this->assertNull($schedule->clockOutLimit);
        $this->assertTrue($schedule->isEarlyClockOut(AppTime::now(), $date));
    }

    private function createAttendance(
        Carbon $date,
        AttendanceShift $shift,
        ?int $workScheduleId,
        string $clockInTime = '07:00:00',
    ): Attendance {
        $user = User::factory()->create();

        return Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'type' => AttendanceType::Regular,
            'shift' => $shift,
            'work_schedule_id' => $workScheduleId,
            'clock_in_time' => $clockInTime,
            'status' => AttendanceStatus::OnTime,
        ]);
    }
}
