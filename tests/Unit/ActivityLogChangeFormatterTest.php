<?php

namespace Tests\Unit;

use App\Support\ActivityLogChangeFormatter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivityLogChangeFormatterTest extends TestCase
{
    #[Test]
    public function it_formats_create_payload_inline(): void
    {
        $formatted = app(ActivityLogChangeFormatter::class)->format([
            'reason' => 'Koreksi HR',
            'before' => [],
            'after' => [
                'type' => 'regular',
                'date' => '2026-06-30',
                'clock_in_time' => '09:00:00',
                'clock_out_time' => '18:00:00',
                'status' => 'on_time',
                'overtime_hours' => 0,
            ],
        ]);

        $this->assertStringContainsString('Alasan: Koreksi HR', $formatted);
        $this->assertStringContainsString('Tipe: Reguler', $formatted);
        $this->assertStringContainsString('Masuk: 09:00', $formatted);
        $this->assertStringContainsString('Pulang: 18:00', $formatted);
        $this->assertStringContainsString('Status: Tepat Waktu', $formatted);
        $this->assertStringNotContainsString('Lembur', $formatted);
        $this->assertStringNotContainsString('{', $formatted);
    }

    #[Test]
    public function it_formats_update_payload_with_before_after(): void
    {
        $formatted = app(ActivityLogChangeFormatter::class)->format([
            'reason' => 'Koreksi HR',
            'before' => [
                'clock_in_time' => '08:30:00',
                'clock_out_time' => '17:00:00',
                'status' => 'late',
            ],
            'after' => [
                'clock_in_time' => '09:00:00',
                'clock_out_time' => '18:00:00',
                'status' => 'on_time',
            ],
        ]);

        $this->assertStringContainsString('Masuk: 08:30 → 09:00', $formatted);
        $this->assertStringContainsString('Pulang: 17:00 → 18:00', $formatted);
        $this->assertStringContainsString('Status: Telat → Tepat Waktu', $formatted);
    }
}
