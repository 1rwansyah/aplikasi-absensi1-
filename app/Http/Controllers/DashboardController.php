<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->employee !== null && ! $user->isAdmin()) {
            return redirect()->route('attendance.index');
        }

        // Statistics
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('employment_status', 'active')->count();
        $today = Carbon::today();

        // Hadir = type regular dengan status on_time / late / late_out / early_out
        $presentToday = Attendance::whereDate('date', $today)
            ->where('type', 'regular')
            ->whereIn('status', ['on_time', 'late', 'late_out', 'early_out'])
            ->count();

        $lateToday = Attendance::whereDate('date', $today)
            ->where('status', 'late')
            ->count();

        // Tidak hadir = karyawan aktif - yang sudah hadir / izin-sakit disetujui / menunggu verifikasi
        $sudahAbsen = Attendance::whereDate('date', $today)
            ->excusedForAttendance()
            ->distinct('user_id')
            ->count('user_id');
        $absentToday = max($activeEmployees - $sudahAbsen, 0);

        // Cuti = izin + sakit yang sudah disetujui HR
        $leaveToday = Attendance::whereDate('date', $today)
            ->approvedLeave()
            ->count();

        $totalAttendanceToday = $sudahAbsen;

        // Weekly attendance data for chart
        $weeklyData = [];
        $weeklyLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $weeklyLabels[] = $date->translatedFormat('l');
            $weeklyData[] = Attendance::whereDate('date', $date)
                ->where('type', 'regular')
                ->whereIn('status', ['on_time', 'late', 'late_out', 'early_out'])
                ->count();
        }

        // Monthly attendance data for chart
        $monthlyData = [];
        $monthlyLabels = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::today()->subMonths($i);
            $monthlyLabels[] = $date->translatedFormat('F');
            $monthlyData[] = Attendance::whereYear('date', $date->year)
                ->whereMonth('date', $date->month)
                ->where('type', 'regular')
                ->whereIn('status', ['on_time', 'late', 'late_out', 'early_out'])
                ->count();
        }

        // Attendance status distribution (keys harus cocok dengan blade)
        $statusDistribution = [
            'present' => $presentToday,
            'late' => $lateToday,
            'absent' => $absentToday,
            'leave' => $leaveToday,
        ];

        // Recent attendance
        $recentAttendance = Attendance::with(['user.employee'])
            ->orderByDesc('date')
            ->orderByDesc('clock_in_time')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'totalEmployees',
            'activeEmployees',
            'totalAttendanceToday',
            'presentToday',
            'lateToday',
            'absentToday',
            'leaveToday',
            'weeklyLabels',
            'weeklyData',
            'monthlyLabels',
            'monthlyData',
            'statusDistribution',
            'recentAttendance'
        ));
    }
}
