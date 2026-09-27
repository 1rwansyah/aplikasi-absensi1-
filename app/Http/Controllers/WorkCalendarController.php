<?php

namespace App\Http\Controllers;

use App\Enums\WorkCalendarType;
use App\Models\HolidayImport;
use App\Models\WorkCalendar;
use App\Services\ActivityLogService;
use App\Services\WorkCalendarGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkCalendarController extends Controller
{
    public function __construct(
        private readonly WorkCalendarGeneratorService $generator,
    ) {}

    public function index(Request $request): View
    {
        $year = (int) ($request->input('year', now()->year));

        $days = WorkCalendar::whereYear('date', $year)
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($d) => $d->date->format('Y-m-d'));

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $firstDay = Carbon::create($year, $m, 1);
            $daysInMonth = $firstDay->daysInMonth;
            $startOffset = ($firstDay->dayOfWeek + 6) % 7; // Mon=0

            $cells = [];
            for ($i = 0; $i < $startOffset; $i++) {
                $cells[] = null;
            }
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = Carbon::create($year, $m, $d)->format('Y-m-d');
                $cells[] = $days->get($dateStr);
            }

            $monthDays = $days->filter(fn ($d) => $d->date->month === $m);
            $months[$m] = [
                'name' => $firstDay->translatedFormat('F'),
                'cells' => $cells,
                'full_day' => $monthDays->filter(fn ($d) => $d->type === WorkCalendarType::FullDay)->count(),
                'half_day' => $monthDays->filter(fn ($d) => $d->type === WorkCalendarType::HalfDay)->count(),
                'holiday' => $monthDays->filter(fn ($d) => $d->type === WorkCalendarType::Holiday)->count(),
                'work_days' => $monthDays->sum(fn ($d) => $d->type->weight()),
            ];
        }

        $yearGenerated = WorkCalendar::whereYear('date', $year)->exists();
        $lastImport = HolidayImport::where('year', $year)->latest('synced_at')->first();

        return view('work-calendars.index', compact('year', 'months', 'yearGenerated', 'lastImport'));
    }

    public function generate(Request $request): RedirectResponse
    {
        $year = (int) $request->input('year', now()->year);

        if (WorkCalendar::whereYear('date', $year)->exists()) {
            return back()->with('error', "Kalender tahun {$year} sudah dibuat.");
        }

        $warnings = $this->generator->generate($year);

        if (count($warnings) > 0) {
            return redirect()
                ->route('work-calendars.index', ['year' => $year])
                ->with('warning', implode(' ', $warnings))
                ->with('success', "Kalender tahun {$year} berhasil dibuat.");
        }

        ActivityLogService::log(
            auth()->user(),
            'create',
            "Membuat kalender kerja tahun {$year}"
        );

        return redirect()
            ->route('work-calendars.index', ['year' => $year])
            ->with('success', "Kalender tahun {$year} berhasil dibuat sesuaikan hari libur nasional.");
    }

    public function syncHolidays(Request $request): RedirectResponse
    {
        $year = (int) $request->input('year', now()->year);

        if (! WorkCalendar::whereYear('date', $year)->exists()) {
            return back()->with('error', "Kalender tahun {$year} belum dibuat. Generate terlebih dahulu.");
        }

        $warnings = $this->generator->syncHolidays($year);

        if (count($warnings) > 0) {
            return redirect()
                ->route('work-calendars.index', ['year' => $year])
                ->with('error', implode(' ', $warnings));
        }

        ActivityLogService::log(
            auth()->user(),
            'sync',
            "Sinkronisasi hari libur nasional tahun {$year}"
        );

        return redirect()
            ->route('work-calendars.index', ['year' => $year])
            ->with('success', "Sinkronisasi hari libur nasional tahun {$year} berhasil, sesuaikan hari libur nasional.");
    }

    public function update(Request $request, WorkCalendar $workCalendar): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:full_day,half_day,holiday'],
            'name' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $workCalendar->update([
            'type' => $validated['type'],
            'name' => $validated['name'] ?? null,
            'note' => $validated['note'] ?? null,
            'is_manual_override' => true,
        ]);

        ActivityLogService::log(
            auth()->user(),
            'update',
            "Memperbarui kalender kerja tanggal {$workCalendar->date->format('d M Y')}",
            $workCalendar
        );

        return back()->with('success', 'Kalender '.$workCalendar->date->format('d M Y').' berhasil diperbarui.');
    }
}
