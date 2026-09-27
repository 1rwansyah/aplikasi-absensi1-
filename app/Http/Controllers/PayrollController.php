<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceType;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\EmployeeSchedule;
use App\Models\Payroll;
use App\Models\PayrollDetail;
use App\Models\PayrollDetailChange;
use App\Models\WorkCalendar;
use App\Services\ActivityLogService;
use App\Services\PayrollAttendanceDateBreakdown;
use App\Services\PayrollBrowsershotService;
use App\Services\PayrollChangeLogService;
use App\Services\PayrollSnapshotService;
use App\Services\WhatsappService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'period_month' => $request->period_month,
            'period_year' => $request->period_year,
            'status' => $request->status,
            'search' => $request->search,
        ];

        $baseQuery = fn () => Payroll::query()
            ->when($filters['period_month'], fn ($q) => $q->where('period_month', $filters['period_month']))
            ->when($filters['period_year'], fn ($q) => $q->where('period_year', $filters['period_year']))
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['search'], fn ($q) => $q->whereHas('employee', fn ($eq) => $eq->where('name', 'like', "%{$filters['search']}%")
                ->orWhere('employee_code', 'like', "%{$filters['search']}%")
            ));

        $summary = $baseQuery()->selectRaw('
            COUNT(*) as total_employees,
            SUM(net_salary) as total_net,
            SUM(total_allowance) as total_allowance,
            SUM(total_deduction) as total_deduction,
            SUM(gross_salary) as total_gross
        ')->first();

        $payrolls = $baseQuery()
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->orderBy('employees.id', 'asc')
            ->select('payrolls.*')
            ->with('employee')
            ->paginate(15)
            ->withQueryString();

        // Get unique years from existing payrolls for filter dropdown
        $availableYears = Payroll::selectRaw('DISTINCT period_year as year')
            ->orderBy('period_year', 'desc')
            ->pluck('year')
            ->toArray();

        return view('payrolls.index', compact('payrolls', 'summary', 'filters', 'availableYears'));
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'period_month' => ['nullable', 'integer', 'between:1,12'],
            'period_year' => ['nullable', 'integer', 'min:2020'],
            'status' => ['nullable', 'in:draft,paid'],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $filters = array_filter($validated, fn ($value) => $value !== null && $value !== '');

        $months = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $monthName = isset($filters['period_month']) ? ($months[$filters['period_month']] ?? $filters['period_month']) : 'All';
        $fileName = "Penggajian_{$monthName}.xlsx";

        return Excel::download(new \App\Exports\PayrollExport($filters), $fileName);
    }

    public function sendWaReport(Request $request)
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'regex:/^628\d{8,12}$/'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'min:2020'],
        ]);

        $sent = app(WhatsappService::class)->sendPayrollReport(
            $validated['phone'],
            $validated['period_month'],
            $validated['period_year']
        );

        if (! $sent) {
            return back()->with('error', 'Gagal mengirim laporan via WhatsApp.');
        }

        return back()->with('success', 'Laporan gaji berhasil dikirim via WhatsApp.');
    }

    public function show(Payroll $payroll)
    {
        $payroll->load('employee', 'details', 'systemSnapshot');

        $undoableChange = PayrollDetailChange::query()
            ->where('payroll_id', $payroll->id)
            ->undoable()
            ->orderByDesc('created_at')
            ->first();

        $hasSystemSnapshot = $payroll->systemSnapshot !== null;
        $hasManualActivity = PayrollDetailChange::query()
            ->where('payroll_id', $payroll->id)
            ->whereNull('superseded_at')
            ->exists();

        $attendanceDates = app(PayrollAttendanceDateBreakdown::class)->forPayroll($payroll);

        return view('payrolls.show', compact(
            'payroll',
            'undoableChange',
            'hasSystemSnapshot',
            'hasManualActivity',
            'attendanceDates',
        ));
    }

    public function downloadPdf(Payroll $payroll)
    {
        $pdfService = new PayrollBrowsershotService();

        return $pdfService->generate($payroll);
    }

    public function markAsPaid(Payroll $payroll)
    {
        $payroll->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        ActivityLogService::log(
            auth()->user(),
            'update',
            "Menandai pembayaran gaji lunas: {$payroll->employee?->name} periode {$payroll->period_month}/{$payroll->period_year}",
            $payroll
        );

        return redirect()
            ->route('payrolls.index', request()->only('period_month', 'period_year', 'status'))
            ->with('success', 'Pembayaran gaji '.$payroll->employee?->name.' berhasil ditandai lunas.');
    }

    public function addAdjustment(Request $request, Payroll $payroll)
    {
        if ($response = $this->ensurePayrollEditable($payroll)) {
            return $response;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:allowance,deduction'],
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($payroll, $validated) {
            $detail = PayrollDetail::create([
                'payroll_id' => $payroll->id,
                'name' => $validated['name'],
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'is_adjustment' => true,
            ]);

            PayrollChangeLogService::recordCreate($detail, auth()->user());
            $this->recalculatePayroll($payroll);
        });

        ActivityLogService::log(
            auth()->user(),
            'create',
            "Menambahkan adjustment gaji {$payroll->employee?->name}: {$validated['name']} Rp ".number_format($validated['amount'], 0, ',', '.'),
            $payroll
        );

        return redirect()
            ->route('payrolls.show', $payroll)
            ->with('success', 'Adjustment berhasil ditambahkan.');
    }

    public function updatePayrollDetail(Request $request, PayrollDetail $detail)
    {
        $payroll = $detail->payroll;

        if ($response = $this->ensurePayrollEditable($payroll)) {
            return $response;
        }

        if (! $detail->isEditable()) {
            return back()->with('error', 'Hanya item tunjangan atau potongan yang dapat diubah.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $oldName = $detail->name;
        $oldAmount = $detail->amount;

        $before = PayrollChangeLogService::snapshotDetail($detail);

        DB::transaction(function () use ($detail, $payroll, $validated, $before) {
            $detail->update([
                'name' => $validated['name'],
                'amount' => $validated['amount'],
                'notes' => $validated['notes'] ?? null,
                'is_adjustment' => true,
            ]);

            PayrollChangeLogService::recordUpdate($detail, $before, auth()->user());
            $this->recalculatePayroll($payroll);
        });

        ActivityLogService::log(
            auth()->user(),
            'update',
            "Mengubah item gaji {$payroll->employee?->name}: {$oldName} (Rp ".number_format($oldAmount, 0, ',', '.').") → {$validated['name']} (Rp ".number_format($validated['amount'], 0, ',', '.').')',
            $payroll
        );

        return redirect()
            ->route('payrolls.show', $payroll)
            ->with('success', 'Item berhasil diperbarui.');
    }

    public function deletePayrollDetail(PayrollDetail $detail)
    {
        $payroll = $detail->payroll;
        $detailName = $detail->name;
        $detailAmount = $detail->amount;

        if ($response = $this->ensurePayrollEditable($payroll)) {
            return $response;
        }

        if (! $detail->isDeletable()) {
            return back()->with('error', 'Hanya item tunjangan atau potongan yang dapat dihapus.');
        }

        DB::transaction(function () use ($detail, $payroll) {
            PayrollChangeLogService::recordDelete($detail, auth()->user());
            $detail->delete();
            $this->recalculatePayroll($payroll);
        });

        ActivityLogService::log(
            auth()->user(),
            'delete',
            "Menghapus item gaji {$payroll->employee?->name}: {$detailName} Rp ".number_format($detailAmount, 0, ',', '.'),
            $payroll
        );

        return redirect()
            ->route('payrolls.show', $payroll)
            ->with('success', 'Item berhasil dihapus.');
    }

    public function undoLastChange(Payroll $payroll)
    {
        if ($response = $this->ensurePayrollEditable($payroll)) {
            return $response;
        }

        try {
            $change = null;

            DB::transaction(function () use ($payroll, &$change) {
                $change = PayrollChangeLogService::undoLast($payroll, auth()->user());
                $this->recalculatePayroll($payroll);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogService::log(
            auth()->user(),
            'update',
            "Membatalkan perubahan gaji {$payroll->employee?->name}: {$change->summary}",
            $payroll
        );

        return redirect()
            ->route('payrolls.show', $payroll)
            ->with('success', 'Perubahan terakhir berhasil dibatalkan.');
    }

    public function resetToSystem(Payroll $payroll)
    {
        if ($response = $this->ensurePayrollEditable($payroll)) {
            return $response;
        }

        try {
            DB::transaction(function () use ($payroll) {
                PayrollSnapshotService::resetToSystem($payroll);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        ActivityLogService::log(
            auth()->user(),
            'update',
            "Reset gaji ke sistem {$payroll->employee?->name} periode {$payroll->period_month}/{$payroll->period_year}",
            $payroll
        );

        return redirect()
            ->route('payrolls.show', $payroll)
            ->with('success', 'Penggajian berhasil dikembalikan ke hasil sistem.');
    }

    private function ensurePayrollEditable(Payroll $payroll)
    {
        if ($payroll->status === 'paid') {
            return back()->with('error', 'Tidak dapat mengubah penggajian yang sudah lunas.');
        }

        return null;
    }

    private function recalculatePayroll(Payroll $payroll): void
    {
        $details = $payroll->details()->get();

        // Gaji Pokok diambil dari detail row bertipe 'salary' bernama 'Gaji Pokok'
        // 'Total Gross' adalah row informatif, harus dikecualikan agar tidak double-count
        $basicSalary = (float) $details->where('type', 'salary')->where('name', 'Gaji Pokok')->first()?->amount ?? 0;
        $totalAllowance = (float) $details->where('type', 'allowance')->sum('amount');
        $totalDeduction = (float) $details->where('type', 'deduction')->sum('amount');

        // Total Gross = Gaji Pokok + semua tunjangan (termasuk manual adjustment)
        $grossSalary = $basicSalary + $totalAllowance;

        // Net = Total Gross - semua potongan (actual, before rounding)
        $netActual = max($grossSalary - $totalDeduction, 0);
        $netSalary = $netActual;
        $roundedNet = (int) (ceil($netActual / 1000) * 1000);
        $roundingAmount = $roundedNet - $netActual;

        $payroll->update([
            'basic_salary' => $basicSalary,
            'total_allowance' => $totalAllowance,
            'total_deduction' => $totalDeduction,
            'gross_salary' => $grossSalary,
            'full_monthly_salary' => $grossSalary,
            'prorate_salary' => $grossSalary,
            'net_salary' => $netSalary,
            'rounding_amount' => $roundingAmount,
        ]);

        // Sinkronkan row informatif Total Gross jika ada
        $payroll->details()
            ->where('type', 'salary')
            ->where('name', 'Total Gross')
            ->update([
                'amount' => $grossSalary,
                'notes' => 'Gaji Pokok + Total Tunjangan',
            ]);
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year' => ['required', 'integer', 'min:2020'],
        ]);

        $calendarDays = WorkCalendar::whereYear('date', $validated['period_year'])
            ->whereMonth('date', $validated['period_month'])
            ->get();

        if ($calendarDays->isEmpty()) {
            return back()->with('error', 'Kalender kerja belum tersedia untuk periode ini.');
        }

        $workDays = $calendarDays->sum(fn ($day) => $day->type->weight());

        $employees = Employee::where('employment_status', 'active')->get();

        DB::transaction(function () use ($employees, $validated, $workDays) {
            foreach ($employees as $employee) {
                $userId = $employee->user_id;

                $offDays = EmployeeSchedule::query()
                    ->where('employee_id', $employee->id)
                    ->whereYear('work_date', $validated['period_year'])
                    ->whereMonth('work_date', $validated['period_month'])
                    ->whereHas('workSchedule', fn ($query) => $query->where('is_off', true))
                    ->count();

                $employeeWorkDays = max($workDays - $offDays, 0);

                // ── 1. Attendance counts ──────────────────────────────────────
                $presentDays = $userId
                    ? Attendance::where('user_id', $userId)
                        ->whereMonth('date', $validated['period_month'])
                        ->whereYear('date', $validated['period_year'])
                        ->where('type', AttendanceType::Regular)
                        ->whereNotNull('clock_in_time')
                        ->count()
                    : 0;

                $lateDays = $userId
                    ? Attendance::where('user_id', $userId)
                        ->whereMonth('date', $validated['period_month'])
                        ->whereYear('date', $validated['period_year'])
                        ->where('status', 'late')
                        ->count()
                    : 0;

                $sickDays = $userId
                    ? Attendance::where('user_id', $userId)
                        ->whereMonth('date', $validated['period_month'])
                        ->whereYear('date', $validated['period_year'])
                        ->approvedLeave()
                        ->where('type', AttendanceType::Sick)
                        ->count()
                    : 0;

                $leaveDays = $userId
                    ? Attendance::where('user_id', $userId)
                        ->whereMonth('date', $validated['period_month'])
                        ->whereYear('date', $validated['period_year'])
                        ->approvedLeave()
                        ->where('type', AttendanceType::Permission)
                        ->count()
                    : 0;

                $rejectedSickDays = $userId
                    ? Attendance::where('user_id', $userId)
                        ->whereMonth('date', $validated['period_month'])
                        ->whereYear('date', $validated['period_year'])
                        ->rejectedSick()
                        ->count()
                    : 0;

                $reportedSickDays = $sickDays + $rejectedSickDays;

                $overtimeHours = $userId
                    ? (float) Attendance::where('user_id', $userId)
                        ->whereMonth('date', $validated['period_month'])
                        ->whereYear('date', $validated['period_year'])
                        ->where('type', 'regular')
                        ->sum('overtime_hours')
                    : 0.0;

                $absentDays = max($employeeWorkDays - $presentDays - $reportedSickDays - $leaveDays, 0);

                // ── 2. Salary components ──────────────────────────────────────
                $basicSalary = $employee->basic_salary ?? 0;

                $components = EmployeeSalaryComponent::with('salaryComponent')
                    ->where('employee_id', $employee->id)
                    ->where('is_active', true)
                    ->get();

                $totalAllowance = $components
                    ->filter(fn ($c) => $c->salaryComponent?->type === 'allowance'
                        && ! str_contains(strtolower($c->salaryComponent->name ?? ''), 'uang makan')
                    )
                    ->sum('amount');

                $dailyMealAllowance = 20000;
                $mealEligibleDays = $presentDays + $sickDays;
                $mealAllowance = $mealEligibleDays * $dailyMealAllowance;
                $totalAllowance += $mealAllowance;

                $overtimeAmount = $employeeWorkDays > 0
                    ? ($basicSalary / $employeeWorkDays / 8) * $overtimeHours
                    : 0;

                $totalAllowance += $overtimeAmount;

                $totalFixedDeduction = $components
                    ->filter(fn ($c) => $c->salaryComponent?->type === 'deduction')
                    ->sum('amount');

                $totalGross = $basicSalary + $totalAllowance;

                $deductionBase = $totalGross - $mealAllowance - $overtimeAmount;
                $dailyRate = $employeeWorkDays > 0 ? $deductionBase / $employeeWorkDays : 0;

                $alphaDeduction = $dailyRate * $absentDays;
                $permissionDeduction = $dailyRate * $leaveDays;

                $spPenalties = $this->getSpPenalties($lateDays, $leaveDays, $absentDays, $dailyRate);
                $spDeduction = array_sum(array_column($spPenalties, 'amount'));

                $totalAbsenceDeduction = $alphaDeduction + $permissionDeduction;
                $totalDeductionAll = $totalFixedDeduction + $totalAbsenceDeduction + $spDeduction;
                $netActual = max($totalGross - $totalDeductionAll, 0);
                $netSalary = $netActual;
                $roundedNet = (int) (ceil($netActual / 1000) * 1000);
                $roundingAmount = $roundedNet - $netActual;

                $existingPayroll = Payroll::where('employee_id', $employee->id)
                    ->where('period_month', $validated['period_month'])
                    ->where('period_year', $validated['period_year'])
                    ->first();

                if ($existingPayroll?->status === 'paid') {
                    continue;
                }

                $payroll = Payroll::updateOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'period_month' => $validated['period_month'],
                        'period_year' => $validated['period_year'],
                    ],
                    [
                        'work_days' => $employeeWorkDays,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'sick_days' => $reportedSickDays,
                        'leave_days' => $leaveDays,
                        'late_days' => $lateDays,
                        'basic_salary' => $basicSalary,
                        'prorate_salary' => $totalGross,
                        'full_monthly_salary' => $totalGross,
                        'total_allowance' => $totalAllowance,
                        'total_deduction' => $totalDeductionAll,
                        'gross_salary' => $totalGross,
                        'net_salary' => $netSalary,
                        'rounding_amount' => $roundingAmount,
                        'status' => 'draft',
                    ]
                );

                PayrollDetail::withTrashed()
                    ->where('payroll_id', $payroll->id)
                    ->where('is_adjustment', false)
                    ->forceDelete();

                PayrollDetail::withTrashed()
                    ->where('payroll_id', $payroll->id)
                    ->where('is_adjustment', true)
                    ->where(function ($q) {
                        $q->where('name', 'like', 'Potongan SP%')
                            ->orWhere('name', 'like', 'SP%');
                    })
                    ->forceDelete();

                PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'name' => 'Gaji Pokok',
                    'type' => 'salary',
                    'amount' => $basicSalary,
                ]);

                if (! $this->hasManualOverride($payroll, 'Uang Makan', 'allowance')) {
                    $mealNoteParts = [];
                    if ($presentDays > 0) {
                        $mealNoteParts[] = "{$presentDays} hari hadir × Rp ".number_format($dailyMealAllowance, 0, ',', '.');
                    }
                    if ($sickDays > 0) {
                        $mealNoteParts[] = "{$sickDays} hari sakit disetujui × Rp ".number_format($dailyMealAllowance, 0, ',', '.');
                    }
                    if ($rejectedSickDays > 0) {
                        $mealNoteParts[] = "{$rejectedSickDays} hari sakit ditolak tidak mendapat uang makan (Rp "
                            .number_format($rejectedSickDays * $dailyMealAllowance, 0, ',', '.').')';
                    }
                    $mealNotes = $mealNoteParts !== [] ? implode('; ', $mealNoteParts) : '0 hari';

                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'name' => 'Uang Makan',
                        'type' => 'allowance',
                        'amount' => $mealAllowance,
                        'notes' => $mealNotes,
                        'is_adjustment' => false,
                    ]);
                }

                if ($overtimeHours > 0 && ! $this->hasManualOverride($payroll, 'Lembur', 'allowance')) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'name' => 'Lembur',
                        'type' => 'allowance',
                        'amount' => $overtimeAmount,
                        'notes' => number_format($overtimeHours, 1).' jam × (Gaji Pokok / Hari Kerja / 8)',
                        'is_adjustment' => false,
                    ]);
                }

                foreach ($components->filter(fn ($c) => $c->salaryComponent?->type === 'allowance'
                    && ! str_contains(strtolower($c->salaryComponent->name ?? ''), 'uang makan')
                ) as $comp) {
                    $componentName = $comp->salaryComponent->name;

                    if ($this->hasManualOverride($payroll, $componentName, 'allowance')) {
                        continue;
                    }

                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'name' => $componentName,
                        'type' => 'allowance',
                        'amount' => $comp->amount,
                    ]);
                }

                foreach ($components->filter(fn ($c) => $c->salaryComponent?->type === 'deduction') as $comp) {
                    $componentName = $comp->salaryComponent->name;

                    if ($this->hasManualOverride($payroll, $componentName, 'deduction')) {
                        continue;
                    }

                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'name' => $componentName,
                        'type' => 'deduction',
                        'amount' => $comp->amount,
                    ]);
                }

                PayrollDetail::create([
                    'payroll_id' => $payroll->id,
                    'name' => 'Total Gross',
                    'type' => 'salary',
                    'amount' => $totalGross,
                    'notes' => 'Gaji Pokok + Total Tunjangan',
                ]);

                if ($alphaDeduction > 0 && ! $this->hasManualOverride($payroll, 'Potongan Alfa', 'deduction')) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'name' => 'Potongan Alfa',
                        'type' => 'deduction',
                        'amount' => $alphaDeduction,
                        'notes' => "(Total Gross - Lembur - Uang Makan) / {$employeeWorkDays} HK × {$absentDays} hari alfa",
                        'is_adjustment' => false,
                    ]);
                }

                if ($permissionDeduction > 0 && ! $this->hasManualOverride($payroll, 'Potongan Izin Tidak Berbayar', 'deduction')) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'name' => 'Potongan Izin Tidak Berbayar',
                        'type' => 'deduction',
                        'amount' => $permissionDeduction,
                        'notes' => "(Total Gross - Lembur - Uang Makan) / {$employeeWorkDays} HK × {$leaveDays} hari izin",
                        'is_adjustment' => false,
                    ]);
                }

                foreach ($spPenalties as $sp) {
                    PayrollDetail::create([
                        'payroll_id' => $payroll->id,
                        'name' => $sp['name'],
                        'type' => 'deduction',
                        'amount' => $sp['amount'],
                        'notes' => $sp['notes'],
                        'is_adjustment' => true,
                    ]);
                }

                $this->recalculatePayroll($payroll);
                PayrollChangeLogService::supersedeAll($payroll);
                PayrollSnapshotService::capture($payroll);
            }
        });

        ActivityLogService::log(
            auth()->user(),
            'create',
            "Memproses gaji bulanan periode {$validated['period_month']}/{$validated['period_year']}"
        );

        return redirect()
            ->route('payrolls.index')
            ->with('success', 'Data penggajian berhasil diproses.');
    }

    /**
     * SP dihitung TERPISAH per kategori pelanggaran.
     * Basis potongan = Daily Rate.
     *
     * Setiap kategori (Telat / Izin / Alpha):
     *   >= 3  → SP1 (25%)
     *   >= 6  → SP2 (50%)
     *   >= 9  → SP3 (75%)
     *
     * Returns array of penalty entries, one per triggered category.
     */
    private function getSpPenalties(int $lateDays, int $leaveDays, int $absentDays, float $dailyRate): array
    {
        $penalties = [];

        $categories = [
            ['label' => 'Telat', 'count' => $lateDays, 'note_unit' => 'kali telat'],
            ['label' => 'Izin', 'count' => $leaveDays, 'note_unit' => 'hari izin tidak berbayar'],
            ['label' => 'Alpha', 'count' => $absentDays, 'note_unit' => 'hari alfa'],
        ];

        foreach ($categories as $cat) {
            $count = $cat['count'];

            if ($count > 9) {
                $level = 'SP3';
                $pct = 0.75;
            } elseif ($count > 6) {
                $level = 'SP2';
                $pct = 0.50;
            } elseif ($count > 3) {
                $level = 'SP1';
                $pct = 0.25;
            } else {
                continue;
            }

            $penalties[] = [
                'name' => "Potongan {$level} {$cat['label']}",
                'amount' => $dailyRate * $pct,
                'notes' => "{$count} {$cat['note_unit']} — {$level} ".round($pct * 100).'% Rate harian',
            ];
        }

        return $penalties;
    }

    private function hasManualOverride(Payroll $payroll, string $name, string $type): bool
    {
        return PayrollDetail::query()
            ->where('payroll_id', $payroll->id)
            ->where('name', $name)
            ->where('type', $type)
            ->where('is_adjustment', true)
            ->exists();
    }
}
