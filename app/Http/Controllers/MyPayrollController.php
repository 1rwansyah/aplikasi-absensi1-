<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Services\PayrollAttendanceDateBreakdown;
use App\Services\PayrollBrowsershotService;
use Illuminate\Http\Request;

class MyPayrollController extends Controller
{
    public function index(Request $request)
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'Anda tidak terdaftar sebagai karyawan.');
        }

        $filters = [
            'period_month' => $request->period_month,
            'period_year' => $request->period_year,
            'status' => $request->status,
        ];

        $payrolls = Payroll::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'paid')
            ->when($filters['period_month'], fn ($q) => $q->where('period_month', $filters['period_month']))
            ->when($filters['period_year'], fn ($q) => $q->where('period_year', $filters['period_year']))
            ->when($filters['status'], fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('my-payrolls.index', compact('payrolls', 'filters'));
    }

    public function show(Payroll $payroll)
    {
        $employee = auth()->user()->employee;

        if (! $employee || $payroll->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this payroll record.');
        }

        $payroll->load('details');

        $attendanceDates = app(PayrollAttendanceDateBreakdown::class)->forPayroll($payroll);

        return view('my-payrolls.show', compact('payroll', 'attendanceDates'));
    }

    public function downloadPdf(Payroll $payroll)
    {
        $employee = auth()->user()->employee;

        if (! $employee || $payroll->employee_id !== $employee->id) {
            abort(403, 'Unauthorized access to this payroll record.');
        }

        $pdfService = new PayrollBrowsershotService();

        return $pdfService->generate($payroll);
    }
}
