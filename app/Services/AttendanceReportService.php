<?php

namespace App\Services;

use App\DTOs\AttendanceReportRow;
use App\Enums\AttendanceReportStatus;
use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    public function dailyReport(Carbon $date, ?string $filter = null, ?string $search = null): LengthAwarePaginator
    {
        $isToday = $date->isToday();

        $employees = User::query()
            ->select('users.*')
            ->where('users.role', 'employee')
            ->whereHas(
                'employee',
                fn ($query) => $query->where('employment_status', 'active')
            )
            ->leftJoin('attendances', function ($join) use ($date) {
                $join->on('attendances.user_id', '=', 'users.id')
                    ->whereDate('attendances.date', $date);
            })
            ->when($search, fn ($q) => $q->where('users.name', 'like', "%{$search}%"))
            ->when($filter === 'alfa', fn ($q) => $q->where(function ($q) {
                $q->whereNull('attendances.id')
                    ->orWhere('attendances.status', AttendanceStatus::Alpha->value)
                    ->orWhere(function ($q) {
                        $q->whereIn('attendances.type', [
                            AttendanceType::Sick->value,
                            AttendanceType::Permission->value,
                        ])->where('attendances.verification_status', VerificationStatus::NoDone->value);
                    });
            }))
            ->when($filter === 'sakit', fn ($q) => $q->where('attendances.type', AttendanceType::Sick->value)
                ->where('attendances.verification_status', '!=', VerificationStatus::NoDone->value))
            ->when($filter === 'izin', fn ($q) => $q->where('attendances.type', AttendanceType::Permission->value)
                ->where('attendances.verification_status', '!=', VerificationStatus::NoDone->value))
            ->when($filter === 'telat', fn ($q) => $q
                ->whereNotNull('attendances.id')
                ->where('attendances.type', AttendanceType::Regular->value)
                ->where('attendances.status', AttendanceStatus::Late->value))
            ->when($filter === 'hadir', fn ($q) => $q
                ->whereNotNull('attendances.id')
                ->where('attendances.type', AttendanceType::Regular->value)
                ->where(function ($q) {
                    $q->whereIn('attendances.status', [
                        AttendanceStatus::OnTime->value,
                        AttendanceStatus::LateOut->value,
                    ])->orWhere(function ($q) {
                        $q->whereNull('attendances.clock_out_time')
                            ->where('attendances.status', '!=', AttendanceStatus::Late->value);
                    });
                }))
            ->when(
                $isToday,
                fn ($q) => $q
                    ->orderByRaw('attendances.clock_in_time IS NULL')
                    ->orderByDesc('attendances.clock_in_time'),
                fn ($q) => $q->orderBy('users.name')
            )
            ->paginate(25)
            ->withQueryString();

        $attendances = Attendance::query()
            ->whereDate('date', $date)
            ->whereIn('user_id', $employees->getCollection()->pluck('id'))
            ->latest('clock_in_time')
            ->get()
            ->keyBy('user_id');

        $employees->setCollection(
            $employees->getCollection()->map(
                fn (User $user) => new AttendanceReportRow($user, $attendances->get($user->id))
            )
        );

        return $employees;
    }

    public function dailyReportAll(Carbon $date): Collection
    {
        $employees = User::query()
            ->where('role', 'employee')
            ->whereHas(
                'employee',
                fn ($query) => $query->where('employment_status', 'active')
            )
            ->orderBy('name')
            ->get();

        $attendances = Attendance::query()
            ->whereDate('date', $date)
            ->whereIn('user_id', $employees->pluck('id'))
            ->get()
            ->keyBy('user_id');

        return $employees->map(
            fn (User $user) => new AttendanceReportRow($user, $attendances->get($user->id))
        )->values();
    }

    public function summaryFor(Carbon $date): array
    {
        $rows = $this->dailyReportAll($date);

        return $this->buildSummary($rows);
    }

    public function holidayReport(Carbon $date, ?string $filter = null, ?string $search = null): Collection
    {
        $attendances = Attendance::query()
            ->whereDate('date', $date)
            ->with('user')
            ->when($search, fn ($q) => $q->whereHas(
                'user',
                fn ($u) => $u->where('name', 'like', "%{$search}%")
            ))
            ->get();

        $rows = $attendances
            ->filter(fn ($a) => $a->user !== null)
            ->map(fn ($a) => new AttendanceReportRow($a->user, $a))
            ->sortBy(fn ($r) => $r->user->name)
            ->values();

        if ($filter) {
            $rows = $rows->filter(
                fn (AttendanceReportRow $row) => $row->status()->filterKey() === $filter
            )->values();
        }

        return $rows;
    }

    public function holidaySummaryFor(Carbon $date): array
    {
        $rows = $this->holidayReport($date);

        return $this->buildSummary($rows);
    }

    /**
     * Paginated attendance records for admin history (multi-user), like employee history.
     *
     * @return LengthAwarePaginator<int, AttendanceReportRow>
     */
    public function historyReport(
        ?string $month = null,
        ?AttendanceType $type = null,
        ?AttendanceStatus $status = null,
        ?string $search = null,
        int $perPage = 15,
        ?string $date = null,
    ): LengthAwarePaginator {
        $paginator = $this->historyQuery($month, $type, $status, $search, $date)
            ->paginate($perPage)
            ->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (Attendance $attendance) => new AttendanceReportRow($attendance->user, $attendance)
            )
        );

        return $paginator;
    }

    /**
     * @return array{hadir: int, telat: int, izin: int, sakit: int, alpha: int}
     */
    public function historyStatistics(
        ?string $month = null,
        ?AttendanceType $type = null,
        ?AttendanceStatus $status = null,
        ?string $search = null,
        ?string $date = null,
    ): array {
        $attendances = $this->historyQuery($month, $type, $status, $search, $date)->get();

        $statistics = [
            'hadir' => 0,
            'telat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpha' => 0,
        ];

        foreach ($attendances as $attendance) {
            if ($attendance->isLeave()) {
                if ($attendance->isRejectedLeave()) {
                    match ($attendance->type) {
                        AttendanceType::Sick => $statistics['sakit']++,
                        AttendanceType::Permission => $statistics['alpha']++,
                        default => null,
                    };

                    continue;
                }

                if ($attendance->isPendingLeave()) {
                    continue;
                }

                match ($attendance->status) {
                    AttendanceStatus::Permission => $statistics['izin']++,
                    AttendanceStatus::Sick => $statistics['sakit']++,
                    default => null,
                };

                continue;
            }

            match ($attendance->status) {
                AttendanceStatus::OnTime, AttendanceStatus::LateOut => $statistics['hadir']++,
                AttendanceStatus::Late, AttendanceStatus::EarlyOut => $statistics['telat']++,
                AttendanceStatus::Permission => $statistics['izin']++,
                AttendanceStatus::Sick => $statistics['sakit']++,
                AttendanceStatus::Alpha => $statistics['alpha']++,
                default => null,
            };
        }

        return $statistics;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<Attendance>
     */
    private function historyQuery(
        ?string $month = null,
        ?AttendanceType $type = null,
        ?AttendanceStatus $status = null,
        ?string $search = null,
        ?string $date = null,
    ) {
        $search = filled($search) ? trim($search) : null;
        $date = filled($date) ? $date : null;

        return Attendance::query()
            ->with(['user.employee', 'verifiedBy', 'workSchedule'])
            ->whereHas('user', fn ($query) => $query->where('role', 'employee'))
            ->when($date, fn ($query) => $query->whereDate('date', $date))
            ->when(! $date && $month, function ($query) use ($month) {
                [$year, $monthNumber] = array_map('intval', explode('-', $month));
                $query->whereYear('date', $year)->whereMonth('date', $monthNumber);
            })
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas(
                            'user.employee',
                            fn ($employee) => $employee->where('name', 'like', "%{$search}%")
                        );
                });
            })
            ->orderByDesc('date')
            ->orderByDesc('id');
    }

    private function buildSummary(Collection $rows): array
    {
        return [
            'total' => $rows->count(),
            'hadir' => $rows->reject(
                fn ($r) => in_array($r->status(), [
                    AttendanceReportStatus::Alpha,
                    AttendanceReportStatus::Sick,
                    AttendanceReportStatus::Permission,
                ], true)
            )->count(),
            'telat' => $rows->filter(fn ($r) => $r->status() === AttendanceReportStatus::Late)->count(),
            'alfa' => $rows->filter(fn ($r) => $r->status() === AttendanceReportStatus::Alpha)->count(),
            'sakit' => $rows->filter(fn ($r) => $r->status() === AttendanceReportStatus::Sick)->count(),
            'izin' => $rows->filter(fn ($r) => $r->status() === AttendanceReportStatus::Permission)->count(),
        ];
    }
}
