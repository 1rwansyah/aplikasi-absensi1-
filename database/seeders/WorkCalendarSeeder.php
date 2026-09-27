<?php

namespace Database\Seeders;

use App\Enums\WorkCalendarType;
use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WorkCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $year = 2026;
        $start = Carbon::create($year, 1, 1);
        $end = Carbon::create($year, 12, 31);

        $current = $start->copy();

        while ($current->lte($end)) {
            $type = match ($current->dayOfWeek) {
                Carbon::SUNDAY => WorkCalendarType::Holiday,
                Carbon::SATURDAY => WorkCalendarType::HalfDay,
                default => WorkCalendarType::FullDay,
            };

            WorkCalendar::updateOrCreate(
                ['date' => $current->toDateString()],
                [
                    'type' => $type->value,
                    'name' => null,
                    'note' => null,
                    'is_manual_override' => false,
                ]
            );

            $current->addDay();
        }
    }
}
