<?php

namespace App\Models;

use App\DTOs\ShiftSchedule;
use App\Enums\AttendanceReportStatus;
use App\Enums\AttendanceShift;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Services\EmployeeScheduleService;
use App\Services\FaceVerificationService;
use App\Support\RichTextSanitizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'type',
        'shift',
        'work_schedule_id',
        'clock_in_time',
        'clock_out_time',
        'clock_in_report',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_location',
        'clock_in_verification_photo',
        'clock_in_face_distance',
        'clock_out_report',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_location',
        'clock_out_verification_photo',
        'clock_out_face_distance',
        'leave_note',
        'doctor_note_path',
        'status',
        'overtime_hours',
        'accuracy',
        'device_type',
        'validation_status',
        'suspicious_reason',
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'last_submission_at',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => AttendanceType::class,
        'shift' => AttendanceShift::class,
        'status' => AttendanceStatus::class,
        'verification_status' => VerificationStatus::class,
        'verified_at' => 'datetime',
        'last_submission_at' => 'datetime',
        'clock_in_latitude' => 'float',
        'clock_in_longitude' => 'float',
        'clock_out_latitude' => 'float',
        'clock_out_longitude' => 'float',
        'clock_in_face_distance' => 'float',
        'clock_out_face_distance' => 'float',
        'overtime_hours' => 'float',
        'accuracy' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForUserOnDate(Builder $query, int $userId, Carbon $date): Builder
    {
        return $query->forUser($userId)->whereDate('date', $date);
    }

    public function isLeave(): bool
    {
        return $this->type->isLeave();
    }

    public function isApprovedLeave(): bool
    {
        return $this->isLeave() && $this->verification_status->isDone();
    }

    public function isRejectedLeave(): bool
    {
        return $this->isLeave() && $this->verification_status->isRejected();
    }

    public function isPendingLeave(): bool
    {
        return $this->isLeave() && $this->verification_status->isPending();
    }

    public function isRegular(): bool
    {
        return $this->type === AttendanceType::Regular;
    }

    public function scopeApprovedLeave(Builder $query): Builder
    {
        return $query
            ->whereIn('type', [AttendanceType::Sick, AttendanceType::Permission])
            ->where('verification_status', VerificationStatus::Done);
    }

    public function scopePendingLeave(Builder $query): Builder
    {
        return $query
            ->whereIn('type', [AttendanceType::Sick, AttendanceType::Permission])
            ->where('verification_status', VerificationStatus::Pending);
    }

    public function scopeRejectedSick(Builder $query): Builder
    {
        return $query
            ->where('type', AttendanceType::Sick)
            ->where('verification_status', VerificationStatus::NoDone);
    }

    public function isRejectedSick(): bool
    {
        return $this->type === AttendanceType::Sick && $this->isRejectedLeave();
    }

    public function scopeExcusedForAttendance(Builder $query): Builder
    {
        return $query->where(function (Builder $inner) {
            $inner->where('type', AttendanceType::Regular)
                ->orWhere(function (Builder $leave) {
                    $leave->whereIn('type', [AttendanceType::Sick, AttendanceType::Permission])
                        ->where('verification_status', '!=', VerificationStatus::NoDone);
                });
        });
    }

    public function statusLabel(): string
    {
        return AttendanceReportStatus::fromAttendance($this)->labelFor($this);
    }

    public function statusBadgeClasses(): string
    {
        return AttendanceReportStatus::fromAttendance($this)->badgeClasses();
    }

    public function typeLabel(): string
    {
        return $this->type->label();
    }

    public function clockInReportText(): ?string
    {
        return RichTextSanitizer::toPlainText($this->clock_in_report);
    }

    public function clockOutReportText(): ?string
    {
        return RichTextSanitizer::toPlainText($this->clock_out_report);
    }

    public function leaveNoteText(): ?string
    {
        return RichTextSanitizer::toPlainText($this->leave_note);
    }

    /**
     * HTML keterangan izin/sakit siap render ({!! !!} / innerHTML).
     */
    public function leaveNoteHtml(): ?string
    {
        return RichTextSanitizer::sanitizeHtml($this->leave_note);
    }

    /**
     * HTML laporan masuk siap render ({!! !!} / innerHTML).
     */
    public function clockInReportHtml(): ?string
    {
        return RichTextSanitizer::sanitizeHtml($this->clock_in_report);
    }

    /**
     * HTML laporan pulang siap render ({!! !!} / innerHTML).
     */
    public function clockOutReportHtml(): ?string
    {
        return RichTextSanitizer::sanitizeHtml($this->clock_out_report);
    }

    public function shiftLabel(): string
    {
        return $this->shift?->label() ?? AttendanceShift::Day->label();
    }

    public function shiftSchedule(): ShiftSchedule
    {
        $schedule = $this->resolvedWorkSchedule();

        if ($schedule !== null && ! $schedule->is_off) {
            return $schedule->toShiftSchedule();
        }

        return Setting::current()->scheduleFor($this->shift ?? AttendanceShift::Day);
    }

    /**
     * Jadwal efektif untuk absensi ini. Petugas security yang tersimpan sebagai
     * reguler (fallback saat clock-in tanpa assignment) dipetakan ulang ke jadwal mingguan.
     */
    public function resolvedWorkSchedule(): ?WorkSchedule
    {
        $stored = $this->workSchedule;

        $employee = $this->user?->employee;
        if ($employee === null || strcasecmp((string) $employee->staff, 'Security') !== 0) {
            return $stored;
        }

        $assigned = app(EmployeeScheduleService::class)
            ->getScheduleForDate($employee, $this->date);

        if ($stored === null) {
            return $assigned;
        }

        if ($stored->code === 'regular' && $assigned->code !== 'regular' && ! $assigned->is_off) {
            return $assigned;
        }

        return $stored;
    }

    public function usesOfficeWorkCalendar(): bool
    {
        $schedule = $this->resolvedWorkSchedule();

        if ($schedule === null) {
            return true;
        }

        return in_array($schedule->code, ['regular', 'ob']);
    }

    public function hasDoctorNote(): bool
    {
        return filled($this->doctor_note_path);
    }

    public function doctorNoteViewUrl(): string
    {
        return route('attendance.note.show', $this);
    }

    public function doctorNotePublicUrl(): ?string
    {
        if (! $this->hasDoctorNote()) {
            return null;
        }

        return asset('storage/'.$this->doctor_note_path);
    }

    public function doctorNoteIsImage(): bool
    {
        if (! $this->hasDoctorNote()) {
            return false;
        }

        $extension = strtolower(pathinfo((string) $this->doctor_note_path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png'], true);
    }

    public function formattedClockIn(): ?string
    {
        return $this->clock_in_time ? substr((string) $this->clock_in_time, 0, 5) : null;
    }

    public function formattedClockOut(): ?string
    {
        return $this->clock_out_time ? substr((string) $this->clock_out_time, 0, 5) : null;
    }

    public function clockInVerificationPhotoUrl(): ?string
    {
        return $this->clock_in_verification_photo
                ? asset('storage/'.$this->clock_in_verification_photo)
                : null;

    }

    public function clockOutVerificationPhotoUrl(): ?string
    {
        return $this->clock_out_verification_photo
                ? asset('storage/'.$this->clock_out_verification_photo)
                : null;

    }

    public function clockInFaceMatchPercent(): ?int
    {
        return app(FaceVerificationService::class)->matchPercentFromDistance($this->clock_in_face_distance);
    }

    public function clockOutFaceMatchPercent(): ?int
    {
        return app(FaceVerificationService::class)->matchPercentFromDistance($this->clock_out_face_distance);
    }

    public function clockInFaceMatchBadgeType(): string
    {
        return app(FaceVerificationService::class)->matchPercentBadgeType($this->clockInFaceMatchPercent());
    }

    public function clockOutFaceMatchBadgeType(): string
    {
        return app(FaceVerificationService::class)->matchPercentBadgeType($this->clockOutFaceMatchPercent());
    }

    public function clockInMapsUrl(): ?string
    {
        return $this->mapsUrl($this->clock_in_latitude, $this->clock_in_longitude);
    }

    public function clockOutMapsUrl(): ?string
    {
        return $this->mapsUrl($this->clock_out_latitude, $this->clock_out_longitude);
    }

    public function clockInLocationLabel(): ?string
    {
        return $this->locationLabel(
            $this->clock_in_location,
            $this->clock_in_latitude,
            $this->clock_in_longitude,
        );
    }

    public function clockOutLocationLabel(): ?string
    {
        return $this->locationLabel(
            $this->clock_out_location,
            $this->clock_out_latitude,
            $this->clock_out_longitude,
        );
    }

    private function locationLabel(?string $address, ?float $latitude, ?float $longitude): ?string
    {
        if (filled($address)) {
            return $address;
        }

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return sprintf('%.6f, %.6f', $latitude, $longitude);
    }

    private function mapsUrl(?float $latitude, ?float $longitude): ?string
    {
        if ($latitude === null || $longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps?q='.urlencode("{$latitude},{$longitude}");
    }
}
