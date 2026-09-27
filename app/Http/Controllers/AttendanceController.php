<?php



namespace App\Http\Controllers;



use App\Enums\AttendanceStatus;

use App\Enums\AttendanceType;

use App\Http\Requests\Attendance\AttendanceHistoryRequest;

use App\Http\Requests\Attendance\ClockInRequest;

use App\Http\Requests\Attendance\ClockOutRequest;

use App\Http\Requests\Attendance\SubmitLeaveRequest;

use App\Services\ActionResult;

use App\Services\AttendanceService;

use App\Services\AttendanceSettingsService;

use App\Services\EmployeeScheduleService;

use App\Services\WhatsappService;

use App\Services\WorkCalendarService;

use App\Support\AppTime;

use App\Support\RichTextSanitizer;

use Carbon\Carbon;

use Illuminate\Http\JsonResponse;

use Illuminate\Http\RedirectResponse;

use Illuminate\Http\Request;

use Illuminate\View\View;



class AttendanceController extends Controller

{

    public function __construct(

        private readonly AttendanceService $attendanceService,

        private readonly AttendanceSettingsService $settingsService,

        private readonly WorkCalendarService $workCalendar,

        private readonly EmployeeScheduleService $employeeSchedules,

    ) {}



    public function index(): View

    {

        $user = auth()->user();

        $settings = $this->settingsService->current();



        $attendance = $this->attendanceService->todayFor($user);

        $clockOut = $this->attendanceService->clockOutContextFor($user);

        $pendingClockOut = $clockOut['attendance'];



        $employee = $user->employee;



        $todaySchedule = null;

        $isOffDay = false;



        if ($employee) {

            $todaySchedule = $this->employeeSchedules->getTodaySchedule($employee);

            $isOffDay = $todaySchedule->is_off;

        }



        return view('attendance.index', [

            'attendance' => $attendance,

            'pendingClockOut' => $pendingClockOut,

            'settings' => $settings,

            'history' => $this->attendanceService->historyFor($user),

            'canClockOutNow' => $clockOut['canClockOutNow'],

            'clockOutOpensAt' => $clockOut['clockOutOpensAt'],

            'clockOutWindow' => $clockOut['clockOutWindow'],

            'todaySchedule' => $todaySchedule,

            'isOffDay' => $isOffDay,

            'hasFaceRegistered' => $user->hasFaceRegistered(),

            'needsFaceDescriptorSync' => $employee

                && $employee->hasProfilePhoto()

                && ! $employee->hasFaceDescriptor(),

            'profilePhotoUrl' => $employee?->profilePhotoUrl(),

            'faceMatchThreshold' => config('face.match_threshold', 0.5),

            'faceMinMatchPercent' => config('face.min_match_percent', 74),

            'holidayLabel' => $this->workCalendar->holidayLabel(),

            'halfDayNotice' => $this->workCalendar->halfDayNotice(),

        ]);

    }



    public function history(AttendanceHistoryRequest $request): View
    {
        $user = $request->user();
        $defaultMonth = AppTime::now()->format('Y-m');
        $selectedDate = $request->selectedDate();
        $selectedMonth = $selectedDate ? null : $request->month();
        $selectedType = $request->type();
        $selectedStatus = $request->status();

        return view('attendance.history', [
            'records' => $this->attendanceService->paginatedHistoryFor(
                $user,
                $selectedMonth,
                $selectedType,
                $selectedStatus,
                date: $selectedDate,
            ),
            'statistics' => $this->attendanceService->monthlyStatisticsFor(
                $user,
                $selectedMonth,
                $selectedType,
                $selectedStatus,
                $selectedDate,
            ),
            'filters' => [
                'date' => $selectedDate,
                'month' => $selectedMonth,
                'type' => $selectedType?->value,
                'status' => $selectedStatus?->value,
                'has_month' => $selectedMonth !== $defaultMonth,
            ],
            'types' => AttendanceType::cases(),
            'statuses' => AttendanceStatus::cases(),
        ]);
    }



    public function clockIn(ClockInRequest $request): RedirectResponse|JsonResponse

    {

        $result = $this->attendanceService->clockIn(

            $request->user(),

            $request->validated('clock_in_report'),

            $request->faceDescriptor(),

            $request->facesDetected(),

            $request->validated('verification_photo'),

            (float) $request->validated('latitude'),

            (float) $request->validated('longitude'),

            $request->attendanceLocation(),

            $request->validated('accuracy') !== null ? (float) $request->validated('accuracy') : null,

            $request->validated('device_type'),

            $request->clientTime()

        );



        if ($result->success) {

            $attendance = $this->attendanceService->todayFor($request->user());

            $clockInTime = Carbon::parse($attendance->clock_in_time)->format('H:i');



            $caption =

                "🟢 ABSEN MASUK\n\n"

                ."Nama: {$attendance->user->name}\n"

                                .'Tanggal: '.$attendance->date->translatedFormat('l, d F Y')."\n"



                ."Jam: {$clockInTime}\n\n"

                ."📝 Rencana Kerja:\n"

                .$this->reportToWhatsapp($attendance->clock_in_report);



            app(WhatsappService::class)->sendGroupImage(

                $attendance->clockInVerificationPhotoUrl(),

                $caption

            );

        }



        return $this->respondWithResult($request, $result);

    }



    public function clockOut(ClockOutRequest $request): RedirectResponse|JsonResponse

    {

        $result = $this->attendanceService->clockOut(

            $request->user(),

            $request->validated('clock_out_report'),

            $request->faceDescriptor(),

            $request->facesDetected(),

            $request->validated('verification_photo'),

            (float) $request->validated('latitude'),

            (float) $request->validated('longitude'),

            $request->attendanceLocation(),

            $request->validated('accuracy') !== null ? (float) $request->validated('accuracy') : null,

            $request->validated('device_type'),

            $request->clientTime()

        );



        if ($result->success) {

            $attendance = $this->attendanceService->todayFor($request->user());

            $clockOutTime = Carbon::parse($attendance->clock_out_time)->format('H:i');



            $caption =

                "🔴 ABSEN PULANG\n\n"

                ."Nama: {$attendance->user->name}\n"

                .'Tanggal: '.$attendance->date->translatedFormat('l, d F Y')."\n"

                ."Jam: {$clockOutTime}\n\n"

                ."📝 Laporan Kerja:\n"

                .$this->reportToWhatsapp($attendance->clock_out_report);



            app(WhatsappService::class)->sendGroupImage(

                $attendance->clockOutVerificationPhotoUrl(),

                $caption

            );

        }



        return $this->respondWithResult($request, $result);

    }



    public function submitLeave(SubmitLeaveRequest $request): RedirectResponse

    {

        $result = $this->attendanceService->submitLeave(

            $request->user(),

            $request->attendanceType(),

            $request->validated('leave_note'),

            $request->file('doctor_note'),

            (float) $request->validated('latitude'),

            (float) $request->validated('longitude'),

            $request->validated('accuracy') !== null ? (float) $request->validated('accuracy') : null,

        );



        return $this->redirectWithResult($result);

    }



    public function serverTime(): JsonResponse

    {

        return response()->json([

            'timestamp' => AppTime::now()->timestamp,

            'time' => AppTime::now()->format('H:i:s'),

            'date' => AppTime::now()->format('Y-m-d'),

            'timezone' => AppTime::timezone(),

        ]);

    }



    private function redirectWithResult(ActionResult $result): RedirectResponse

    {

        $key = $result->success ? 'success' : 'error';



        return back()->with($key, $result->message);

    }



    private function respondWithResult(Request $request, ActionResult $result): RedirectResponse|JsonResponse

    {

        if ($request->expectsJson() || $request->wantsJson()) {

            return response()->json([

                'success' => $result->success,

                'message' => $result->message,

            ], $result->success ? 200 : 422);

        }



        return $this->redirectWithResult($result);

    }



    private function reportToWhatsapp(?string $html): string

    {

        return RichTextSanitizer::toPlainText($html) ?? '-';

    }

}

