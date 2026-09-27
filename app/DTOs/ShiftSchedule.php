<?php

namespace App\DTOs;

use App\Enums\AttendanceStatus;
use App\Support\TimeFormat;
use App\Support\TimeWindow;
use Carbon\Carbon;

final class ShiftSchedule
{
    public function __construct(
        public readonly string $officeStart,
        public readonly string $lateLimit,
        public readonly string $clockOutStart,
        public readonly ?string $clockOutLimit,
    ) {}

    public function formattedOfficeStart(): string
    {
        return TimeFormat::display($this->officeStart);
    }

    public function formattedLateLimit(): string
    {
        return TimeFormat::display($this->lateLimit);
    }

    public function formattedClockOutStart(): string
    {
        return TimeFormat::display($this->clockOutStart);
    }

    public function formattedClockOutLimit(): ?string
    {
        return $this->clockOutLimit !== null ? TimeFormat::display($this->clockOutLimit) : null;
    }

    public function clockInWindowDescription(): string
    {
        return $this->formattedOfficeStart().' – '.$this->formattedLateLimit();
    }

    public function clockOutWindowDescription(): string
    {
        $start = $this->formattedClockOutStart();

        if ($this->hasUnlimitedClockOut()) {
            return "{$start} hari berikutnya (tanpa batas)";
        }

        $limit = $this->formattedClockOutLimit();

        if ($this->clockOutWindow()->isOvernight()) {
            return "{$start} – {$limit} (lewat tengah malam)";
        }

        return "{$start} – {$limit}";
    }

    public function resolveClockInStatus(Carbon $clockIn): AttendanceStatus
    {
        return $this->isLate($clockIn) ? AttendanceStatus::Late : AttendanceStatus::OnTime;
    }

    public function resolveClockOutStatus(Carbon $time, AttendanceStatus $current): AttendanceStatus
    {
        if ($this->isLateClockOut($time)) {
            return AttendanceStatus::LateOut;
        }

        return $current;
    }

    public function isLate(Carbon $clockIn): bool
    {
        return $clockIn->format('H:i:s') > TimeFormat::normalized($this->lateLimit);
    }

    public function hasUnlimitedClockOut(): bool
    {
        return $this->clockOutLimit === null;
    }

    public function isEarlyClockOut(Carbon $time, ?Carbon $dutyDate = null): bool
    {
        if ($dutyDate !== null && ($this->hasUnlimitedClockOut() || $this->isNextDayClockOutPattern())) {
            return $time->lessThan($this->nextDayClockOutOpensAt($dutyDate));
        }

        return $this->clockOutWindow()->isTooEarly($time);
    }

    public function isLateClockOut(Carbon $time): bool
    {
        return $this->clockOutWindow()->isTooLate($time);
    }

    public function canClockOutNow(Carbon $time, ?Carbon $dutyDate = null): bool
    {
        return ! $this->isEarlyClockOut($time, $dutyDate);
    }

    /**
     * Normal clock-out time (= clockOutStart) as a Carbon datetime anchored to $date.
     * For overnight shifts (e.g. 22:00–07:00 next day) the clock-out start
     * itself is still on the same $date, so no day offset is needed here.
     */
    public function normalClockOutCarbon(Carbon $date): Carbon
    {
        [$h, $m, $s] = explode(':', TimeFormat::normalized($this->clockOutStart));

        return $date->copy()->setTime((int) $h, (int) $m, (int) $s);
    }

    /**
     * End-of-window clock-out (= clockOutLimit) as Carbon anchored to $date.
     * This is the point from which overtime is counted.
     */
    public function clockOutLimitCarbon(Carbon $date): Carbon
    {
        if ($this->clockOutLimit === null) {
            return $this->nextDayClockOutOpensAt($date);
        }

        [$h, $m, $s] = explode(':', TimeFormat::normalized($this->clockOutLimit));

        return $date->copy()->setTime((int) $h, (int) $m, (int) $s);
    }

    public function isOvernightClockOut(): bool
    {
        return $this->clockOutWindow()->isOvernight();
    }

    /**
     * Shift 24 jam / security malam: jam pulang (mis. 07:00) <= jam masuk (mis. 07:00 atau 19:00)
     * berarti pulang efektif jatuh di hari duty berikutnya, bukan pagi hari yang sama.
     */
    public function isNextDayClockOutPattern(): bool
    {
        if ($this->hasUnlimitedClockOut() || $this->isOvernightClockOut()) {
            return false;
        }

        return TimeFormat::normalized($this->clockOutStart) <= TimeFormat::normalized($this->officeStart);
    }

    public function opensClockOutOnNextDutyDay(): bool
    {
        return $this->hasUnlimitedClockOut()
            || $this->isOvernightClockOut()
            || $this->isNextDayClockOutPattern();
    }

    /**
     * Day/office shift: enforce earliest clock-out start (e.g. work calendar full/half day).
     * Does not apply to overnight windows — caller must skip for Night shift.
     */
    public function withMinimumClockOutStart(string $minimum): self
    {
        if ($this->isOvernightClockOut() || $this->isNextDayClockOutPattern()) {
            return $this;
        }

        $min = TimeFormat::normalized($minimum);
        $start = TimeFormat::normalized($this->clockOutStart);
        $limit = $this->clockOutLimit !== null ? TimeFormat::normalized($this->clockOutLimit) : null;

        $effectiveStart = max($start, $min);
        $effectiveLimit = $limit !== null ? max($limit, $effectiveStart) : null;

        if ($effectiveStart === $start && $effectiveLimit === $limit) {
            return $this;
        }

        return new self(
            $this->officeStart,
            $this->lateLimit,
            $effectiveStart,
            $effectiveLimit,
        );
    }

    /**
     * Half-day work calendar: clock-out opens at the calendar time (e.g. 14:00),
     * even when shift settings start later the same day.
     */
    /**
     * Jam pulang normal efektif untuk perhitungan lembur.
     * - Shift same-day: clockOutStart pada hari duty.
     * - Shift overnight / unlimited (security 24 jam): clockOutStart/limit pada hari berikutnya.
     */
    public function effectiveNormalClockOutCarbon(Carbon $dutyDate): Carbon
    {
        if ($this->hasUnlimitedClockOut()) {
            return $this->nextDayClockOutOpensAt($dutyDate);
        }

        if ($this->isOvernightClockOut() || $this->isNextDayClockOutPattern()) {
            $reference = $this->clockOutLimit ?? $this->clockOutStart;
            [$h, $m, $s] = explode(':', TimeFormat::normalized($reference));

            return $dutyDate->copy()->addDay()->setTime((int) $h, (int) $m, (int) $s);
        }

        return $this->normalClockOutCarbon($dutyDate);
    }

    /**
     * Lembur dihitung setelah jeda 1 jam dari jam pulang normal efektif.
     * Berlaku untuk full day, half day, overnight, dan security 24 jam.
     */
    public function overtimeHoursFromClockOutOpen(Carbon $date, Carbon $now): float
    {
        $overtimeStart = $this->effectiveNormalClockOutCarbon($date)->copy()->addHour();

        if ($now->lessThanOrEqualTo($overtimeStart)) {
            return 0.0;
        }

        return round($overtimeStart->diffInMinutes($now) / 60, 2);
    }

    /**
     * @deprecated Use overtimeHoursFromClockOutOpen() — same grace rule for all shift types.
     */
    public function overtimeHoursFromClockOutLimit(Carbon $date, Carbon $now): float
    {
        return $this->overtimeHoursFromClockOutOpen($date, $now);
    }

    public function withCalendarClockOutStart(string $opensAt): self
    {
        if ($this->isOvernightClockOut() || $this->isNextDayClockOutPattern()) {
            return $this;
        }

        $effectiveStart = TimeFormat::normalized($opensAt);
        $limit = $this->clockOutLimit !== null ? TimeFormat::normalized($this->clockOutLimit) : null;
        $effectiveLimit = $limit !== null ? max($limit, $effectiveStart) : null;

        if ($effectiveStart === TimeFormat::normalized($this->clockOutStart) && $effectiveLimit === $limit) {
            return $this;
        }

        return new self(
            $this->officeStart,
            $this->lateLimit,
            $effectiveStart,
            $effectiveLimit,
        );
    }

    private function nextDayClockOutOpensAt(Carbon $dutyDate): Carbon
    {
        [$h, $m, $s] = explode(':', TimeFormat::normalized($this->clockOutStart));

        return $dutyDate->copy()->addDay()->setTime((int) $h, (int) $m, (int) $s);
    }

    private function clockOutWindow(): TimeWindow
    {
        return TimeWindow::create($this->clockOutStart, $this->clockOutLimit);
    }
}
