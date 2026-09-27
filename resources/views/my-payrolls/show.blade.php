<x-app-layout>
    <x-attendance.employee-page page-id="my-payroll-detail-page">
        <div class="space-y-6">

            @php
                $months = [
                    '',
                    'Januari',
                    'Februari',
                    'Maret',
                    'April',
                    'Mei',
                    'Juni',
                    'Juli',
                    'Agustus',
                    'September',
                    'Oktober',
                    'November',
                    'Desember',
                ];
                $emp = $payroll->employee;
                $periodLabel = ($months[$payroll->period_month] ?? $payroll->period_month).' '.$payroll->period_year;
                $attendanceRate = $payroll->work_days > 0
                    ? min(100, round(($payroll->present_days / $payroll->work_days) * 100))
                    : 0;
                $attendanceWidthClass = [
                    'w-0', 'w-[10%]', 'w-[20%]', 'w-[30%]', 'w-[40%]', 'w-1/2',
                    'w-[60%]', 'w-[70%]', 'w-[80%]', 'w-[90%]', 'w-full',
                ][(int) round($attendanceRate / 10)];
            @endphp

            {{-- Back + Header --}}
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('my-payrolls.index') }}"
                    class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Semua slip
                </a>
                <a href="{{ route('my-payrolls.pdf', $payroll) }}"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m4 6H5a2 2 0 01-2-2V7a2 2 0 012-2h4l2-2h2l2 2h4a2 2 0 012 2v10a2 2 0 01-2 2z" />
                    </svg>
                    Unduh PDF
                </a>
            </div>

            {{-- Payroll hero --}}
            <section class="relative overflow-hidden rounded-3xl border border-red-900/10 bg-gradient-to-br from-red-700 via-red-600 to-rose-700 px-5 py-6 text-white shadow-lg shadow-red-900/10 sm:px-7 sm:py-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[34px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-24 right-32 h-44 w-44 rounded-full bg-white/5"></div>
                <div class="relative flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur">
                                {{ $periodLabel }}
                            </span>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-red-700">
                                {{ $payroll->status === 'paid' ? 'Sudah Dibayar' : 'Dalam Proses' }}
                            </span>
                        </div>
                        <p class="mt-5 text-sm font-medium text-red-100">Total gaji diterima</p>
                        <p class="mt-1 break-words text-3xl font-bold tracking-tight sm:text-4xl">
                            Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}
                        </p>
                        <p class="mt-3 text-sm text-red-100">
                            {{ $emp?->name ?? '-' }} · {{ $emp?->employee_code ?? '-' }}
                        </p>
                        @if ($payroll->status === 'paid' && $payroll->paid_at)
                            <p class="mt-1 text-xs text-red-200">
                                Dibayar pada {{ $payroll->paid_at->translatedFormat('d F Y, H:i') }}
                            </p>
                        @endif
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:flex">
                        <div class="rounded-xl bg-black/10 px-4 py-3 backdrop-blur-sm">
                            <p class="text-xs text-red-100">Hari kerja</p>
                            <p class="mt-1 text-lg font-bold">{{ $payroll->formattedWorkDays() }}</p>
                        </div>
                        <div class="rounded-xl bg-black/10 px-4 py-3 backdrop-blur-sm">
                            <p class="text-xs text-red-100">Kehadiran</p>
                            <p class="mt-1 text-lg font-bold">{{ $attendanceRate }}%</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Left: Employee Info + Attendance Summary --}}
                <div class="space-y-6">

                    {{-- Employee Card --}}
                    <div
                        class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/70 px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.88 17.8M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Data Karyawan</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Informasi penerima slip gaji</p>
                            </div>
                        </div>

                        <div class="space-y-3 p-5">
                            <div class="rounded-xl bg-gray-50 px-3.5 py-3 dark:bg-gray-900/30">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Nama Karyawan</p>
                                <p class="mt-1 font-bold text-gray-900 dark:text-gray-100">{{ $emp?->name ?? '-' }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-gray-100 px-3.5 py-3 dark:border-gray-700">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Kode</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $emp?->employee_code ?? '-' }}</p>
                                </div>
                                <div class="rounded-xl border border-gray-100 px-3.5 py-3 dark:border-gray-700">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Staff</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $emp?->staff ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-100 px-3.5 py-3 dark:border-gray-700">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Jabatan</p>
                                <p class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ $emp?->position ?? '-' }}</p>
                            </div>
                            <div class="rounded-xl border border-blue-100 bg-blue-50/50 px-3.5 py-3 dark:border-blue-900/40 dark:bg-blue-950/20">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-blue-500">Rekening Pembayaran</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $emp?->bank_name ? $emp->bank_name . ' · ' . $emp->bank_account_number : '-' }}
                                </p>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $emp?->bank_account_name ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Attendance Summary --}}
                    <div
                        class="rounded-3xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Ringkasan Kehadiran</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Dasar perhitungan periode ini</p>
                            </div>
                        </div>

                        @include('payrolls.partials.attendance-summary-dates')

                        <div class="mt-4">
                            <div
                                class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>Tingkat Kehadiran</span>
                                <span>{{ $attendanceRate }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="{{ $attendanceWidthClass }} h-2 rounded-full bg-green-500 transition-all">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Right: Salary Breakdown --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Salary Summary Cards --}}
                    <div
                        class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-950/30 dark:text-red-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 10v2m9-6a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Ringkasan Gaji</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Komposisi pendapatan periode {{ $periodLabel }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-px bg-gray-100 sm:grid-cols-3 dark:bg-gray-700">
                            <div class="bg-white p-5 dark:bg-gray-800">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Gaji Pokok</p>
                                <p class="mt-1 font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}</p>
                            </div>
                            <div class="bg-white p-5 dark:bg-gray-800">
                                <p class="text-xs text-green-600 dark:text-green-400">Total Tunjangan</p>
                                <p class="mt-1 font-semibold text-green-700 dark:text-green-400">+ Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</p>
                            </div>
                            <div class="bg-white p-5 dark:bg-gray-800">
                                <p class="text-xs text-red-500 dark:text-red-400">Total Potongan</p>
                                <p class="mt-1 font-semibold text-red-600 dark:text-red-400">- Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Payroll Details Table --}}
                    <div
                        class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div
                            class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/70 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z" />
                                </svg>
                            </span>
                            <div>
                                <h2 class="text-sm font-bold text-gray-900 dark:text-white">Rincian Komponen Gaji</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Detail gaji, tunjangan, dan potongan</p>
                            </div>
                        </div>

                        @php
                            // Exclude 'Total Gross' informatif row
                            $visibleDetails = $payroll->details->filter(fn($d) => !($d->type === 'salary' && $d->name === 'Total Gross'));
                            $salaryRows     = $visibleDetails->where('type', 'salary');
                            $allowanceRows  = $visibleDetails->where('type', 'allowance');
                            $deductionRows  = $visibleDetails->where('type', 'deduction');
                        @endphp

                        @if ($visibleDetails->isEmpty())
                            <div class="px-6 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                                Tidak ada rincian komponen gaji.
                            </div>
                        @else

                        {{-- ── GAJI ─────────────────────────────────────────── --}}
                        <div class="px-4 pt-5 pb-1 sm:px-6">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Gaji</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[18rem] border-collapse">
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                    @foreach ($salaryRows as $detail)
                                        <tr class="bg-white hover:bg-gray-50/50 dark:bg-gray-800 dark:hover:bg-gray-700/30">
                                            <td class="w-1/2 px-4 py-3 text-sm font-medium text-gray-900 sm:px-6 dark:text-gray-100">{{ $detail->name }}</td>
                                            <td class="hidden px-4 py-3 text-sm text-gray-400 sm:table-cell sm:px-6 dark:text-gray-500">{{ $detail->notes ?? '' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-800 sm:px-6 dark:text-gray-200">Rp {{ number_format($detail->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- ── TUNJANGAN ────────────────────────────────────── --}}
                        <div class="border-t border-gray-100 px-4 pt-5 pb-1 sm:px-6 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Tunjangan</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[18rem] border-collapse">
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                    @foreach ($allowanceRows as $detail)
                                        <tr class="bg-white hover:bg-gray-50/50 dark:bg-gray-800 dark:hover:bg-gray-700/30">
                                            <td class="w-1/2 px-4 py-3 text-sm font-medium text-gray-900 sm:px-6 dark:text-gray-100">
                                                {{ $detail->name }}
                                            </td>
                                            <td class="hidden px-4 py-3 text-sm text-gray-400 sm:table-cell sm:px-6 dark:text-gray-500"></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-green-700 sm:px-6 dark:text-green-400">+ Rp {{ number_format($detail->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    @if ($allowanceRows->isEmpty())
                                        <tr><td colspan="3" class="px-4 py-2 text-xs italic text-gray-400 sm:px-6 dark:text-gray-600">Tidak ada tunjangan</td></tr>
                                    @endif
                                </tbody>
                                <tfoot class="border-t border-gray-100 dark:border-gray-700">
                                    <tr class="bg-gray-50/60 dark:bg-gray-700/20">
                                        <td colspan="2" class="px-4 py-2.5 text-xs font-semibold text-gray-500 sm:px-6 dark:text-gray-400">Total Tunjangan</td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm font-bold text-green-700 sm:px-6 dark:text-green-400">+ Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- ── POTONGAN ─────────────────────────────────────── --}}
                        <div class="border-t border-gray-100 px-4 pt-5 pb-1 sm:px-6 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-red-500 dark:text-red-400">Potongan</p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[18rem] border-collapse">
                                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                    @foreach ($deductionRows as $detail)
                                        <tr class="bg-white hover:bg-gray-50/50 dark:bg-gray-800 dark:hover:bg-gray-700/30">
                                            <td class="w-1/2 px-4 py-3 text-sm font-medium text-gray-900 sm:px-6 dark:text-gray-100">
                                                {{ $detail->name }}
                                            </td>
                                            <td class="hidden px-4 py-3 text-sm text-gray-400 sm:table-cell sm:px-6 dark:text-gray-500"></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-red-600 sm:px-6 dark:text-red-400">- Rp {{ number_format($detail->amount, 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                    @if ($deductionRows->isEmpty())
                                        <tr><td colspan="3" class="px-4 py-2 text-xs italic text-gray-400 sm:px-6 dark:text-gray-600">Tidak ada potongan</td></tr>
                                    @endif
                                </tbody>
                                <tfoot class="border-t border-gray-100 dark:border-gray-700">
                                    <tr class="bg-gray-50/60 dark:bg-gray-700/20">
                                        <td colspan="2" class="px-4 py-2.5 text-xs font-semibold text-gray-500 sm:px-6 dark:text-gray-400">Total Potongan</td>
                                        <td class="whitespace-nowrap px-4 py-2.5 text-right text-sm font-bold text-red-600 sm:px-6 dark:text-red-400">- Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        {{-- ── FINAL SUMMARY ────────────────────────────────── --}}
                        <div class="space-y-2 border-t-2 border-gray-200 bg-gradient-to-r from-gray-50 to-emerald-50/50 px-4 py-5 sm:px-6 dark:border-gray-600 dark:from-gray-700/30 dark:to-emerald-950/10">
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Total Gross</span>
                                <span class="shrink-0 font-semibold text-gray-700 dark:text-gray-300">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Take Home Pay</span>
                                <span class="shrink-0 font-semibold text-gray-700 dark:text-gray-300">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                            </div>
                            @if ($payroll->roundingAmount() > 0)
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-xs text-gray-400 dark:text-gray-500">Pembulatan</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">+ Rp {{ number_format($payroll->roundingAmount(), 0, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="flex flex-col gap-1 border-t border-gray-300 pt-3 sm:flex-row sm:items-center sm:justify-between dark:border-gray-600">
                                <span class="text-base font-bold text-gray-900 dark:text-gray-100">Total Gaji Dibayarkan</span>
                                <span class="break-all text-xl font-bold text-green-600 sm:text-2xl dark:text-green-400">Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @endif
                    </div>

                </div>
            </div>

        </div>
    </x-attendance.employee-page>

</x-app-layout>
