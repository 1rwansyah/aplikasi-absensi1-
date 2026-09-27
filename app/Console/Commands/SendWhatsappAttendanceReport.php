<?php

namespace App\Console\Commands;

use App\Models\WorkCalendar;
use App\Services\WhatsappService;
use Illuminate\Console\Command;

class SendWhatsappAttendanceReport extends Command
{
    protected $signature = 'attendance:send-whatsapp-report {type=masuk : Report type: masuk or pulang}';

    protected $description = 'Send daily WhatsApp attendance report';

    public function handle(WhatsappService $whatsappService): int
    {
        $type = $this->argument('type');

        if (! in_array($type, ['masuk', 'pulang'], true)) {
            $this->error('Invalid report type. Use masuk or pulang.');

            return self::FAILURE;
        }

        $today = today();

        if ($today->isSunday()) {
            $this->info('Skipped: Sunday.');

            return self::SUCCESS;
        }

        $isHoliday = WorkCalendar::whereDate('date', $today)
            ->where('type', 'holiday')
            ->exists();

        if ($isHoliday) {
            $this->info('Skipped: holiday.');

            return self::SUCCESS;
        }

        $sent = $whatsappService->sendAttendanceReport($today, $type);

        if (! $sent) {
            $this->error("Failed to send WhatsApp {$type} attendance report.");

            return self::FAILURE;
        }

        $this->info("WhatsApp {$type} attendance report sent.");

        return self::SUCCESS;
    }
}
