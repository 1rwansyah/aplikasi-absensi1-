<?php

namespace Tests\Unit;

use App\Enums\WorkCalendarType;
use App\Models\WorkCalendar;
use App\Services\WorkCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WorkCalendarClockOutTest extends TestCase
{
    use RefreshDatabase;

    private WorkCalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WorkCalendarService::class);
    }

    public function test_full_day_minimum_clock_out_is_eighteen(): void
    {
        $date = Carbon::parse('2026-06-02');

        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::FullDay]);

        $this->assertSame(
            WorkCalendarService::DAY_SHIFT_FULL_DAY_CLOCK_OUT,
            $this->service->dayShiftMinimumClockOutStart($date),
        );
    }

    public function test_half_day_minimum_clock_out_is_fourteen(): void
    {
        $date = Carbon::parse('2026-06-06');

        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::HalfDay]);

        $this->assertSame(
            WorkCalendarService::DAY_SHIFT_HALF_DAY_CLOCK_OUT,
            $this->service->dayShiftMinimumClockOutStart($date),
        );
        $this->assertStringContainsString('14:00', $this->service->halfDayNotice($date) ?? '');
    }

    public function test_holiday_has_no_minimum_floor(): void
    {
        $date = Carbon::parse('2026-06-07');

        WorkCalendar::create(['date' => $date, 'type' => WorkCalendarType::Holiday, 'name' => 'Minggu']);

        $this->assertNull($this->service->dayShiftMinimumClockOutStart($date));
        $this->assertNull($this->service->halfDayNotice($date));
    }

    public function test_holiday_does_not_apply_full_day_or_half_day_minimum_constants(): void
    {
        $date = Carbon::parse('2026-12-25');

        WorkCalendar::create([
            'date' => $date,
            'type' => WorkCalendarType::Holiday,
            'name' => 'Natal',
        ]);

        $minimum = $this->service->dayShiftMinimumClockOutStart($date);

        $this->assertNull($minimum);
        $this->assertNotSame(WorkCalendarService::DAY_SHIFT_FULL_DAY_CLOCK_OUT, $minimum);
        $this->assertNotSame(WorkCalendarService::DAY_SHIFT_HALF_DAY_CLOCK_OUT, $minimum);
    }
}
