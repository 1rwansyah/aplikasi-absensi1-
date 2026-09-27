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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AttendanceOvertimeTest extends TestCase
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

    #[DataProvider('fullDayOvertimeProvider')]
    public function test_full_day_day_shift_overtime_by_clock_out_time(string $time, float $expectedHours): void
    {
        $date = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::FullDay]);
        $attendance = $this->createAttendance($date, AttendanceShift::Day);

        $this->assertOvertimeAt($attendance, $date, $time, $expectedHours);
    }

    public static function fullDayOvertimeProvider(): array
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

    #[DataProvider('halfDayOvertimeProvider')]
    public function test_half_day_day_shift_overtime_by_clock_out_time(string $time, float $expectedHours): void
    {
        $date = Carbon::parse('2026-06-06');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::HalfDay]);
        $attendance = $this->createAttendance($date, AttendanceShift::Day);

        $this->assertOvertimeAt($attendance, $date, $time, $expectedHours);
    }

    public static function halfDayOvertimeProvider(): array
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

    #[DataProvider('securityOvernightOvertimeProvider')]
    public function test_security_overnight_overtime_by_clock_out_time(string $time, float $expectedHours): void
    {
        $this->seed(WorkScheduleSeeder::class);

        $dutyDate = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $dutyDate, 'type' => WorkCalendarType::FullDay]);

        $securitySchedule = WorkSchedule::where('code', 'security')->firstOrFail();
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $securitySchedule->id,
            'date' => $dutyDate,
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $nextDay = $dutyDate->copy()->addDay();
        $at = Carbon::parse($nextDay->toDateString().' '.$time, AppTime::timezone());

        $this->assertSame(
            $expectedHours,
            $this->service->overtimeHoursFor($attendance, $at),
            "security overtime at next day {$time}",
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

    public function test_next_day_clock_out_pattern_does_not_count_23_hours_on_morning_clock_out(): void
    {
        $nightSchedule = WorkSchedule::updateOrCreate(
            ['code' => 'night'],
            [
                'name' => 'Security Malam',
                'clock_in_start' => '19:00:00',
                'late_limit' => '19:15:00',
                'clock_out_start' => '07:00:00',
                'clock_out_limit' => '07:15:00',
                'is_off' => false,
            ],
        );

        $dutyDate = Carbon::parse('2026-06-02');
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $nightSchedule->id,
            'date' => $dutyDate,
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '19:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $clockOut = Carbon::parse('2026-06-03 07:00:00', AppTime::timezone());

        $this->assertSame(
            0.0,
            $this->service->overtimeHoursFor($attendance, $clockOut),
            'Pulang pagi hari berikutnya tidak boleh dihitung ~23 jam lembur',
        );
    }

    public function test_security_with_clock_out_limit_still_uses_next_day_pattern(): void
    {
        $securitySchedule = WorkSchedule::updateOrCreate(
            ['code' => 'security'],
            [
                'name' => 'Security',
                'clock_in_start' => '07:00:00',
                'late_limit' => '07:15:00',
                'clock_out_start' => '07:00:00',
                'clock_out_limit' => '07:15:00',
                'is_off' => false,
            ],
        );

        $dutyDate = Carbon::parse('2026-06-02');
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_schedule_id' => $securitySchedule->id,
            'date' => $dutyDate,
            'type' => AttendanceType::Regular,
            'shift' => AttendanceShift::Day,
            'clock_in_time' => '07:00:00',
            'status' => AttendanceStatus::OnTime,
        ]);

        $clockOut = Carbon::parse('2026-06-03 07:00:00', AppTime::timezone());

        $this->assertSame(0.0, $this->service->overtimeHoursFor($attendance, $clockOut));
    }

    public function test_holiday_uses_same_grace_rule_from_effective_clock_out(): void
    {
        $date = Carbon::parse('2026-06-07');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::Holiday, 'name' => 'Minggu']);
        $attendance = $this->createAttendance($date, AttendanceShift::Day);

        $schedule = $this->service->clockOutScheduleFor($attendance);
        $this->assertSame('17:00:00', $schedule->clockOutStart);

        $at = Carbon::parse($date->toDateString().' 20:00:00', AppTime::timezone());
        $this->assertSame(2.0, $this->service->overtimeHoursFor($attendance, $at));
        $this->assertSame(2.0, $schedule->overtimeHoursFromClockOutOpen($date, $at));
    }

    public function test_night_shift_overnight_overtime_starts_after_next_day_limit_plus_grace(): void
    {
        $date = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::FullDay]);

        $nightAttendance = $this->createAttendance($date, AttendanceShift::Night, '18:00:00');
        $dayAttendance = $this->createAttendance($date, AttendanceShift::Day);

        $duringShift = Carbon::parse('2026-06-02 20:00:00', AppTime::timezone());
        $this->assertSame(0.0, $this->service->overtimeHoursFor($nightAttendance, $duringShift));
        $this->assertSame(1.0, $this->service->overtimeHoursFor($dayAttendance, $duringShift));

        $nextMorning = Carbon::parse('2026-06-03 09:30:00', AppTime::timezone());
        $this->assertSame(1.5, $this->service->overtimeHoursFor($nightAttendance, $nextMorning));
    }

    public function test_shift_schedule_open_overtime_matches_service_full_and_half_day(): void
    {
        $fullDate = Carbon::parse('2026-06-02');
        WorkCalendar::create(['date' => $fullDate, 'type' => WorkCalendarType::FullDay]);
        $fullAttendance = $this->createAttendance($fullDate, AttendanceShift::Day);

        $halfDate = Carbon::parse('2026-06-06');
        WorkCalendar::create(['date' => $halfDate, 'type' => WorkCalendarType::HalfDay]);
        $halfAttendance = $this->createAttendance($halfDate, AttendanceShift::Day);

        $fullSchedule = $this->service->clockOutScheduleFor($fullAttendance);
        $halfSchedule = $this->service->clockOutScheduleFor($halfAttendance);

        $at = Carbon::parse('2026-06-02 19:30:00', AppTime::timezone());
        $this->assertSame(0.5, $fullSchedule->overtimeHoursFromClockOutOpen($fullDate, $at));
        $this->assertSame(0.5, $this->service->overtimeHoursFor($fullAttendance, $at));

        $atHalf = Carbon::parse('2026-06-06 15:30:00', AppTime::timezone());
        $this->assertSame(0.5, $halfSchedule->overtimeHoursFromClockOutOpen($halfDate, $atHalf));
        $this->assertSame(0.5, $this->service->overtimeHoursFor($halfAttendance, $atHalf));
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

    private function assertOvertimeAt(
        Attendance $attendance,
        Carbon $date,
        string $time,
        float $expectedHours,
    ): void {
        $at = Carbon::parse($date->toDateString().' '.$time, AppTime::timezone());

        $this->assertSame(
            $expectedHours,
            $this->service->overtimeHoursFor($attendance, $at),
            "overtime at {$time}",
        );
    }
}
