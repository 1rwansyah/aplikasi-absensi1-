<?php

namespace App\DTOs;

use App\Enums\AttendanceReportStatus;
use App\Models\Attendance;
use App\Models\User;

final class AttendanceReportRow
{
    public function __construct(
        public readonly User $user,
        public readonly ?Attendance $attendance,
    ) {}

    public function status(): AttendanceReportStatus
    {
        return AttendanceReportStatus::fromAttendance($this->attendance);
    }

    public function statusLabel(): string
    {
        return $this->status()->labelFor($this->attendance);
    }

    public function clockIn(): ?string
    {
        return $this->attendance?->formattedClockIn();
    }

    public function clockOut(): ?string
    {
        return $this->attendance?->formattedClockOut();
    }
}
