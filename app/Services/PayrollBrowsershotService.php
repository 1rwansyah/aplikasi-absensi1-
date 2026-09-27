<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\Payroll;

class PayrollBrowsershotService
{
    /**
     * Generate PDF from payroll data using PDF Service
     *
     * @param  Payroll  $payroll
     * @return \Illuminate\Http\Response
     */
    public function generate(Payroll $payroll)
    {
        try {
            // Prepare data for template
            $data = $this->prepareData($payroll);

            // Render Blade template to HTML
            $html = view('payrolls.slip-pdf', $data)->render();

            // Generate filename
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
            $monthName = $months[$payroll->period_month] ?? $payroll->period_month;
            $employeeName = str_replace(' ', '_', $data['employee']['name']);
            $fileName = "Slip_Gaji_{$employeeName}_{$monthName}_{$payroll->period_year}.pdf";

            // Generate PDF using PDF Service Client
            $pdfClient = new PdfServiceClient();
            $pdf = $pdfClient->generatePdf($html);

            // Return PDF download response
            return response($pdf)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', "attachment; filename=\"{$fileName}\"");
        } catch (\Exception $e) {
            // Log error and return error response
            \Log::error('PDF generation failed (PDF Service): ' . $e->getMessage(), [
                'payroll_id' => $payroll->id,
                'trace' => $e->getTraceAsString(),
            ]);

            return response('Gagal generate PDF. Silakan coba lagi atau hubungi admin.', 500);
        }
    }

    /**
     * Prepare payroll data for PDF template
     *
     * @param  Payroll  $payroll
     * @return array<string, mixed>
     */
    public function prepareData(Payroll $payroll): array
    {
        // Load payroll with relationships
        $payroll->load(['employee', 'details']);

        // Prepare background image as Base64
        $backgroundImage = null;
        $imagePath = public_path('images/payrolls/slip-template.jpg');
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            $backgroundImage = 'data:image/jpeg;base64,' . $imageData;
        }

        // Check if employee exists
        if (! $payroll->employee) {
            throw new \RuntimeException('Employee data not found for this payroll.');
        }

        // Group salary components by type
        $salaryComponents = $payroll->details
            ->filter(fn ($detail) => $detail->type === 'salary')
            ->sortBy('name')
            ->values();

        $allowanceComponents = $payroll->details
            ->filter(fn ($detail) => $detail->type === 'allowance')
            ->sortBy('name')
            ->values();

        $deductionComponents = $payroll->details
            ->filter(fn ($detail) => $detail->type === 'deduction')
            ->sortBy('name')
            ->values();

        // Prepare company settings from database
        $companyProfile = CompanyProfile::getProfile();
        $company = [
            'name' => $companyProfile?->name ?? 'Nama Perusahaan',
            'address' => $companyProfile?->address ?? 'Alamat kantor',
            'email' => $companyProfile?->email ?? 'company@example.com',
            'phone' => $companyProfile?->phone ?? '0210000000',
            'website' => $companyProfile?->website,
            'logo' => $companyProfile?->logo_url,
        ];

        // Prepare employee data with null safety
        $employee = [
            'name' => $payroll->employee->name ?? '',
            'employee_code' => $payroll->employee->employee_code ?? '',
            'position' => $payroll->employee->position ?? '',
            'staff' => $payroll->employee->staff ?? '',
            'join_date' => $payroll->employee->join_date?->format('d F Y') ?? '',
            'bank_name' => $payroll->employee->bank_name ?? '-',
            'bank_account_number' => $payroll->employee->bank_account_number ?? '-',
            'bank_account_name' => $payroll->employee->bank_account_name ?? '-',
        ];

        // Prepare period
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
        $period = [
            'month' => $months[$payroll->period_month] ?? $payroll->period_month,
            'year' => $payroll->period_year,
            'formatted' => ($months[$payroll->period_month] ?? $payroll->period_month) . ' ' . $payroll->period_year,
        ];

        // Prepare attendance summary with null safety
        $attendance = [
            'work_days' => $payroll->work_days ?? 0,
            'present_days' => $payroll->present_days ?? 0,
            'absent_days' => $payroll->absent_days ?? 0,
            'sick_days' => $payroll->sick_days ?? 0,
            'leave_days' => $payroll->leave_days ?? 0,
            'late_days' => $payroll->late_days ?? 0,
            'overtime_hours' => 0,
        ];

        // Calculate overtime hours from attendance records
        $overtimeDetails = [];
        if ($payroll->employee && $payroll->employee->user_id) {
            $overtimeRecords = \App\Models\Attendance::where('user_id', $payroll->employee->user_id)
                ->whereMonth('date', $payroll->period_month)
                ->whereYear('date', $payroll->period_year)
                ->where('type', \App\Enums\AttendanceType::Regular)
                ->where('overtime_hours', '>', 0)
                ->orderBy('date')
                ->get(['date', 'overtime_hours']);

            foreach ($overtimeRecords as $record) {
                $overtimeDetails[] = [
                    'date' => $record->date->format('d/m/Y'),
                    'hours' => (float) $record->overtime_hours,
                ];
            }

            $overtimeTotal = \App\Models\Attendance::where('user_id', $payroll->employee->user_id)
                ->whereMonth('date', $payroll->period_month)
                ->whereYear('date', $payroll->period_year)
                ->where('type', \App\Enums\AttendanceType::Regular)
                ->sum('overtime_hours');
            $attendance['overtime_hours'] = (float) $overtimeTotal;
        }

        // Prepare financial summary with null safety
        $summary = [
            'basic_salary' => $payroll->basic_salary ?? 0,
            'total_allowance' => $payroll->total_allowance ?? 0,
            'total_deduction' => $payroll->total_deduction ?? 0,
            'gross_salary' => $payroll->gross_salary ?? 0,
            'net_salary' => $payroll->net_salary ?? 0,
            'rounding_amount' => $payroll->rounding_amount ?? 0,
            'rounded_net_salary' => $payroll->roundedNetSalary() ?? 0,
        ];

        // Prepare salary components for display
        $components = [
            'salary' => $salaryComponents->map(fn ($detail) => [
                'name' => $detail->name ?? '',
                'amount' => (float) ($detail->amount ?? 0),
                'notes' => $detail->notes,
            ])->toArray(),
            'allowance' => $allowanceComponents->map(fn ($detail) => [
                'name' => $detail->name ?? '',
                'amount' => (float) ($detail->amount ?? 0),
                'notes' => $detail->notes,
            ])->toArray(),
            'deduction' => $deductionComponents->map(fn ($detail) => [
                'name' => $detail->name ?? '',
                'amount' => (float) ($detail->amount ?? 0),
                'notes' => $detail->notes,
            ])->toArray(),
        ];

        return [
            'company' => $company,
            'employee' => $employee,
            'payroll' => [
                'id' => $payroll->id,
                'status' => $payroll->status ?? 'unknown',
                'paid_at' => $payroll->paid_at?->format('d F Y'),
            ],
            'period' => $period,
            'attendance' => $attendance,
            'overtime_details' => $overtimeDetails,
            'summary' => $summary,
            'components' => $components,
            'backgroundImage' => $backgroundImage,
        ];
    }
}
