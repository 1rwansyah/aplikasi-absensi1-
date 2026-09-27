<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\PayrollDetail;

class WhatsappPayrollReportService
{
    private const MONTHS = [
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

    public function generate(int $periodMonth, int $periodYear): string
    {
        $payrolls = Payroll::with('employee', 'details')
            ->where('period_month', $periodMonth)
            ->where('period_year', $periodYear)
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->orderBy('employees.id', 'asc')
            ->select('payrolls.*')
            ->get();

        $monthName = self::MONTHS[$periodMonth] ?? (string) $periodMonth;

        $message = "Permohonan Pengajuan Gaji ";
        $message .= "Periode {$monthName} {$periodYear}\n\n";

        $counter = 1;
        $totalNet = 0;

        foreach ($payrolls as $payroll) {
            $employee = $payroll->employee;
            $name = $employee?->name ?? '-';
            $position = $employee?->position ?? '-';
            $takeHomePay = $payroll->roundedNetSalary();
            $totalNet += $takeHomePay;

            $attendanceInfo = $this->getAttendanceInfo($payroll);

            $line = "{$name}({$position})-{$attendanceInfo}-Rp.".number_format($takeHomePay, 0, ',', '.');

            $message .= $line."\n";

            $counter++;
        }

        $message .= "\nTotal: Rp ".number_format($totalNet, 0, ',', '.')."\n";

        return trim($message);
    }

    private function getAttendanceInfo(Payroll $payroll): string
    {
        $parts = [];
        $absentDays = $payroll->absent_days ?? 0;
        $sickDays = $payroll->sick_days ?? 0;
        $leaveDays = $payroll->leave_days ?? 0;

        if ($absentDays > 0) {
            $parts[] = "Absen {$absentDays}";
        }
        if ($sickDays > 0) {
            $parts[] = "Sakit {$sickDays}";
        }
        if ($leaveDays > 0) {
            $parts[] = "Izin {$leaveDays}";
        }

        if (empty($parts)) {
            $parts[] = 'Absen 0';
        }

        $spInfo = $this->getSpInfo($payroll);
        if ($spInfo) {
            $parts[] = $spInfo;
        }

        return implode(', ', $parts);
    }

    private function getSpInfo(Payroll $payroll): ?string
    {
        $spDetails = $payroll->details()
            ->where('type', 'deduction')
            ->where('is_adjustment', true)
            ->where(function ($q) {
                $q->where('name', 'like', 'Potongan SP%')
                    ->orWhere('name', 'like', 'SP%');
            })
            ->get();

        if ($spDetails->isEmpty()) {
            return null;
        }

        $parts = [];
        foreach ($spDetails as $detail) {
            $parts[] = str_replace('Potongan ', '', $detail->name);
        }

        return implode(', ', $parts);
    }
}
