<?php

namespace Tests\Unit;

use App\DTOs\ShiftSchedule;
use App\Services\WorkCalendarService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShiftScheduleClockOutTest extends TestCase
{
    private function officeDaySchedule(): ShiftSchedule
    {
        return new ShiftSchedule(
            officeStart: '09:00:00',
            lateLimit: '09:30:00',
            clockOutStart: '17:00:00',
            clockOutLimit: '19:00:00',
        );
    }

    private function effectiveFullDaySchedule(): ShiftSchedule
    {
        return $this->officeDaySchedule()->withMinimumClockOutStart(
            WorkCalendarService::DAY_SHIFT_FULL_DAY_CLOCK_OUT,
        );
    }

    private function effectiveHalfDaySchedule(): ShiftSchedule
    {
        return $this->officeDaySchedule()->withCalendarClockOutStart(
            WorkCalendarService::DAY_SHIFT_HALF_DAY_CLOCK_OUT,
        );
    }

    public function test_raises_day_clock_out_start_to_full_day_minimum(): void
    {
        $effective = $this->effectiveFullDaySchedule();

        $this->assertSame('18:00:00', $effective->clockOutStart);
        $this->assertSame('19:00:00', $effective->clockOutLimit);
    }

    #[DataProvider('fullDayClockOutBoundaryProvider')]
    public function test_full_day_clock_out_boundaries(string $time, bool $canClockOut): void
    {
        $schedule = $this->effectiveFullDaySchedule();
        $at = Carbon::parse("2026-06-02 {$time}");

        $this->assertSame($canClockOut, $schedule->canClockOutNow($at));
        $this->assertSame(! $canClockOut, $schedule->isEarlyClockOut($at));
    }

    public static function fullDayClockOutBoundaryProvider(): array
    {
        return [
            '17:59 blocked' => ['17:59:00', false],
            '18:00 allowed' => ['18:00:00', true],
            '18:01 allowed' => ['18:01:00', true],
        ];
    }

    #[DataProvider('halfDayClockOutBoundaryProvider')]
    public function test_half_day_clock_out_boundaries(string $time, bool $canClockOut): void
    {
        $schedule = $this->effectiveHalfDaySchedule();
        $at = Carbon::parse("2026-06-06 {$time}");

        $this->assertSame($canClockOut, $schedule->canClockOutNow($at));
        $this->assertSame(! $canClockOut, $schedule->isEarlyClockOut($at));
    }

    public static function halfDayClockOutBoundaryProvider(): array
    {
        return [
            '13:59 blocked' => ['13:59:00', false],
            '14:00 allowed' => ['14:00:00', true],
            '14:01 allowed' => ['14:01:00', true],
        ];
    }

    public function test_half_day_calendar_forces_fourteen_even_when_settings_start_later(): void
    {
        $effective = $this->officeDaySchedule()->withCalendarClockOutStart(
            WorkCalendarService::DAY_SHIFT_HALF_DAY_CLOCK_OUT,
        );

        $this->assertSame('14:00:00', $effective->clockOutStart);
        $this->assertTrue($effective->canClockOutNow(Carbon::parse('2026-06-06 14:00:00')));
        $this->assertFalse($effective->canClockOutNow(Carbon::parse('2026-06-06 13:59:00')));
        $this->assertTrue($effective->isEarlyClockOut(Carbon::parse('2026-06-06 13:59:00')));
    }

    #[DataProvider('fullDayOvertimeFromOpenProvider')]
    public function test_full_day_overtime_from_clock_out_open(string $time, float $expected): void
    {
        $date = Carbon::parse('2026-06-02');
        $schedule = $this->effectiveFullDaySchedule();

        $this->assertSame(
            $expected,
            $schedule->overtimeHoursFromClockOutOpen($date, Carbon::parse("2026-06-02 {$time}")),
        );
    }

    public static function fullDayOvertimeFromOpenProvider(): array
    {
        return [
            '18:30 zero grace' => ['18:30:00', 0.0],
            '18:59 zero grace' => ['18:59:00', 0.0],
            '19:00 end of grace' => ['19:00:00', 0.0],
            '19:30 half hour ot' => ['19:30:00', 0.5],
            '20:00 one hour ot' => ['20:00:00', 1.0],
            '20:30 one and half ot' => ['20:30:00', 1.5],
            '21:00 two hours ot' => ['21:00:00', 2.0],
        ];
    }

    #[DataProvider('halfDayOvertimeFromOpenProvider')]
    public function test_half_day_overtime_from_clock_out_open(string $time, float $expected): void
    {
        $date = Carbon::parse('2026-06-06');
        $schedule = $this->effectiveHalfDaySchedule();

        $this->assertSame(
            $expected,
            $schedule->overtimeHoursFromClockOutOpen($date, Carbon::parse("2026-06-06 {$time}")),
        );
    }

    public static function halfDayOvertimeFromOpenProvider(): array
    {
        return [
            '14:30 zero grace' => ['14:30:00', 0.0],
            '14:59 zero grace' => ['14:59:00', 0.0],
            '15:00 end of grace' => ['15:00:00', 0.0],
            '15:30 half hour ot' => ['15:30:00', 0.5],
            '16:00 one hour ot' => ['16:00:00', 1.0],
            '16:30 one and half ot' => ['16:30:00', 1.5],
        ];
    }

    private function securitySchedule(): ShiftSchedule
    {
        return new ShiftSchedule(
            officeStart: '07:00:00',
            lateLimit: '07:15:00',
            clockOutStart: '07:00:00',
            clockOutLimit: null,
        );
    }

    #[DataProvider('securityOvernightOvertimeProvider')]
    public function test_security_overnight_overtime_from_effective_next_day_clock_out(string $time, float $expected): void
    {
        $dutyDate = Carbon::parse('2026-06-02');
        $schedule = $this->securitySchedule();
        $nextDay = Carbon::parse("2026-06-03 {$time}");

        $this->assertSame(
            $expected,
            $schedule->overtimeHoursFromClockOutOpen($dutyDate, $nextDay),
        );
    }

    public static function securityOvernightOvertimeProvider(): array
    {
        return [
            '07:30 zero grace' => ['07:30:00', 0.0],
            '08:00 end of grace' => ['08:00:00', 0.0],
            '08:30 half hour ot' => ['08:30:00', 0.5],
            '09:00 one hour ot' => ['09:00:00', 1.0],
            '09:30 one and half ot' => ['09:30:00', 1.5],
        ];
    }

    public function test_effective_normal_clock_out_for_security_is_next_morning(): void
    {
        $dutyDate = Carbon::parse('2026-06-02');
        $schedule = $this->securitySchedule();

        $effective = $schedule->effectiveNormalClockOutCarbon($dutyDate);

        $this->assertSame('2026-06-03', $effective->toDateString());
        $this->assertSame('07:00:00', $effective->format('H:i:s'));
    }

    public function test_effective_normal_clock_out_for_overnight_shift_is_next_day_limit(): void
    {
        $dutyDate = Carbon::parse('2026-06-02');
        $schedule = new ShiftSchedule(
            officeStart: '17:00:00',
            lateLimit: '22:00:00',
            clockOutStart: '22:00:00',
            clockOutLimit: '07:00:00',
        );

        $effective = $schedule->effectiveNormalClockOutCarbon($dutyDate);

        $this->assertSame('2026-06-03', $effective->toDateString());
        $this->assertSame('07:00:00', $effective->format('H:i:s'));
    }

    public function test_does_not_modify_overnight_schedule(): void
    {
        $schedule = new ShiftSchedule(
            officeStart: '17:00:00',
            lateLimit: '22:00:00',
            clockOutStart: '22:00:00',
            clockOutLimit: '07:00:00',
        );

        $effective = $schedule->withMinimumClockOutStart('18:00:00');

        $this->assertSame($schedule, $effective);
        $this->assertTrue($effective->isEarlyClockOut(Carbon::parse('2026-06-02 18:00:00')));
        $this->assertTrue($effective->canClockOutNow(Carbon::parse('2026-06-02 22:00:00')));
    }

    public function test_unlimited_clock_out_opens_next_day_and_never_late_out(): void
    {
        $schedule = new ShiftSchedule(
            officeStart: '07:00:00',
            lateLimit: '07:15:00',
            clockOutStart: '07:00:00',
            clockOutLimit: null,
        );
        $dutyDate = Carbon::parse('2026-06-02');

        $this->assertTrue($schedule->isEarlyClockOut(Carbon::parse('2026-06-02 18:00:00'), $dutyDate));
        $this->assertTrue($schedule->isEarlyClockOut(Carbon::parse('2026-06-03 06:59:00'), $dutyDate));
        $this->assertFalse($schedule->isEarlyClockOut(Carbon::parse('2026-06-03 07:00:00'), $dutyDate));
        $this->assertFalse($schedule->isLateClockOut(Carbon::parse('2026-06-03 12:00:00')));
    }
}
