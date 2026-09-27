<?php

namespace App\Http\Controllers\Settings;

use App\Exceptions\SecurityScheduleImportException;
use App\Http\Controllers\Controller;
use App\Imports\SecurityScheduleImport;
use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class SecurityScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $weekStart = $request->filled('week_start')
            ? Carbon::parse($request->week_start)->startOfWeek()
            : now()->startOfWeek();

        $days = collect(range(0, 6))
            ->map(fn ($day) => $weekStart->copy()->addDays($day));

        $employees = Employee::query()
            ->where('staff', 'Security')
            ->orderBy('name')
            ->get();

        $assignments = EmployeeSchedule::with('workSchedule')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('work_date', [
                $days->first()->toDateString(),
                $days->last()->toDateString(),
            ])
            ->get()
            ->keyBy(fn ($item) => $item->employee_id.'_'.$item->work_date->format('Y-m-d'));

        $workSchedules = WorkSchedule::query()
            ->whereIn('code', ['security', 'off'])
            ->orderByRaw("
        CASE
            WHEN code = 'security' THEN 1
            WHEN code = 'off' THEN 2
            ELSE 3
        END
    ")
            ->get();

        return view('settings.security-schedules', compact(
            'weekStart',
            'days',
            'employees',
            'assignments',
            'workSchedules',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'schedules' => ['required', 'array'],
            'schedules.*' => ['array'],
            'schedules.*.*' => [
                'required',
                'string',
                Rule::in(['lobby', 'gate', 'off']),
            ],
        ]);

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek();

        $days = collect(range(0, 6))
            ->map(fn ($day) => $weekStart->copy()->addDays($day)->toDateString());

        $securityEmployeeIds = Employee::query()
            ->where('staff', 'Security')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        $workSchedules = WorkSchedule::query()
            ->whereIn('code', ['security', 'off'])
            ->get()
            ->keyBy('code');

        foreach ($validated['schedules'] as $employeeId => $dateSchedules) {
            if (! in_array((string) $employeeId, $securityEmployeeIds, true)) {
                abort(422, 'Karyawan security tidak valid.');
            }

            foreach ($dateSchedules as $date => $value) {
                if (! $days->contains($date)) {
                    abort(422, 'Tanggal jadwal tidak valid.');
                }

                $scheduleCode = $value === 'off' ? 'off' : 'security';

                $postLocation = match ($value) {
                    'lobby' => 'lobby',
                    'gate' => 'gate',
                    default => null,
                };

                $workSchedule = $workSchedules->get($scheduleCode);

                if (! $workSchedule) {
                    abort(422, 'Jadwal tidak valid.');
                }

                EmployeeSchedule::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'work_date' => $date,
                    ],
                    [
                        'work_schedule_id' => $workSchedule->id,
                        'post_location' => $postLocation,
                    ]
                );
            }
        }

        return redirect()
            ->route('settings.security-schedules.index', [
                'week_start' => $weekStart->toDateString(),
            ])
            ->with('success', 'Jadwal security berhasil disimpan.');
    }

    public function import(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek();

        try {
            Excel::import(new SecurityScheduleImport($weekStart), $validated['file']);
        } catch (SecurityScheduleImportException $exception) {
            return redirect()
                ->route('settings.security-schedules.index', [
                    'week_start' => $weekStart->toDateString(),
                ])
                ->withErrors(['file' => $exception->errors]);
        }

        return redirect()
            ->route('settings.security-schedules.index', [
                'week_start' => $weekStart->toDateString(),
            ])
            ->with('success', 'Jadwal security berhasil diimport dari Excel.');
    }
}
