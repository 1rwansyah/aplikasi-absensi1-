<?php

namespace App\Http\Controllers;

use App\Exports\AbsenceThresholdExport;
use App\Services\AbsenceThresholdMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AbsenceThresholdController extends Controller
{
    public function __construct(
        private readonly AbsenceThresholdMonitoringService $monitoring,
    ) {}

    public function index(Request $request): View
    {
        $period = $this->resolvePeriod($request);
        $result = $this->monitoring->forMonth($period['month'], $period['year']);

        $months = collect(range(1, 12))->mapWithKeys(
            fn (int $m) => [$m => Carbon::create(null, $m, 1)->translatedFormat('F')]
        );

        $years = range((int) Carbon::now()->year - 2, (int) Carbon::now()->year + 1);

        return view('admin.absence-threshold.index', [
            'rows' => $result['rows'],
            'summary' => $result['summary'],
            'month' => $period['month'],
            'year' => $period['year'],
            'months' => $months,
            'years' => $years,
            'periodLabel' => $period['label'],
            'threshold' => AbsenceThresholdMonitoringService::THRESHOLD,
        ]);
    }

    public function exportExcel(Request $request): BinaryFileResponse|StreamedResponse
    {
        $period = $this->resolvePeriod($request);
        $result = $this->monitoring->forMonth($period['month'], $period['year']);
        $fileName = sprintf(
            'Rekap_Alfa_Izin_%s.xlsx',
            str_replace(' ', '_', $period['label'])
        );

        return Excel::download(
            new AbsenceThresholdExport(
                $result['rows'],
                $result['summary'],
                $period['label'],
            ),
            $fileName
        );
    }

    /**
     * @return array{month: int, year: int, label: string}
     */
    private function resolvePeriod(Request $request): array
    {
        $now = Carbon::now();
        $month = max(1, min(12, (int) $request->input('month', $now->month)));
        $year = max(2020, min((int) $now->year + 1, (int) $request->input('year', $now->year)));

        return [
            'month' => $month,
            'year' => $year,
            'label' => Carbon::create($year, $month, 1)->translatedFormat('F Y'),
        ];
    }
}
