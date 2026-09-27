<?php

namespace App\Exports;

use App\Models\Payroll;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Events\AfterSheet;

class PayrollExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStrictNullComparison, WithTitle, WithCustomStartCell, WithColumnFormatting, WithStyles, WithEvents
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

    public function __construct(private readonly array $filters = []) {}

    public function query()
    {
        return Payroll::query()
            ->when($this->filters['period_month'] ?? null, fn ($q) => $q->where('period_month', $this->filters['period_month']))
            ->when($this->filters['period_year'] ?? null, fn ($q) => $q->where('period_year', $this->filters['period_year']))
            ->when($this->filters['status'] ?? null, fn ($q) => $q->where('status', $this->filters['status']))
            ->when($this->filters['search'] ?? null, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq
                ->where('name', 'like', '%'.$this->filters['search'].'%')
                ->orWhere('employee_code', 'like', '%'.$this->filters['search'].'%')
            ))
            ->join('employees', 'employees.id', '=', 'payrolls.employee_id')
            ->orderBy('employees.id', 'asc')
            ->select('payrolls.*')
            ->with('employee');
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Karyawan',
            'Nama Karyawan',
            'Jabatan',
            'Staff',
            'Bank',
            'No. Rekening',
            'Nama Rekening',
            'Periode',
            'Hari Kerja',
            'Hadir',
            'Alfa',
            'Sakit',
            'Izin',
            'Telat',
            'Gaji Pokok',
            'Total Tunjangan',
            'Total Potongan',
            'Total Gross',
            'Take Home Pay',
            'Pembulatan',
            'Status',
        ];
    }

    public function map($payroll): array
    {
        static $rowNumber = 1;

        $employee = $payroll->employee;
        $monthName = self::MONTHS[$payroll->period_month] ?? $payroll->period_month;
        $status = $payroll->status === 'paid' ? 'Lunas' : 'Draft';

        return [
            $rowNumber++,
            $employee?->employee_code ?? '-',
            $employee?->name ?? '-',
            $employee?->position ?? '-',
            $employee?->staff ?? '-',
            $employee?->bank_name ?? '-',
            $employee?->bank_account_number ?? '-',
            $employee?->bank_account_name ?? '-',
            "{$monthName} {$payroll->period_year}",
            $payroll->formattedWorkDays(),
            $payroll->present_days,
            $payroll->absent_days,
            $payroll->sick_days,
            $payroll->leave_days,
            $payroll->late_days,
            $payroll->basic_salary,
            $payroll->total_allowance,
            $payroll->total_deduction,
            $payroll->gross_salary,
            $payroll->roundedNetSalary(),
            $payroll->rounding_amount,
            $status,
        ];
    }

    public function title(): string
    {
        return 'Penggajian';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function columnFormats(): array
    {
        return [
            'P' => 'Rp #,##0', // Gaji Pokok
            'Q' => 'Rp #,##0', // Total Tunjangan
            'R' => 'Rp #,##0', // Total Potongan
            'S' => 'Rp #,##0', // Total Gross
            'T' => 'Rp #,##0', // Take Home Pay
            'U' => 'Rp #,##0', // Pembulatan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style header row (row 6)
            6 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Judul Laporan (merge A1:V1)
                $sheet->mergeCells('A1:V1');
                $sheet->setCellValue('A1', 'LAPORAN PENGGAJIAN');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Nama Perusahaan (merge A2:V2)
                $sheet->mergeCells('A2:V2');
                $sheet->setCellValue('A2', 'Nama Perusahaan');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Periode Laporan (merge A3:V3)
                $period = $this->getPeriodLabel();
                $sheet->mergeCells('A3:V3');
                $sheet->setCellValue('A3', "Periode : {$period}");
                $sheet->getStyle('A3')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Tanggal Export (merge A4:V4)
                $exportDate = now()->locale('id')->translatedFormat('d F Y');
                $sheet->mergeCells('A4:V4');
                $sheet->setCellValue('A4', "Tanggal Export : {$exportDate}");
                $sheet->getStyle('A4')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Border untuk seluruh tabel data
                $sheet->getStyle('A6:'.$highestColumn.$highestRow)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                // Alignment untuk kolom tertentu
                // Kolom No (A) - Center
                $sheet->getStyle('A7:A'.$highestRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Kolom angka (P:U) - Right
                $sheet->getStyle('P7:U'.$highestRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                    ],
                ]);

                // Kolom Status (V) - Center
                $sheet->getStyle('V7:V'.$highestRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                // Kolom teks (B:I, J:O) - Left
                $sheet->getStyle('B7:O'.$highestRow)->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                    ],
                ]);

                // Freeze pane pada row header (row 6)
                $sheet->freezePane('A7');

                // Auto filter pada header
                $sheet->setAutoFilter('A6:'.$highestColumn.'6');

                // Page setup untuk cetak
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setRight(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setLeft(0.5);
            },
        ];
    }

    private function getPeriodLabel(): string
    {
        $month = isset($this->filters['period_month']) ? $this->filters['period_month'] : null;
        $year = isset($this->filters['period_year']) ? $this->filters['period_year'] : null;

        if ($month && $year) {
            $monthName = self::MONTHS[$month] ?? $month;
            return "{$monthName} {$year}";
        }

        if ($month) {
            $monthName = self::MONTHS[$month] ?? $month;
            return $monthName;
        }

        if ($year) {
            return "Tahun {$year}";
        }

        return 'Semua Periode';
    }
}
