<?php

namespace App\Services;

use App\Enums\WorkCalendarType;
use App\Models\HolidayImport;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkCalendarGeneratorService
{
    const SOURCE = 'api.co.id';

    const HOLIDAY_TYPE = 'National Holiday';

    public function generate(int $year): array
    {
        $warnings = [];

        // 1. Generate all dates for the year using weekday/weekend rules
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        $current = $start->copy();
        while ($current->lte($end)) {
            $type = match (true) {
                $current->isSunday() => WorkCalendarType::Holiday,
                $current->isSaturday() => WorkCalendarType::HalfDay,
                default => WorkCalendarType::FullDay,
            };

            WorkCalendar::updateOrCreate(
                ['date' => $current->toDateString()],
                ['type' => $type]
            );

            $current->addDay();
        }

        // 2. Fetch & apply holidays (skip if already imported)
        $apiKey = config('services.api_co.id');

        if (blank($apiKey)) {
            $warnings[] = 'API key hari libur belum tersedia. Kalender dibuat tanpa sinkron hari libur nasional.';

            return $warnings;
        }

        $import = HolidayImport::where('year', $year)
            ->where('type', self::HOLIDAY_TYPE)
            ->where('source', self::SOURCE)
            ->first();

        $holidays = null;

        if ($import) {
            // Use cached response — parse data array from stored JSON
            $cached = json_decode($import->response_json, true);
            $holidays = $cached['data'] ?? $cached;
        } else {
            // Fetch from API
            try {
                $response = Http::withHeaders([
                    'x-api-co-id' => $apiKey,
                ])->timeout(10)->get('https://use.api.co.id/holidays/indonesia', [
                    'year' => $year,
                ]);

                if ($response->successful() && ($response->json('is_success') === true)) {
                    $holidays = $response->json('data') ?? [];

                    HolidayImport::updateOrCreate(
                        ['year' => $year, 'type' => self::HOLIDAY_TYPE, 'source' => self::SOURCE],
                        [
                            'synced_at' => now(),
                            'response_json' => $response->body(),
                        ]
                    );
                } else {
                    $warnings[] = 'API hari libur mengembalikan error ('.$response->status().'). Kalender dibuat tanpa data hari libur nasional.';
                    Log::warning('Holiday API error', ['status' => $response->status(), 'body' => $response->body()]);
                }
            } catch (\Throwable $e) {
                $warnings[] = 'Gagal menghubungi API hari libur: '.$e->getMessage().'. Kalender dibuat tanpa data hari libur nasional.';
                Log::error('Holiday API exception', ['message' => $e->getMessage()]);
            }
        }

        // 3. Apply holidays to work_calendars (skip manual override rows)
        if (is_array($holidays)) {
            foreach ($holidays as $item) {
                $date = $item['date'] ?? null;
                $name = $item['name'] ?? null;

                if (! $date) {
                    continue;
                }

                $row = WorkCalendar::where('date', $date)->first();

                if (! $row) {
                    continue;
                }

                if ($row->is_manual_override) {
                    continue;
                }

                $row->update([
                    'type' => WorkCalendarType::Holiday,
                    'name' => $name,
                    'note' => self::HOLIDAY_TYPE,
                    'source' => self::SOURCE,
                ]);
            }
        }

        return $warnings;
    }

    public function syncHolidays(int $year): array
    {
        $warnings = [];
        $apiKey = config('services.api_co.id');

        if (blank($apiKey)) {
            $warnings[] = 'API key hari libur belum tersedia.';

            return $warnings;
        }

        try {
            $response = Http::withHeaders([
                'x-api-co-id' => $apiKey,
            ])->timeout(10)->get('https://use.api.co.id/holidays/indonesia', [
                'year' => $year,
            ]);

            if (! $response->successful() || $response->json('is_success') !== true) {
                $warnings[] = 'API hari libur mengembalikan error ('.$response->status().').';

                return $warnings;
            }

            $holidays = $response->json('data') ?? [];

            HolidayImport::updateOrCreate(
                ['year' => $year, 'type' => self::HOLIDAY_TYPE, 'source' => self::SOURCE],
                [
                    'synced_at' => now(),
                    'response_json' => $response->body(),
                ]
            );

            if (is_array($holidays)) {
                foreach ($holidays as $item) {
                    $date = $item['date'] ?? null;
                    $name = $item['name'] ?? null;

                    if (! $date) {
                        continue;
                    }

                    $row = WorkCalendar::where('date', $date)->first();

                    if (! $row || $row->is_manual_override) {
                        continue;
                    }

                    $row->update([
                        'type' => WorkCalendarType::Holiday,
                        'name' => $name,
                        'note' => self::HOLIDAY_TYPE,
                        'source' => self::SOURCE,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $warnings[] = 'Gagal menghubungi API hari libur: '.$e->getMessage();
            Log::error('Holiday sync exception', ['message' => $e->getMessage()]);
        }

        return $warnings;
    }
}
