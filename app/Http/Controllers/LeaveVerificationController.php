<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Notifications\LeaveRejectedNotification;
use App\Services\ActivityLogService;
use App\Services\WhatsappService;
use App\Support\AppTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LeaveVerificationController extends Controller
{
    public function __construct(
        private readonly WhatsappService $whatsappService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Attendance::class);

        $status = $request->input('status');
        $hasExplicitDate = $request->filled('date');
        $skipDateFilter = $status === VerificationStatus::Pending->value && ! $hasExplicitDate;

        $date = $hasExplicitDate
            ? Carbon::parse($request->input('date'), AppTime::timezone())->startOfDay()
            : AppTime::today()->startOfDay();

        $query = Attendance::with(['user', 'verifiedBy'])
            ->whereIn('type', [AttendanceType::Sick->value, AttendanceType::Permission->value])
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if (! $skipDateFilter) {
            $query->whereDate('date', $date);
        }

        if ($request->filled('status')) {
            $query->where('verification_status', $status);
        }

        $search = $request->input('search');

        if (filled($search)) {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('employee_code', 'like', "%{$search}%");
                    });
            });
        }

        $leaves = $query->paginate(20)->withQueryString();

        return view('leave-verification.index', [
            'leaves' => $leaves,
            'date' => $skipDateFilter ? null : $date,
            'search' => $search,
            'filters' => [
                'status' => $status,
                'date' => $skipDateFilter ? '' : $date->toDateString(),
                'is_today' => ! $skipDateFilter && $date->isSameDay(AppTime::today()),
            ],
        ]);
    }

    public function verify(Request $request, Attendance $attendance): RedirectResponse
    {
        Gate::authorize('update', $attendance);

        if (! $attendance->isLeave()) {
            return redirect()->back()->with('error', 'Hanya pengajuan izin/sakit yang dapat diverifikasi.');
        }

        if (! $attendance->verification_status->isPending()) {
            return redirect()->back()->with('error', 'Pengajuan ini sudah diverifikasi sebelumnya.');
        }

        $request->validate([
            'verification_status' => ['required', 'in:done,no_done'],
            'rejection_reason' => [
                'required_if:verification_status,no_done',
                'nullable',
                'string',
                'min:10',
                'max:2000',
            ],
        ], [
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.min' => 'Alasan penolakan minimal 10 karakter.',
        ]);

        $status = $request->input('verification_status');
        $verificationStatus = $status === 'done' ? VerificationStatus::Done : VerificationStatus::NoDone;
        $rejectionReason = $verificationStatus === VerificationStatus::NoDone
            ? trim((string) $request->input('rejection_reason'))
            : null;

        $attendance->update([
            'verification_status' => $verificationStatus,
            'verified_by' => $request->user()->id,
            'verified_at' => now(),
            'rejection_reason' => $rejectionReason,
        ]);

        $attendance->loadMissing(['user', 'verifiedBy']);

        if ($verificationStatus === VerificationStatus::Done) {
            $this->whatsappService->sendLeaveNotification($attendance);
        }

        if ($verificationStatus === VerificationStatus::NoDone && $attendance->user?->email) {
            $attendance->user->notify(new LeaveRejectedNotification($attendance));
        }

        $actionLabel = $verificationStatus === VerificationStatus::Done
            ? 'disetujui'
            : ($attendance->type === AttendanceType::Sick ? 'ditolak' : 'ditolak (alfa)');
        ActivityLogService::log(
            $request->user(),
            'update',
            "Verifikasi pengajuan {$attendance->type->label()} {$attendance->user->name} pada {$attendance->date->format('d/m/Y')}: {$actionLabel}",
            $attendance,
        );

        $message = $verificationStatus === VerificationStatus::Done
            ? 'Pengajuan berhasil disetujui dan notifikasi dikirim ke WhatsApp.'
            : ($attendance->type === AttendanceType::Sick
                ? 'Pengajuan sakit ditolak. Email pemberitahuan dikirim ke karyawan.'
                : 'Pengajuan berhasil ditolak. Email pemberitahuan dikirim ke karyawan.');

        return redirect()->back()->with('success', $message);
    }

    public function mySubmissions(Request $request): View
    {
        $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'status' => ['nullable', 'in:pending,done,no_done'],
        ]);

        // Empty month (or first visit) = semua bulan, sama seperti opsi "Semua Bulan" di riwayat.
        if (! $request->has('month') && ! $request->has('status')) {
            $selectedMonth = null;
        } else {
            $selectedMonth = filled($request->input('month')) ? $request->input('month') : null;
        }

        $baseQuery = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('type', [AttendanceType::Sick->value, AttendanceType::Permission->value]);

        if ($selectedMonth) {
            [$year, $monthNumber] = array_map('intval', explode('-', $selectedMonth));
            $baseQuery->whereYear('date', $year)->whereMonth('date', $monthNumber);
        }

        $counts = (clone $baseQuery)
            ->selectRaw('verification_status, count(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        $summary = [
            'total' => (int) $counts->sum(),
            'pending' => (int) ($counts->get(VerificationStatus::Pending->value, 0)),
            'done' => (int) ($counts->get(VerificationStatus::Done->value, 0)),
            'no_done' => (int) ($counts->get(VerificationStatus::NoDone->value, 0)),
        ];

        $query = (clone $baseQuery)
            ->with('verifiedBy')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('verification_status', $request->input('status'));
        }

        $submissions = $query->paginate(20)->withQueryString();

        $monthOptions = collect();
        $cursor = AppTime::now()->startOfMonth();
        for ($i = 0; $i < 24; $i++) {
            $monthOptions->push([
                'value' => $cursor->format('Y-m'),
                'label' => $cursor->translatedFormat('F Y'),
            ]);
            $cursor->subMonthNoOverflow();
        }

        return view('leave-verification.my-submissions', [
            'submissions' => $submissions,
            'summary' => $summary,
            'monthOptions' => $monthOptions,
            'filters' => [
                'month' => $selectedMonth,
                'status' => $request->input('status'),
                'has_month' => $selectedMonth !== null,
            ],
        ]);
    }
}
