<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Exceptions\AdminAttendanceLockedException;
use App\Http\Requests\Admin\StoreAttendanceRequest;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Setting;
use App\Models\User;
use App\Services\AdminAttendanceService;
use App\Services\AttendanceReportService;
use App\Services\AttendanceVerificationPhotoService;
use App\Services\WhatsappService;
use App\Services\WorkCalendarService;
use App\Support\AppTime;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Requests\Admin\SendWhatsappAttendanceReportRequest;

class AdminAttendanceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AttendanceReportService $reportService,
        private readonly AttendanceVerificationPhotoService $verificationPhotos,
        private readonly WorkCalendarService $workCalendar,
        private readonly AdminAttendanceService $adminAttendance,
    ) {}

    public function index(Request $request): View
    {
        $view = $request->input('view') === 'history' ? 'history' : 'monitoring';
        $settings = Setting::current();
        $manualContext = $this->manualAttendanceFormContext();

        $validationErrors = session('errors');
        $openManualEditAttendanceId = $request->integer('edit') ?: null;
        if ($openManualEditAttendanceId === 0) {
            $openManualEditAttendanceId = null;
        }
        if ($openManualEditAttendanceId === null && $validationErrors?->any() && old('manual_reason') !== null && old('clock_in_date') !== null) {
            $openManualEditAttendanceId = Attendance::query()
                ->where('user_id', old('user_id'))
                ->whereDate('date', old('clock_in_date'))
                ->value('id');
        }

        $editAttendance = $openManualEditAttendanceId
            ? Attendance::query()->with('user.employee', 'workSchedule')->find($openManualEditAttendanceId)
            : null;

        $shared = [
            'view' => $view,
            'geofence' => [
                'enabled' => $settings->hasGeofence(),
                'officeLatitude' => $settings->office_latitude,
                'officeLongitude' => $settings->office_longitude,
                'radiusMeters' => (int) $settings->attendance_radius_meters,
                'radiusLabel' => $settings->formattedAttendanceRadius(),
            ],
            ...$manualContext,
            'openManualAttendanceModal' => $request->boolean('manual')
                || ($validationErrors && $validationErrors->any() && old('manual_reason') !== null && old('user_id') !== null && ! $openManualEditAttendanceId)
                || (session('error') !== null && old('clock_in_date') !== null && ! $openManualEditAttendanceId),
            'openManualEditAttendanceModal' => $openManualEditAttendanceId !== null,
            'editAttendance' => $editAttendance,
        ];

        if ($view === 'history') {
            return $this->historyIndex($request, $shared);
        }

        return $this->monitoringIndex($request, $shared);
    }

    /**
     * @param  array<string, mixed>  $shared
     */
    private function monitoringIndex(Request $request, array $shared): View
    {
        $date = Carbon::parse($request->input('date', AppTime::today()->toDateString()), AppTime::timezone());
        $filter = $request->input('status');
        $search = $request->input('search');

        $holidayEntry = $this->workCalendar->holidayEntry($date);
        $isHoliday = $holidayEntry !== null;

        if ($isHoliday) {
            $rows = $this->reportService->holidayReport($date, $filter, $search);
            $summary = $this->reportService->holidaySummaryFor($date);
        } else {
            $rows = $this->reportService->dailyReport($date, $filter, $search);
            $summary = $this->reportService->summaryFor($date);
        }

        return view('admin.attendance.index', [
            ...$shared,
            'date' => $date,
            'filter' => $filter,
            'search' => $search,
            'summary' => $summary,
            'rows' => $rows,
            'isHoliday' => $isHoliday,
            'holidayName' => $holidayEntry?->name ?: 'Hari Libur',
            'hasHolidayRecords' => $isHoliday && $rows->isNotEmpty(),
            'payrollLockedAttendanceIds' => $this->adminAttendance->lockedAttendanceIds(
                $rows->pluck('attendance')->filter()->values(),
            ),
            'historyRecords' => null,
            'historyStatistics' => null,
            'historyFilters' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $shared
     */
    private function historyIndex(Request $request, array $shared): View
    {
        $defaultMonth = AppTime::now()->format('Y-m');

        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'type' => ['nullable', Rule::enum(AttendanceType::class)],
            'status' => ['nullable', Rule::enum(AttendanceStatus::class)],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $date = filled($validated['date'] ?? null) ? $validated['date'] : null;

        if ($date) {
            $month = null;
        } elseif (! $request->hasAny(['date', 'month', 'type', 'status', 'search'])) {
            $month = $defaultMonth;
        } else {
            $month = filled($validated['month'] ?? null) ? $validated['month'] : null;
        }

        $typeValue = $validated['type'] ?? null;
        $type = $typeValue instanceof AttendanceType
            ? $typeValue
            : (filled($typeValue) ? AttendanceType::from($typeValue) : null);

        $statusValue = $validated['status'] ?? null;
        $status = $statusValue instanceof AttendanceStatus
            ? $statusValue
            : (filled($statusValue) ? AttendanceStatus::from($statusValue) : null);
        $search = filled($validated['search'] ?? null) ? trim($validated['search']) : null;

        $historyRecords = $this->reportService->historyReport($month, $type, $status, $search, 15, $date);
        $historyStatistics = $this->reportService->historyStatistics($month, $type, $status, $search, $date);

        return view('admin.attendance.index', [
            ...$shared,
            'date' => $date ? Carbon::parse($date, AppTime::timezone()) : AppTime::today(),
            'filter' => null,
            'search' => $search,
            'summary' => [
                'total' => 0,
                'hadir' => 0,
                'telat' => 0,
                'alfa' => 0,
                'sakit' => 0,
                'izin' => 0,
            ],
            'rows' => collect(),
            'isHoliday' => false,
            'holidayName' => null,
            'hasHolidayRecords' => false,
            'payrollLockedAttendanceIds' => $this->adminAttendance->lockedAttendanceIds(
                $historyRecords->getCollection()->map(fn ($row) => $row->attendance)->filter()->values(),
            ),
            'historyRecords' => $historyRecords,
            'historyStatistics' => $historyStatistics,
            'historyFilters' => [
                'date' => $date,
                'month' => $month,
                'type' => $type?->value,
                'status' => $status?->value,
                'search' => $search,
                'has_month' => $month !== null && $month !== $defaultMonth,
            ],
        ]);
    }

    /**
     * @return array{employees: Collection<int, Employee>, types: array<int, AttendanceType>, statuses: array<int, AttendanceStatus>}
     */
    private function manualAttendanceFormContext(): array
    {
        return [
            'employees' => Employee::query()
                ->where('employment_status', 'active')
                ->whereNotNull('user_id')
                ->with('user')
                ->orderBy('name')
                ->get(),
            'types' => AttendanceType::cases(),
            'statuses' => AttendanceStatus::cases(),
        ];
    }

    public function create(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $date = Carbon::parse(
            $request->input('date', AppTime::today()->toDateString()),
            AppTime::timezone(),
        );

        return redirect()->route('admin.attendance.index', [
            'date' => $date->toDateString(),
            'manual' => 1,
        ]);
    }

    public function schedulePreview(Request $request): JsonResponse
    {
        $this->authorize('create', Attendance::class);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date'],
            'attendance_id' => ['nullable', 'integer', 'exists:attendances,id'],
        ]);

        $user = User::query()->with('employee')->findOrFail($validated['user_id']);
        $date = Carbon::parse($validated['date'], AppTime::timezone())->startOfDay();

        $attendance = isset($validated['attendance_id'])
            ? Attendance::query()->find($validated['attendance_id'])
            : null;

        return response()->json(
            $this->adminAttendance->schedulePreview($user, $date, $attendance),
        );
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        try {
            $attendance = $this->adminAttendance->create(
                $request->attendanceAttributes(),
                $request->user(),
                $request->manualReason(),
            );
        } catch (AdminAttendanceLockedException $e) {
            return redirect()
                ->route('admin.attendance.index', [
                    'date' => $request->input('clock_in_date'),
                    'manual' => 1,
                ])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.attendance.index', ['date' => $attendance->date->toDateString()])
            ->with('success', 'Absensi manual berhasil ditambahkan.');
    }

    public function edit(Request $request, Attendance $attendance): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $attendance);

        $attendance->load('user.employee', 'workSchedule');

        if ($this->adminAttendance->isLockedByPaidPayroll($attendance)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Absensi tidak dapat diedit karena payroll periode '
                        .$attendance->date->translatedFormat('F Y').' sudah ditandai lunas.',
                ], 403);
            }

            return redirect()
                ->route('admin.attendance.index', ['date' => $attendance->date->toDateString()])
                ->with('error', 'Absensi tidak dapat diedit karena payroll periode '
                    .$attendance->date->translatedFormat('F Y').' sudah ditandai lunas.');
        }

        if ($request->wantsJson()) {
            return response()->json($this->adminAttendance->editFormData($attendance));
        }

        return redirect()->route('admin.attendance.index', [
            'date' => $attendance->date->toDateString(),
            'edit' => $attendance->id,
        ]);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        try {
            $this->adminAttendance->update(
                $attendance,
                $request->attendanceAttributes(),
                $request->user(),
                $request->manualReason(),
            );
        } catch (AdminAttendanceLockedException $e) {
            return redirect()
                ->route('admin.attendance.index', [
                    'date' => $request->input('clock_in_date', $attendance->date->toDateString()),
                    'edit' => $attendance->id,
                ])
                ->withInput()
                ->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.attendance.index', ['date' => $attendance->fresh()->date->toDateString()])
            ->with('success', 'Absensi berhasil diperbarui.');
    }

    public function verificationPhoto(Attendance $attendance, string $moment): StreamedResponse
    {
        $user = Auth::user();

        if (! $user instanceof User || ! $user->isAdmin()) {
            abort(403);
        }

        $path = match ($moment) {
            'clock-in' => $attendance->clock_in_verification_photo,
            'clock-out' => $attendance->clock_out_verification_photo,
            default => null,
        };

        return $this->verificationPhotos->stream($path);
    }

    public function sendWhatsappReport(
        SendWhatsappAttendanceReportRequest $request,
        WhatsappService $whatsappService,
    ): JsonResponse {
        $date = Carbon::parse($request->validated('date'), AppTime::timezone());
        $type = $request->validated('type');

        $sent = $whatsappService->sendAttendanceReport($date, $type);

        if (! $sent) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim rekap absensi ke WhatsApp. Periksa koneksi bot.',
            ], 502);
        }

        $typeLabel = $type === 'pulang' ? 'pulang' : 'masuk';

        return response()->json([
            'success' => true,
            'message' => "Rekap absensi {$typeLabel} tanggal {$date->translatedFormat('d F Y')} berhasil dikirim ke WhatsApp.",
        ]);
    }
}
