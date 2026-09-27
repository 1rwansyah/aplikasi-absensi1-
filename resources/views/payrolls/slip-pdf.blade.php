<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji - {{ $employee['name'] }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
            position: relative;
        }

        .page-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        /* ===== Preview Mode Only ===== */
        .page {
            width: 210mm;
            height: 287mm;
            margin: 0 auto;
            padding: 0;
            box-sizing: border-box;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
        }

        .page-content {
            position: relative;
            z-index: 1;
        }

        .container {
            width: 90%;
            margin: 0 auto 15px auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .header-left {
            display: table-cell;
            vertical-align: top;
            width: 100px;
        }

        .header-right {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            padding-right: 50px;
        }

        .header h1 {
            font-size: 30px;
            color: #000;
            font-weight: bold;
            margin-bottom: 0;
        }

        /* Two Column Layout */
        .two-col-section {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .col-left {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 20px;
            padding-left: 10px;
        }

        .col-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-left: 20px;
        }

        /* Info Section */
        .info-section {
            margin-bottom: 0;
        }

        .info-row {
            display: table;
            width: 100%;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            color: #000;
            margin-bottom: 3px;
            font-size: 12px;
        }

        .info-value {
            color: #000;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .info-value-right {
            display: table-cell;
            color: #000;
            margin-bottom: 10px;
            font-size: 12px;
            text-align: right;
        }

        .info-value-inline {
            display: table-cell;
            color: #000;
            font-size: 12px;
            text-align: right;
        }

        /* Section Title */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
            padding: 6px 10px;
            background: #f0f0f0;
            border: 1px solid #ddd;
        }

        /* Income/Deduction List */
        .item-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .item-label {
            display: table-cell;
            color: #000;
            font-size: 12px;
        }

        .item-value {
            display: table-cell;
            text-align: right;
            color: #000;
            font-size: 12px;
        }

        .divider {
            border-top: 1px solid #ccc;
            margin: 10px 0;
        }

        .total-row {
            font-weight: bold;
            font-size: 12px;
        }

        .total-row .item-label {
            font-weight: bold;
        }

        .total-row .item-value {
            font-weight: bold;
        }

        /* Summary Section */
        .summary-section {
            margin-bottom: 25px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .summary-label {
            display: table-cell;
            width: 40%;
            font-weight: bold;
            color: #000;
            font-size: 12px;
        }

        .summary-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
            color: #000;
            font-size: 12px;
        }

        /* Attendance Table */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .attendance-table th,
        .attendance-table td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: center;
            font-size: 12px;
        }

        .attendance-table th {
            background: #f0f0f0;
            font-weight: bold;
            border: 1px solid #ccc;
        }

        /* Total Box */
        .total-box {
            border: 1px solid #000;
            padding: 10px;
            margin-top: 25px;
            text-align: right;
        }

        .total-box-label {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin-bottom: 8px;
        }

        .total-box-value {
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }

        /* Signature */
        .signature {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            text-align: left;
            width: 60px;
            vertical-align: top;
        }

        .signature-box .date {
            margin-bottom: 0;
            font-size: 12px;
            color: #000;
        }

        .signature-box .label {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 0;
            color: #000;
        }

        .signature-box .line {
            height: 70px;
        }

        .signature-box .name {
            font-size: 12px;
            font-weight: bold;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 0;
            display: inline-block;
        }

        /* Footer */
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 15px 20px;
            text-align: right;
            font-size: 12px;
            color: #666;
        }

        .footer p {
            margin: 3px 0;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 15px;
            color: #999;
            font-style: italic;
            font-size: 10px;
        }

        /* ===== Preview Mode Only ===== */
        @page {
            size: A4;
            margin: 0;
        }

        @media print {
            body {
                padding: 0;
            }

            .page {
                width: 210mm;
                height: 287mm;
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    {{-- Background Image --}}
    @if($backgroundImage)
        <img class="page-background" src="{{ $backgroundImage }}" alt="">
    @endif
    <div class="page">

        <div class="page-content">
            <div class="container">

            {{-- ============================================ --}}
            {{-- HEADER SECTION --}}
            {{-- ============================================ --}}
            <div class="header">
                <div class="header-left">
                    @if($company['logo'])
                        <img src="{{ $company['logo'] }}" alt="Logo" style="max-width: 80px;">
                    @endif
                </div>
                <div class="header-right">
                    <h1>SLIP GAJI</h1>
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- COMPANY & EMPLOYEE INFO SECTION --}}
            {{-- ============================================ --}}
            <div class="two-col-section">
                {{-- Left: Company Information --}}
                <div class="col-left">
                    <div class="info-section">
                        <div class="info-label">{{ $company['name'] }}</div>
                        <div class="info-value">{{ $company['address'] }}</div>
                    </div>
                </div>

                {{-- Right: Employee Information --}}
                <div class="col-right">
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-label">Nama / ID</div>
                            <div class="info-value-inline">{{ $employee['name'] }} / {{ $employee['employee_code'] }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Jabatan / Staff</div>
                            <div class="info-value-right">{{ $employee['position'] }} / {{ $employee['staff'] ?? '' }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Tanggal Mulai Bekerja</div>
                            <div class="info-value-right">{{ $employee['join_date'] }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Periode Gaji</div>
                            <div class="info-value-right">{{ $period['formatted'] }}</div>
                        </div>

                        <div class="info-row">
                            <div class="info-label">Hari Kerja Aktif</div>
                            <div class="info-value-right">{{ (int) $attendance['work_days'] }} hari</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- INCOME & DEDUCTIONS SECTION --}}
            {{-- ============================================ --}}
            <div class="section-title">
                <div class="two-col-section" style="margin-bottom: 0;">
                    <div>PENDAPATAN</div>
                    <div class="col-right">POTONGAN</div>
                </div>
            </div>
            <div class="two-col-section">
                {{-- Left: Income Components --}}
                <div class="col-left">
                    {{-- Salary Components --}}
                    @foreach($components['salary'] as $item)
                        <div class="item-row">
                            <div class="item-label">{{ $item['name'] }}</div>
                            <div class="item-value">Rp {{ number_format($item['amount'], 0, ',', '.') }}</div>
                        </div>
                    @endforeach

                    {{-- Allowance Components --}}
                    @foreach($components['allowance'] as $item)
                        <div class="item-row">
                            <div class="item-label">{{ $item['name'] }}</div>
                            <div class="item-value">Rp {{ number_format($item['amount'], 0, ',', '.') }}</div>
                        </div>
                    @endforeach

                    {{-- Empty State --}}
                    @if(empty($components['salary']) && empty($components['allowance']))
                        <div class="empty-state">Tidak ada komponen pendapatan</div>
                    @endif
                </div>

                {{-- Right: Deduction Components --}}
                <div class="col-right">
                    {{-- Deduction Components --}}
                    @foreach($components['deduction'] as $item)
                        <div class="item-row">
                            <div class="item-label">{{ $item['name'] }}</div>
                            <div class="item-value">Rp {{ number_format($item['amount'], 0, ',', '.') }}</div>
                        </div>
                    @endforeach

                    {{-- Empty State --}}
                    @if(empty($components['deduction']))
                        <div class="empty-state">Tidak ada potongan</div>
                    @endif
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- TOTALS SECTION --}}
            {{-- ============================================ --}}
            <div class="divider"></div>
            <div class="two-col-section">
                {{-- Left: Take Home Pay --}}
                <div class="col-left">
                    <div class="item-row total-row">
                        <div class="item-label">Take Home Pay</div>
                        <div class="item-value">Rp {{ number_format($summary['rounded_net_salary'], 0, ',', '.') }}</div>
                    </div>
                </div>

                {{-- Right: Total Deductions --}}
                <div class="col-right">
                    <div class="item-row total-row">
                        <div class="item-label">Total Potongan</div>
                        <div class="item-value">Rp {{ number_format($summary['total_deduction'], 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>

            {{-- ============================================ --}}
            {{-- ATTENDANCE RECAP SECTION --}}
            {{-- ============================================ --}}
            <div class="section-title" style="margin-top: 20px;">REKAP ABSENSI</div>

            @if(!empty($overtime_details))
                <div class="item-row" style="margin-top: 10px;">
                    <div class="item-label" style="font-weight: bold; padding-left: 10px;">Lembur</div>
                </div>
                @foreach($overtime_details as $detail)
                    <div class="item-row">
                        <div class="item-label" style="padding-left: 10px;">{{ $detail['date'] }}</div>
                        <div class="item-value">{{ $detail['hours'] }} jam</div>
                    </div>
                @endforeach
                <div class="divider"></div>
                <div class="item-row total-row">
                    <div class="item-label" style="padding-left: 10px;">Total Lembur</div>
                    <div class="item-value">{{ $attendance['overtime_hours'] }} jam</div>
                </div>
            @endif

            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Izin</th>
                        <th>Alfa</th>
                        <th>Sakit</th>
                        <th>Cuti</th>
                        <th>Dinas Luar</th>
                        <th>Telat</th>
                        <th>Lembur</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $attendance['leave_days'] }}</td>
                        <td>{{ $attendance['absent_days'] }}</td>
                        <td>{{ $attendance['sick_days'] }}</td>
                        <td>0</td>
                        <td>0</td>
                        <td>{{ $attendance['late_days'] }}</td>
                        <td>{{ $attendance['overtime_hours'] }} jam</td>
                    </tr>
                </tbody>
            </table>

{{-- ============================================ --}}
            {{-- SIGNATURE SECTION --}}
            {{-- ============================================ --}}
            <div class="signature">
                <div class="signature-box">
                    <div class="date">Jakarta, {{ $payroll['paid_at'] ?? now()->format('d F Y') }}</div>
                    <div class="label">Diterima Oleh,</div>
                    <div class="line"></div>
                    <div class="name">{{ $employee['name'] }}</div>
                </div>
            </div>

        </div>
    </div>

            {{-- ============================================ --}}
            {{-- FOOTER SECTION --}}
            {{-- ============================================ --}}
            <div class="footer">
                <p>{{ $company['email'] }}</p>
                <p>{{ $company['phone'] }}</p>
                <p>{{ $company['address'] }}</p>
            </div>

    </div>
</body>
</html>
