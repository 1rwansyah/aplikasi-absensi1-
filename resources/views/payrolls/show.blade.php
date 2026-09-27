<x-app-layout>
    <div class="py-6">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

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
            @endphp

            {{-- Flash --}}
            @if (session('success'))
                <div
                    class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div
                    class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Back + Header --}}
            <div class="flex items-center justify-between gap-4">

                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Detail Penggajian</h1>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                        {{ $months[$payroll->period_month] ?? $payroll->period_month }} {{ $payroll->period_year }}
                        &mdash; {{ $emp?->name ?? '-' }}
                    </p>
                </div>
                <a href="{{ route('payrolls.index') }}"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>

            </div>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Left: Employee Info + Attendance Summary --}}
                <div class="space-y-6">

                    {{-- Employee Card --}}
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2
                            class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Data Karyawan</h2>

                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Nama</p>
                                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $emp?->name ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Kode Karyawan</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $emp?->employee_code ?? '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Jabatan</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $emp?->position ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Staff</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $emp?->staff ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Bank</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $emp?->bank_name ? $emp->bank_name . ' — ' . $emp->bank_account_number : '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 dark:text-gray-500">Atas Nama</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300">
                                    {{ $emp?->bank_account_name ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Attendance Summary --}}
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2
                            class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Ringkasan Kehadiran</h2>

                        @include('payrolls.partials.attendance-summary-dates')

                        <div class="mt-4">
                            <div
                                class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>Tingkat Kehadiran</span>
                                <span>{{ $payroll->work_days > 0 ? round(($payroll->present_days / $payroll->work_days) * 100) : 0 }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-2 rounded-full bg-green-500"
                                    style="width: {{ $payroll->work_days > 0 ? round(($payroll->present_days / $payroll->work_days) * 100) : 0 }}%">
                                </div>
                            </div>
                        </div>

                        @php
                            $spRows = $payroll->details
                                ->filter(fn($d) => $d->is_adjustment && str_starts_with($d->name, 'Potongan SP'));
                        @endphp
                        @if ($spRows->isNotEmpty())
                            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-red-500 dark:text-red-400">Surat Peringatan Aktif</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($spRows as $spRow)
                                        @php
                                            $spLabel = str_replace('Potongan ', '', $spRow->name);
                                            $isSpColor = str_contains($spLabel, 'SP3')
                                                ? 'bg-red-100 text-red-700 ring-red-300 dark:bg-red-900/30 dark:text-red-400 dark:ring-red-700'
                                                : (str_contains($spLabel, 'SP2')
                                                    ? 'bg-orange-100 text-orange-700 ring-orange-300 dark:bg-orange-900/30 dark:text-orange-400 dark:ring-orange-700'
                                                    : 'bg-amber-100 text-amber-700 ring-amber-300 dark:bg-amber-900/30 dark:text-amber-400 dark:ring-amber-700');
                                        @endphp
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $isSpColor }}">
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                                            </svg>
                                            {{ $spLabel }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Status + Action --}}
                    <div
                        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <h2
                            class="mb-4 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Status Pembayaran</h2>

                        @if ($payroll->status === 'paid')
                            <div class="flex items-center gap-3 rounded-xl bg-green-50 px-4 py-3 dark:bg-green-900/20">
                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-semibold text-green-700 dark:text-green-400">Sudah Dibayar
                                    </p>
                                    <p class="text-xs text-green-600 dark:text-green-500">
                                        {{ $payroll->paid_at?->format('d M Y, H:i') }}</p>
                                </div>
                            </div>
                        @else
                            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Pembayaran ini belum ditandai lunas.
                            </p>
                            <form method="POST" action="{{ route('payrolls.mark-paid', $payroll) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-green-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-green-700 dark:bg-gray-800 dark:text-gray-100 dark:border dark:border-gray-700 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Tandai Lunas
                                </button>
                            </form>
                        @endif
                    </div>

                </div>

                {{-- Right: Salary Breakdown --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Salary Summary Cards --}}
                    <div
                        class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                            <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Ringkasan Gaji</h2>
                        </div>
                        {{-- 4 summary cards --}}
                        <div class="grid grid-cols-2 gap-px bg-gray-100 md:grid-cols-4 dark:bg-gray-700">
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
                            <div class="bg-white p-5 dark:bg-gray-800">
                                <p class="text-xs text-green-600 dark:text-green-400">Total Gaji Dibayarkan</p>
                                <p class="mt-1 font-semibold text-green-700 dark:text-green-400">Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}</p>
                            </div>
                        </div>
                        {{-- Calculation summary strip --}}
                        <div class="border-t border-gray-100 bg-gray-50/60 px-6 py-4 dark:border-gray-700 dark:bg-gray-700/30 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Total Gross</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Take Home Pay</span>
                                <span class="font-semibold text-gray-800 dark:text-gray-200">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                            </div>
                            @if ($payroll->rounding_amount > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">Pembulatan (ke atas per Rp 1.000)</span>
                                <span class="text-xs text-green-600 dark:text-green-400">+ Rp {{ number_format($payroll->rounding_amount, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="flex items-center justify-between border-t border-gray-200 pt-3 dark:border-gray-600">
                                <span class="text-base font-bold text-gray-900 dark:text-gray-100">Total Gaji Dibayarkan</span>
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Payroll Details Table --}}
                    <div
                        class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                            <h2
                                class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Rincian Komponen Gaji</h2>
                            @if ($payroll->status !== 'paid')
                                <div class="flex flex-wrap items-center gap-2">
                                    <form method="POST" action="{{ route('payrolls.undo', $payroll) }}">
                                        @csrf
                                        <button type="submit"
                                            @disabled(! $undoableChange)
                                            title="{{ $undoableChange ? 'Batalkan: '.$undoableChange->summary : 'Tidak ada perubahan yang dapat dibatalkan' }}"
                                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-sm font-medium transition
                                                {{ $undoableChange
                                                    ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                                                    : 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-500' }}">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 19l-7-7 7-7" />
                                            </svg>
                                            Undo
                                        </button>
                                    </form>
                                    <button type="button"
                                        onclick="openResetModal()"
                                        @disabled(! $hasSystemSnapshot || ! $hasManualActivity)
                                        title="{{ ! $hasSystemSnapshot ? 'Snapshot sistem belum tersedia' : (! $hasManualActivity ? 'Tidak ada perubahan manual' : 'Kembalikan ke hasil sistem terakhir') }}"
                                        class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-sm font-medium transition
                                            {{ $hasSystemSnapshot && $hasManualActivity
                                                ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700'
                                                : 'cursor-not-allowed border-gray-200 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-500' }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Reset
                                    </button>
                                    <button type="button" onclick="openAdjModal()"
                                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 dark:bg-gray-800 dark:text-gray-100 dark:border dark:border-gray-700 dark:hover:bg-gray-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Tambah
                                    </button>
                                </div>
                                @if ($undoableChange)
                                    <p class="w-full text-right text-xs text-gray-500 dark:text-gray-400">
                                        Aksi terakhir: {{ $undoableChange->summary }}
                                    </p>
                                @endif
                            @endif
                        </div>

                        @php
                            // Exclude 'Total Gross' informatif row — bukan komponen gaji
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
                        <div class="px-6 pt-5 pb-1">
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Gaji</p>
                        </div>
                        <table class="w-full border-collapse">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @foreach ($salaryRows as $detail)
                                    <tr class="bg-white hover:bg-gray-50/50 dark:bg-gray-800 dark:hover:bg-gray-700/30">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 w-1/2">{{ $detail->name }}</td>
                                        <td class="px-6 py-3 text-sm text-gray-400 dark:text-gray-500">{{ $detail->notes ?? '' }}</td>
                                        <td class="px-6 py-3 text-right text-sm font-semibold text-gray-800 dark:text-gray-200 whitespace-nowrap">Rp {{ number_format($detail->amount, 0, ',', '.') }}</td>
                                        <td class="w-10"></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- ── TUNJANGAN ────────────────────────────────────── --}}
                        <div class="px-6 pt-5 pb-1 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">Tunjangan</p>
                        </div>
                        <table class="w-full border-collapse">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @foreach ($allowanceRows as $detail)
                                    <tr class="bg-white hover:bg-gray-50/50 dark:bg-gray-800 dark:hover:bg-gray-700/30">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 w-1/2">
                                            {{ $detail->name }}
                                            @if ($detail->is_adjustment)
                                                <span class="ml-1.5 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">Adj</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-400 dark:text-gray-500">{{ $detail->notes ?? '' }}</td>
                                        <td class="px-6 py-3 text-right text-sm font-semibold text-green-700 dark:text-green-400 whitespace-nowrap">+ Rp {{ number_format($detail->amount, 0, ',', '.') }}</td>
                                        <td class="w-16 px-3 text-right">
                                            @include('payrolls.partials.detail-row-actions', ['detail' => $detail])
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($allowanceRows->isEmpty())
                                    <tr><td colspan="4" class="px-6 py-2 text-xs text-gray-400 dark:text-gray-600 italic">Tidak ada tunjangan</td></tr>
                                @endif
                            </tbody>
                            <tfoot class="border-t border-gray-100 dark:border-gray-700">
                                <tr class="bg-gray-50/60 dark:bg-gray-700/20">
                                    <td colspan="2" class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400">Total Tunjangan</td>
                                    <td class="px-6 py-2.5 text-right text-sm font-bold text-green-700 dark:text-green-400 whitespace-nowrap">+ Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                        {{-- ── POTONGAN ─────────────────────────────────────── --}}
                        <div class="px-6 pt-5 pb-1 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs font-semibold uppercase tracking-wider text-red-500 dark:text-red-400">Potongan</p>
                        </div>
                        <table class="w-full border-collapse">
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/60">
                                @foreach ($deductionRows as $detail)
                                    @php
                                        $isSpLine = $detail->is_adjustment && str_starts_with($detail->name, 'Potongan SP');
                                        $rowBg = $isSpLine
                                            ? 'bg-red-50/40 hover:bg-red-50 dark:bg-red-900/10 dark:hover:bg-red-900/20'
                                            : 'bg-white hover:bg-gray-50/50 dark:bg-gray-800 dark:hover:bg-gray-700/30';
                                    @endphp
                                    <tr class="{{ $rowBg }}">
                                        <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-gray-100 w-1/2">
                                            {{ $detail->name }}
                                            @if ($isSpLine)
                                                @php
                                                    $spBadgeColor = str_contains($detail->name, 'SP3')
                                                        ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                                        : (str_contains($detail->name, 'SP2')
                                                            ? 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'
                                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400');
                                                @endphp
                                                <span class="ml-1.5 rounded-full px-2 py-0.5 text-xs font-semibold {{ $spBadgeColor }}">SP</span>
                                            @elseif ($detail->is_adjustment)
                                                <span class="ml-1.5 rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">Adj</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-sm text-gray-400 dark:text-gray-500">{{ $detail->notes ?? '' }}</td>
                                        <td class="px-6 py-3 text-right text-sm font-semibold text-red-600 dark:text-red-400 whitespace-nowrap">- Rp {{ number_format($detail->amount, 0, ',', '.') }}</td>
                                        <td class="w-16 px-3 text-right">
                                            @include('payrolls.partials.detail-row-actions', ['detail' => $detail])
                                        </td>
                                    </tr>
                                @endforeach
                                @if ($deductionRows->isEmpty())
                                    <tr><td colspan="4" class="px-6 py-2 text-xs text-gray-400 dark:text-gray-600 italic">Tidak ada potongan</td></tr>
                                @endif
                            </tbody>
                            <tfoot class="border-t border-gray-100 dark:border-gray-700">
                                <tr class="bg-gray-50/60 dark:bg-gray-700/20">
                                    <td colspan="2" class="px-6 py-2.5 text-xs font-semibold text-gray-500 dark:text-gray-400">Total Potongan</td>
                                    <td class="px-6 py-2.5 text-right text-sm font-bold text-red-600 dark:text-red-400 whitespace-nowrap">- Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>

                        {{-- ── FINAL SUMMARY ────────────────────────────────── --}}
                        <div class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50/80 dark:bg-gray-700/30 px-6 py-4 space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Total Gross</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Rp {{ number_format($payroll->gross_salary, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Take Home Pay</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300">Rp {{ number_format($payroll->net_salary, 0, ',', '.') }}</span>
                            </div>
                            @if ($payroll->rounding_amount > 0)
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-gray-500">Pembulatan (ke atas per Rp 1.000)</span>
                                <span class="text-xs text-green-600 dark:text-green-400">+ Rp {{ number_format($payroll->rounding_amount, 0, ',', '.') }}</span>
                            </div>
                            @endif
                            <div class="flex items-center justify-between border-t border-gray-300 pt-3 dark:border-gray-600">
                                <span class="text-base font-bold text-gray-900 dark:text-gray-100">Total Gaji Dibayarkan</span>
                                <span class="text-2xl font-bold text-green-600 dark:text-green-400">Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @endif
                    </div>

                </div>
            </div>

        </div>
    </div>

    {{-- Delete Item Confirmation Modal --}}
    <div id="item-delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog"
        aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeItemDeleteModal()"></div>
        <div class="relative w-full max-w-md transform rounded-2xl bg-white shadow-xl transition-all dark:bg-gray-800">
            <div class="flex flex-col items-center px-6 pb-6 pt-8 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-50 dark:bg-gray-700">
                    <svg class="h-7 w-7 text-red-500 dark:text-red-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Hapus Item?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Anda akan menghapus item
                    <span id="item-delete-name" class="font-semibold text-gray-800 dark:text-gray-200"></span>.
                    Total gaji akan dihitung ulang otomatis.
                </p>
            </div>
            <div class="border-t border-gray-100 dark:border-gray-700"></div>
            <div class="flex items-center justify-end gap-3 px-6 py-4">
                <button type="button" onclick="submitItemDelete()"
                    class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 dark:bg-red-900 dark:text-red-100 dark:border dark:border-red-800 dark:hover:bg-red-800">
                    Ya, Hapus
                </button>
                <button type="button" onclick="closeItemDeleteModal()"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Batal
                </button>
            </div>
        </div>
    </div>

    {{-- Edit Item Modal --}}
    <div id="item-edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog"
        aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeItemEditModal()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Edit Item</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $payroll->employee?->name }} &mdash;
                        {{ $months[$payroll->period_month] }} {{ $payroll->period_year }}</p>
                </div>
                <button type="button" onclick="closeItemEditModal()"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="item-edit-form" method="POST" class="px-6 py-5 space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Item
                        <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="item-edit-name"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah (Rp)
                        <span class="text-red-500">*</span></label>
                    <input type="text" name="amount" id="item-edit-amount" inputmode="numeric"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label>
                    <textarea name="notes" id="item-edit-notes" rows="2"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-gray-500 dark:focus:ring-gray-500"></textarea>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <button type="submit"
                        class="rounded-xl bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 dark:bg-gray-800 dark:text-gray-100 dark:border dark:border-gray-700 dark:hover:bg-gray-700">
                        Simpan
                    </button>
                    <button type="button" onclick="closeItemEditModal()"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Adjustment Modal --}}
    <div id="adjustment-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog"
        aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeAdjModal()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Tambah</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $payroll->employee?->name }} &mdash;
                        {{ $months[$payroll->period_month] }} {{ $payroll->period_year }}</p>
                </div>
                <button type="button" onclick="closeAdjModal()"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('payrolls.adjustments.store', $payroll) }}"
                class="px-6 py-5 space-y-4">
                @csrf

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Adjustment
                        <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        placeholder="Contoh: Bonus Project, Potongan SP"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipe <span
                                class="text-red-500">*</span></label>
                        <select name="type"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                            <option value="allowance" @selected(old('type') === 'allowance')>Tunjangan / Bonus</option>
                            <option value="deduction" @selected(old('type') === 'deduction')>Potongan</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah (Rp)
                            <span class="text-red-500">*</span></label>
                        <input type="text" name="amount" value="{{ old('amount') }}" inputmode="numeric"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Catatan</label>
                    <textarea name="notes" rows="2" placeholder="Opsional: keterangan adjustment..."
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-gray-500 dark:focus:ring-gray-500">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <button type="submit"
                        class="rounded-xl bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 dark:bg-gray-800 dark:text-gray-100 dark:border dark:border-gray-700 dark:hover:bg-gray-700">
                        Simpan
                    </button>
                    <button type="button" onclick="closeAdjModal()"
                        class="rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Reset ke Sistem Confirmation Modal --}}
    <div id="reset-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeResetModal()"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl dark:bg-gray-800">
            <div class="flex flex-col items-center px-6 pb-6 pt-8 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-amber-50 dark:bg-gray-700">
                    <svg class="h-7 w-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Reset?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Semua perubahan manual (edit, hapus, tambah) akan dibatalkan dan penggajian dikembalikan ke hasil <strong>Proses Gaji</strong> terakhir.
                </p>
            </div>
            <div class="border-t border-gray-100 dark:border-gray-700"></div>
            <div class="flex items-center justify-end gap-3 px-6 py-4">
                <form method="POST" action="{{ route('payrolls.reset-to-system', $payroll) }}">
                    @csrf
                    <button type="submit"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700">
                        Ya, Reset
                    </button>
                </form>
                <button type="button" onclick="closeResetModal()"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                    Batal
                </button>
            </div>
        </div>
    </div>

    <script>
        let itemDeleteTargetId = null;

        function openItemDeleteModal(id, name) {
            itemDeleteTargetId = id;
            document.getElementById('item-delete-name').textContent = name;
            const modal = document.getElementById('item-delete-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeItemDeleteModal() {
            itemDeleteTargetId = null;
            const modal = document.getElementById('item-delete-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function submitItemDelete() {
            if (!itemDeleteTargetId) return;
            document.getElementById('item-delete-form-' + itemDeleteTargetId).submit();
        }

        function openItemEditModal(id, name, amount, notes, actionUrl) {
            document.getElementById('item-edit-form').action = actionUrl;
            document.getElementById('item-edit-name').value = name;
            document.getElementById('item-edit-amount').value = amount;
            document.getElementById('item-edit-notes').value = notes;
            const modal = document.getElementById('item-edit-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeItemEditModal() {
            const modal = document.getElementById('item-edit-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function openAdjModal() {
            const modal = document.getElementById('adjustment-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeAdjModal() {
            const modal = document.getElementById('adjustment-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function openResetModal() {
            const modal = document.getElementById('reset-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeResetModal() {
            const modal = document.getElementById('reset-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeItemDeleteModal();
                closeItemEditModal();
                closeAdjModal();
                closeResetModal();
            }
        });
    </script>

</x-app-layout>
