<?php

namespace App\Exports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AbsenceThresholdExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithTitle, WithCustomStartCell, WithStyles, WithEvents
{
    private int $rowNumber = 1;

    /**
     * @param  list<array{
     *     user_id: int,
     *     employee_id: int|null,
     *     name: string,
     *     employee_code: string,
     *     alpha_count: int,
     *     permission_count: int,
     *     alpha_dates: list<string>,
     *     permission_dates: list<string>
     * }>  $rows
     * @param  array{total: int, alpha_threshold: int, permission_threshold: int}  $summary
     */
    public function __construct(
        private readonly array $rows,
        private readonly array $summary,
        private readonly string $periodLabel,
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows);
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Karyawan',
            'Nama Karyawan',
            'Jumlah Alfa',
            'Tanggal Alfa',
            'Jumlah Izin',
            'Tanggal Izin',
        ];
    }

    public function map($row): array
    {
        $alphaCount = (int) ($row['alpha_count'] ?? 0);
        $permissionCount = (int) ($row['permission_count'] ?? 0);

        return [
            $this->rowNumber++,
            $row['employee_code'] ?? '-',
            $row['name'] ?? '-',
            $alphaCount > 0 ? $alphaCount : '-',
            $this->formatDates($row['alpha_dates'] ?? []),
            $permissionCount > 0 ? $permissionCount : '-',
            $this->formatDates($row['permission_dates'] ?? []),
        ];
    }

    public function title(): string
    {
        return 'Alfa & Izin';
    }

    public function startCell(): string
    {
        return 'A6';
    }

    public function styles(Worksheet $sheet): array
    {
        return [
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
                $highestRow = max((int) $sheet->getHighestRow(), 6);
                $exportDate = now()->locale('id')->translatedFormat('d F Y');

                $sheet->mergeCells('A1:G1');
                $sheet->setCellValue('A1', 'LAPORAN IZIN & ALFA');
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

                $sheet->mergeCells('A2:G2');
                $sheet->setCellValue('A2', 'Nama Perusahaan');
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->mergeCells('A3:G3');
                $sheet->setCellValue('A3', "Periode : {$this->periodLabel}");
                $sheet->getStyle('A3')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->mergeCells('A4:G4');
                $sheet->setCellValue('A4', "Tanggal Export : {$exportDate}");
                $sheet->getStyle('A4')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->mergeCells('A5:G5');
                $sheet->setCellValue('A5', "Total karyawan: {$this->summary['total']}");
                $sheet->getStyle('A5')->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);

                $sheet->getStyle("A6:G{$highestRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A7:A{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("D7:D{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("F7:F{$highestRow}")->applyFromArray([
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
            },
        ];
    }

    /**
     * @param  list<string>  $dates
     */
    private function formatDates(array $dates): string
    {
        if ($dates === []) {
            return '-';
        }

        return collect($dates)
            ->map(fn (string $date) => Carbon::parse($date)->translatedFormat('d M Y'))
            ->implode(', ');
    }
}
