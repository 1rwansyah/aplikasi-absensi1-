<?php

namespace App\Services;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Support\AppTime;

class WhatsappLeaveNotificationService
{
    public function buildCaption(Attendance $attendance): string
    {
        $attendance->loadMissing('user.employee');

        $user = $attendance->user;
        $employee = $user?->employee;
        $name = $employee?->name ?? $user?->name ?? '—';

        $header = match ($attendance->type) {
            AttendanceType::Sick => '🟡 LAPORAN SAKIT',
            AttendanceType::Permission => '🟣 LAPORAN IZIN',
            default => '📋 LAPORAN ABSENSI',
        };

        $submittedAt = AppTime::now()->format('H:i');
        $note = $attendance->leaveNoteText() ?? '-';

        $lines = [
            $header,
            '',
            "Nama: {$name}",
             'Tanggal: '.$attendance->date->translatedFormat('l, d F Y'),
            "Jam: {$submittedAt}",
            '',
            '📝 Keterangan:',
            $note,
        ];

        if ($attendance->hasDoctorNote() && ! $attendance->doctorNoteIsImage()) {
            $label = $attendance->type === AttendanceType::Sick
                ? 'Surat dokter'
                : 'Bukti izin';

            $lines[] = '';
            $lines[] = "📎 {$label}: PDF tersimpan di sistem HRIS";
        }

        return implode("\n", $lines);
    }

    public function resolveAttachmentUrl(Attendance $attendance): ?string
    {
        if (! $attendance->doctorNoteIsImage()) {
            return null;
        }

        return $attendance->doctorNotePublicUrl();
    }
}
