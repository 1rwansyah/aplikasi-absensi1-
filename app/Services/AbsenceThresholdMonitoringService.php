<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Enums\AttendanceType;
use App\Enums\VerificationStatus;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AbsenceThresholdMonitoringService
{
    public const THRESHOLD = 4;

    /**
     * @return array{
     *     rows: list<array{
     *         user_id: int,
     *         employee_id: int|null,
     *         name: string,
     *         employee_code: string,
     *         alpha_count: int,
     *         permission_count: int,
     *         alpha_dates: list<string>,
     *         permission_dates: list<string>
     *     }>,
     *     summary: array{total: int, alpha_threshold: int, permission_threshold: int}
     * }
     */
    public function forMonth(int $month, int $year): array
    {
        $activeEmployees = Employee::query()
            ->where('employment_status', 'active')
            ->whereNotNull('user_id')
            ->get(['id', 'user_id', 'name', 'employee_code'])
            ->keyBy('user_id');

        if ($activeEmployees->isEmpty()) {
            return [
                'rows' => [],
                'summary' => [
                    'total' => 0,
                    'alpha_threshold' => 0,
                    'permission_threshold' => 0,
                ],
            ];
        }

        $userIds = $activeEmployees->keys()->all();

        $alphaDatesByUser = $this->alphaDatesByUser($userIds, $month, $year);
        $permissionDatesByUser = $this->permissionDatesByUser($userIds, $month, $year);

        $rows = [];

        foreach ($activeEmployees as $userId => $employee) {
            $alphaDates = $alphaDatesByUser->get($userId, collect())->values()->all();
            $permissionDates = $permissionDatesByUser->get($userId, collect())->values()->all();
            $alphaCount = count($alphaDates);
            $permissionCount = count($permissionDates);

            if ($alphaCount < self::THRESHOLD && $permissionCount < self::THRESHOLD) {
                continue;
            }

            $rows[] = [
                'user_id' => (int) $userId,
                'employee_id' => $employee->id,
                'name' => $employee->name,
                'employee_code' => $employee->employee_code,
                'alpha_count' => $alphaCount,
                'permission_count' => $permissionCount,
                'alpha_dates' => $alphaDates,
                'permission_dates' => $permissionDates,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $scoreA = max($a['alpha_count'], $a['permission_count']);
            $scoreB = max($b['alpha_count'], $b['permission_count']);

            return [$scoreB, $b['alpha_count'] + $b['permission_count'], $a['name']]
                <=> [$scoreA, $a['alpha_count'] + $a['permission_count'], $b['name']];
        });

        return [
            'rows' => $rows,
            'summary' => [
                'total' => count($rows),
                'alpha_threshold' => count(array_filter($rows, fn (array $row) => $row['alpha_count'] >= self::THRESHOLD)),
                'permission_threshold' => count(array_filter($rows, fn (array $row) => $row['permission_count'] >= self::THRESHOLD)),
            ],
        ];
    }

    public function thresholdCountForMonth(?Carbon $date = null): int
    {
        $date ??= Carbon::now();

        return $this->forMonth((int) $date->month, (int) $date->year)['summary']['total'];
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<int, string>>
     */
    private function alphaDatesByUser(array $userIds, int $month, int $year): Collection
    {
        return Attendance::query()
            ->whereIn('user_id', $userIds)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('status', AttendanceStatus::Alpha)
            ->orderBy('date')
            ->get(['user_id', 'date'])
            ->groupBy('user_id')
            ->map(fn (Collection $items) => $items
                ->map(fn (Attendance $row) => $row->date->toDateString())
                ->unique()
                ->values());
    }

    /**
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<int, string>>
     */
    private function permissionDatesByUser(array $userIds, int $month, int $year): Collection
    {
        return Attendance::query()
            ->whereIn('user_id', $userIds)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->where('type', AttendanceType::Permission)
            ->where('verification_status', VerificationStatus::Done)
            ->orderBy('date')
            ->get(['user_id', 'date'])
            ->groupBy('user_id')
            ->map(fn (Collection $items) => $items
                ->map(fn (Attendance $row) => $row->date->toDateString())
                ->unique()
                ->values());
    }
}
