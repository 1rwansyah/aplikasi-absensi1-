<?php

namespace App\Notifications;

use App\Models\Attendance;
use App\Support\AppTime;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class LeaveRejectedNotification extends Notification
{
    public function __construct(
        public readonly Attendance $attendance,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $attendance = $this->attendance->loadMissing(['verifiedBy', 'user']);
        $typeLabel = $attendance->type->label();
        $dateLabel = $attendance->date->translatedFormat('d F Y');
        $verifier = $attendance->verifiedBy?->name ?? 'HR';
        $reason = trim((string) $attendance->rejection_reason);
        $canResubmitToday = $attendance->date->isSameDay(AppTime::today())
            && AppTime::now()->format('H:i:s') < '23:00:00';

        $mail = (new MailMessage)
            ->subject("Pengajuan {$typeLabel} ditolak — {$dateLabel}")
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line("Pengajuan {$typeLabel} Anda untuk tanggal {$dateLabel} telah ditolak.")
            ->line('Ditolak oleh: '.$verifier);

        if ($reason !== '') {
            $mail->line('**Alasan penolakan:**')
                ->line("> **{$reason}**");
        }

        if ($canResubmitToday) {
            $mail->line('Lengkapi keterangan dan bukti pendukung, lalu ajukan kembali melalui aplikasi atau website SADAR sebelum pukul 23.00 hari ini. Pengajuan ulang tetap memerlukan persetujuan HR.');
        }

        if ($attendance->isRejectedSick()) {
            $mail->line('Status absensi: sakit (ditolak). Uang makan untuk hari tersebut tidak diberikan.');
            $statusMessage = 'Status absensi tercatat sebagai sakit (ditolak). Uang makan untuk hari tersebut tidak diberikan.';
        } else {
            $mail->line('Status absensi: tercatat sebagai alfa untuk hari tersebut.');
            $statusMessage = 'Status absensi tercatat sebagai alfa untuk hari tersebut.';
        }

        return $mail
            ->action('Lihat Status Pengajuan', url(route('leave-verification.my-submissions', absolute: false)))
            ->salutation(new HtmlString('Regards,<br>Tim HR'))
            ->markdown('mail.leave-rejected', [
                'employeeName' => $notifiable->name,
                'typeLabel' => $typeLabel,
                'dateLabel' => $dateLabel,
                'verifier' => $verifier,
                'reason' => $reason,
                'statusMessage' => $statusMessage,
                'canResubmitToday' => $canResubmitToday,
                'statusUrl' => url(route('leave-verification.my-submissions', absolute: false)),
            ]);
    }
}
