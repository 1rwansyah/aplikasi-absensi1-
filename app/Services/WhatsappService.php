<?php

namespace App\Services;

use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    public function sendGroupMessage(string $message): bool
    {
        try {
            $response = Http::timeout(5)
                ->withToken(config('services.wa_bot.token'))
                ->post(config('services.wa_bot.url').'/send-group', [
                    'groupId' => config('services.wa_bot.group_id'),
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::error('Gagal kirim WhatsApp absensi', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Gagal kirim WhatsApp absensi', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendGroupImage(?string $imageUrl, string $caption): void
    {
        if (! $imageUrl) {
            $this->sendGroupMessage($caption);

            return;
        }

        try {
            Http::timeout(20)
                ->withToken(config('services.wa_bot.token'))
                ->post(config('services.wa_bot.url').'/send-group-image', [
                    'groupId' => config('services.wa_bot.group_id'),
                    'imageUrl' => $imageUrl,
                    'caption' => $caption,
                ]);
        } catch (\Throwable $e) {
            Log::error('Gagal kirim foto WhatsApp absensi', [
                'error' => $e->getMessage(),
                'imageUrl' => $imageUrl,
            ]);
        }
    }

    public function sendAttendanceReport(?Carbon $date = null, string $type = 'masuk'): bool
    {
        $message = app(WhatsappAttendanceReportService::class)->generate($date, $type);

        return $this->sendGroupMessage($message);
    }

    public function sendDirectMessage(string $phone, string $message): bool
    {
        try {
            $response = Http::timeout(10)
                ->withToken(config('services.wa_bot.token'))
                ->post(config('services.wa_bot.url').'/send-message', [
                    'phone' => $phone,
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::error('Gagal kirim WhatsApp direct message', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Gagal kirim WhatsApp direct message', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendPayrollReport(string $phone, int $periodMonth, int $periodYear): bool
    {
        $message = app(WhatsappPayrollReportService::class)->generate($periodMonth, $periodYear);

        return $this->sendDirectMessage($phone, $message);
    }

    public function sendLeaveNotification(Attendance $attendance): void
    {
        $notification = app(WhatsappLeaveNotificationService::class);
        $caption = $notification->buildCaption($attendance);
        $imageUrl = $notification->resolveAttachmentUrl($attendance);

        if ($imageUrl !== null) {
            $this->sendGroupImage($imageUrl, $caption);

            return;
        }

        $this->sendGroupMessage($caption);
    }
}
