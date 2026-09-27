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
use App\Services\AttendanceService;
use App\Services\WorkCalendarService;
use App\Support\AppTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceClockOutScheduleTest extends TestCase
{
    use RefreshDatabase;

    private AttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AttendanceService::class);
        $this->seedOfficeShiftSettings();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_day_shift_uses_calendar_floor_night_shift_does_not(): void
    {
        $date = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::FullDay]);

        $dayAttendance = $this->createAttendance($date, AttendanceShift::Day);
        $nightAttendance = $this->createAttendance($date, AttendanceShift::Night, '18:00:00');

        $daySchedule = $this->service->clockOutScheduleFor($dayAttendance);
        $nightSchedule = $this->service->clockOutScheduleFor($nightAttendance);

        $this->assertSame('18:00:00', $daySchedule->clockOutStart);
        $this->assertSame('22:00:00', $nightSchedule->clockOutStart);
    }

    #[DataProvider('fullDayDayShiftClockOutProvider')]
    public function test_full_day_day_shift_clock_out_by_time(string $time, bool $canClockOut): void
    {
        $date = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::FullDay]);
        $attendance = $this->createAttendance($date, AttendanceShift::Day);

        $this->assertClockOutEligibilityAt($attendance, $date, $time, $canClockOut);
        $this->assertSame('18:00', $this->service->clockOutScheduleFor($attendance)->formattedClockOutStart());
    }

    public static function fullDayDayShiftClockOutProvider(): array
    {
        return [
            '17:59 blocked' => ['17:59:00', false],
            '18:00 allowed' => ['18:00:00', true],
        ];
    }

    #[DataProvider('halfDayDayShiftClockOutProvider')]
    public function test_half_day_day_shift_clock_out_by_time(string $time, bool $canClockOut): void
    {
        $date = Carbon::parse('2026-06-06');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::HalfDay]);
        $attendance = $this->createAttendance($date, AttendanceShift::Day);

        $this->assertClockOutEligibilityAt($attendance, $date, $time, $canClockOut);
        $this->assertSame('14:00', $this->service->clockOutScheduleFor($attendance)->formattedClockOutStart());
    }

    public static function halfDayDayShiftClockOutProvider(): array
    {
        return [
            '13:59 blocked' => ['13:59:00', false],
            '14:00 allowed' => ['14:00:00', true],
        ];
    }

    public function test_holiday_day_shift_uses_settings_without_calendar_override(): void
    {
        $date = Carbon::parse('2026-06-07');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::Holiday, 'name' => 'Minggu']);
        $attendance = $this->createAttendance($date, AttendanceShift::Day);

        $schedule = $this->service->clockOutScheduleFor($attendance);

        $this->assertSame('17:00:00', $schedule->clockOutStart);
        $this->assertNull(app(WorkCalendarService::class)->dayShiftMinimumClockOutStart($date));

        $this->assertClockOutEligibilityAt($attendance, $date, '16:59:00', false);
        $this->assertClockOutEligibilityAt($attendance, $date, '17:00:00', true);
        $this->assertClockOutEligibilityAt($attendance, $date, '17:30:00', true);
        $this->assertClockOutEligibilityAt($attendance, $date, '18:00:00', true);
    }

    #[DataProvider('nightShiftClockOutProvider')]
    public function test_night_shift_ignores_calendar_full_and_half_day_floors(
        string $time,
        bool $canClockOut,
        string $expectedOpensAt,
    ): void {
        $date = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::FullDay]);
        $attendance = $this->createAttendance($date, AttendanceShift::Night, '18:00:00');

        $schedule = $this->service->clockOutScheduleFor($attendance);

        $this->assertSame('22:00:00', $schedule->clockOutStart);
        $this->assertNotSame('18:00:00', $schedule->clockOutStart);
        $this->assertNotSame('14:00:00', $schedule->clockOutStart);
        $this->assertSame($expectedOpensAt, $schedule->formattedClockOutStart());

        $this->assertClockOutEligibilityAt($attendance, $date, $time, $canClockOut);
    }

    public static function nightShiftClockOutProvider(): array
    {
        return [
            '18:00 still early for night window' => ['18:00:00', false, '22:00'],
            '17:59 early' => ['17:59:00', false, '22:00'],
            '22:00 opens night clock out' => ['22:00:00', true, '22:00'],
        ];
    }

    public function test_half_day_calendar_does_not_force_fourteen_on_night_shift(): void
    {
        $date = Carbon::parse('2026-06-06');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::HalfDay]);
        $attendance = $this->createAttendance($date, AttendanceShift::Night, '18:00:00');

        $schedule = $this->service->clockOutScheduleFor($attendance);

        $this->assertSame('22:00:00', $schedule->clockOutStart);
        $this->assertClockOutEligibilityAt($attendance, $date, '14:00:00', false);
        $this->assertClockOutEligibilityAt($attendance, $date, '22:00:00', true);
    }

    private function seedOfficeShiftSettings(): void
    {
        Setting::query()->delete();
        Setting::current()->update([
            'clock_out_start' => '17:00:00',
            'clock_out_limit' => '19:00:00',
            'night_clock_out_start' => '22:00:00',
            'night_clock_out_limit' => '07:00:00',
        ]);
    }

    private function createAttendance(
        Carbon $date,
        AttendanceShift $shift,
        string $clockInTime = '09:00:00',
    ): Attendance {
        $user = User::factory()->create();

        return Attendance::create([
            'user_id' => $user->id,
            'date' => $date,
            'type' => AttendanceType::Regular,
            'shift' => $shift,
            'clock_in_time' => $clockInTime,
            'status' => AttendanceStatus::OnTime,
        ]);
    }

    private function assertClockOutEligibilityAt(
        Attendance $attendance,
        Carbon $date,
        string $time,
        bool $canClockOut,
    ): void {
        Carbon::setTestNow(Carbon::parse($date->toDateString().' '.$time, AppTime::timezone()));

        $schedule = $this->service->clockOutScheduleFor($attendance);
        $context = $this->service->clockOutContext($attendance);
        $now = AppTime::now();

        $this->assertSame(
            $canClockOut,
            $context['canClockOutNow'],
            "clockOutContext at {$time}",
        );
        $this->assertSame(
            $canClockOut,
            $schedule->canClockOutNow($now),
            "schedule->canClockOutNow at {$time}",
        );
        $this->assertSame(
            ! $canClockOut,
            $schedule->isEarlyClockOut($now),
            "schedule->isEarlyClockOut at {$time}",
        );
    }
}
