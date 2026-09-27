<x-app-layout>
    <div class="py-6">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- Flash --}}
            <div id="flash-success" class="{{ session('success') ? '' : 'hidden' }} rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400">
                {{ session('success') }}
            </div>
            <div id="flash-error" class="{{ session('error') ? '' : 'hidden' }} rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                {{ session('error') }}
            </div>

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

            {{-- Page header --}}
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Penggajian</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Proses dan kelola penggajian bulanan
                        karyawan.</p>
                </div>
            </div>

            {{-- Summary Cards --}}
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
            @endphp
            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <x-ui.card>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total
                        Karyawan</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                        {{ $summary->total_employees ?? 0 }}</p>
                </x-ui.card>

                <x-ui.card>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Gaji
                        Full Bulanan</p>
                    <p class="mt-2 break-words text-lg font-bold text-gray-900 dark:text-gray-100">Rp
                        {{ number_format($summary->total_gross ?? 0, 0, ',', '.') }}</p>
                </x-ui.card>

                <x-ui.card variant="green">
                    <p class="text-xs font-medium uppercase tracking-wider text-green-600 dark:text-green-400">Total
                        Tunjangan</p>
                    <p class="mt-2 break-words text-lg font-bold text-green-700 dark:text-green-400">Rp
                        {{ number_format($summary->total_allowance ?? 0, 0, ',', '.') }}</p>
                </x-ui.card>

                <x-ui.card variant="red">
                    <p class="text-xs font-medium uppercase tracking-wider text-red-500 dark:text-red-400">Total
                        Potongan</p>
                    <p class="mt-2 break-words text-lg font-bold text-red-600 dark:text-red-400">Rp
                        {{ number_format($summary->total_deduction ?? 0, 0, ',', '.') }}</p>
                </x-ui.card>

                <x-ui.card variant="primary" class="col-span-2 min-w-0 md:col-span-1">
                    <p class="text-xs font-medium uppercase tracking-wider text-red-100 dark:text-gray-400">Total THP
                        Aktual
                    </p>
                    <p class="mt-2 break-words text-lg font-bold text-white dark:text-gray-100">Rp
                        {{ number_format($summary->total_net ?? 0, 0, ',', '.') }}</p>
                </x-ui.card>
            </div>

            {{-- Generate Payroll --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">Proses Gaji Bulanan</h2>
                    <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Hitung gaji semua karyawan aktif untuk
                        periode yang dipilih.</p>
                </div>
                <div class="px-6 py-5">
                    <form method="POST" action="{{ route('payrolls.generate') }}"
                        class="flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-end">
                        @csrf

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Bulan</label>
                            <select name="period_month"
                                class="rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                                @foreach ($months as $num => $label)
                                    @if ($num > 0)
                                        <option value="{{ $num }}" @selected(old('period_month', now()->month) == $num)>
                                            {{ $label }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tahun</label>
                            <input type="number" name="period_year" value="{{ old('period_year', now()->year) }}"
                                min="2020"
                                class="w-28 rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                        </div>

                        <button type="submit"
                            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 sm:w-auto dark:border dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:hover:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Proses Gaji Bulanan
                        </button>
                    </form>
                </div>
            </div>

            {{-- Filter + Table --}}
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Filter Toolbar --}}
                <div class="border-b border-gray-100 px-4 py-4 sm:px-6 dark:border-gray-700">
                    <form method="GET" action="{{ route('payrolls.index') }}" class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
                        id="filter-form">

                        <x-ui.search-input name="search" value="{{ $filters['search'] ?? '' }}"
                            placeholder="Cari karyawan..." />

                        <x-ui.select name="period_month">
                            <option value="">Semua Bulan</option>
                            @foreach ($months as $num => $label)
                                @if ($num > 0)
                                    <option value="{{ $num }}" @selected(request('period_month') == $num)>
                                        {{ $label }}</option>
                                @endif
                            @endforeach
                        </x-ui.select>

                        @if (!empty($availableYears))
                            <x-ui.select name="period_year">
                                <option value="">Semua Tahun</option>
                                @foreach ($availableYears as $year)
                                    <option value="{{ $year }}" @selected(request('period_year') == $year)>
                                        {{ $year }}</option>
                                @endforeach
                            </x-ui.select>
                        @endif

                        <x-ui.select name="status">
                            <option value="">Semua Status</option>
                            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                            <option value="paid" @selected(request('status') === 'paid')>Lunas</option>
                        </x-ui.select>

                        <x-ui.button type="submit" variant="primary" size="md" class="w-full min-h-11 sm:w-auto">
                            Filter
                        </x-ui.button>

                        @if (request()->hasAny(['period_month', 'period_year', 'status', 'search']))
                            <a href="{{ route('payrolls.index') }}"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </a>
                        @endif

                        @php
                            $waMonth = request('period_month');
                            $waYear = request('period_year');
                            $waDisabled = empty($waMonth) || empty($waYear);
                            $exportUrl = route('payrolls.export', request()->only(['period_month', 'period_year', 'status', 'search']));
                        @endphp
                        @if ($waDisabled)
                            <span onclick="showFlash('error', 'Silakan pilih filter bulan dan tahun terlebih dahulu.')" class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border cursor-pointer border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-400 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Download Excel
                            </span>
                        @else
                            <a href="{{ $exportUrl }}"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-green-200 bg-green-50 px-4 py-2 text-sm font-medium text-green-700 shadow-sm transition hover:bg-green-100 sm:w-auto dark:border-green-800 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Download Excel
                            </a>
                        @endif

                        <button type="button"
                            onclick="{{ $waDisabled ? "showFlash('error', 'Silakan pilih filter bulan dan tahun terlebih dahulu.')" : "sendWaReport({$waMonth}, {$waYear})" }}"
                            class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border px-4 py-2 text-sm font-medium shadow-sm transition sm:w-auto {{ $waDisabled ? 'cursor-pointer border-gray-200 bg-gray-50 text-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-600' : 'border-green-200 bg-green-50 text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400 dark:hover:bg-green-900/50' }}">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WA Report
                        </button>
                    </form>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    @forelse ($payrolls as $payroll)
                        @php
                            $statusClass =
                                $payroll->status === 'paid'
                                    ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-500/30'
                                    : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/20 dark:text-yellow-400 dark:ring-yellow-500/30';
                        @endphp
                        <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $payroll->employee?->name ?? '-' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $payroll->employee?->employee_code ?? '-' }} · {{ $months[$payroll->period_month] ?? $payroll->period_month }} {{ $payroll->period_year }}
                                    </p>
                                </div>
                                <span class="flex-shrink-0 rounded-lg px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                    {{ $payroll->status === 'paid' ? 'Lunas' : 'Draft' }}
                                </span>
                            </div>
                            <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">Hadir / Alfa</dt>
                                    <dd class="text-gray-800 dark:text-gray-200">{{ $payroll->present_days }} / {{ $payroll->absent_days }}</dd>
                                </div>
                                <div>
                                    <dt class="text-xs text-gray-500 dark:text-gray-400">THP</dt>
                                    <dd class="break-words font-semibold text-gray-900 dark:text-gray-100">Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}</dd>
                                </div>
                            </dl>
                            <div class="mt-3 flex items-center gap-2">
                                <a href="{{ route('payrolls.show', $payroll) }}"
                                    class="inline-flex h-11 min-w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                    <span class="sr-only">Detail</span>
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                @if ($payroll->status === 'paid')
                                    <a href="{{ route('payrolls.pdf', $payroll) }}"
                                        class="inline-flex h-11 min-w-11 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400">
                                        <span class="sr-only">Download PDF</span>
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                @else
                                    <form method="POST" action="{{ route('payrolls.mark-paid', $payroll) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="inline-flex h-11 min-w-11 items-center justify-center rounded-xl bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400">
                                            <span class="sr-only">Tandai Lunas</span>
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            Belum ada data penggajian. Silahkan buat penggajian terlebih dahulu.
                        </p>
                    @endforelse
                </div>

                {{-- Table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full border-collapse">
                        <thead class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Karyawan</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Periode</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Hari Kerja</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Hadir</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Alfa</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Gaji Pokok</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-green-600 dark:text-green-400">
                                    Tunjangan</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-red-500 dark:text-red-400">
                                    Potongan</th>
                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Take Home Pay</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Status</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($payrolls as $payroll)
                                @php
                                    $statusClass =
                                        $payroll->status === 'paid'
                                            ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-500/30'
                                            : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/20 dark:text-yellow-400 dark:ring-yellow-500/30';
                                @endphp
                                <tr
                                    class="bg-white transition hover:bg-gray-50/80 dark:bg-gray-800 dark:hover:bg-gray-700/50">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $payroll->employee?->name ?? '-' }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $payroll->employee?->employee_code ?? '-' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $months[$payroll->period_month] ?? $payroll->period_month }}
                                        {{ $payroll->period_year }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                                        {{ $payroll->formattedWorkDays() }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                                        {{ $payroll->present_days }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                                        {{ $payroll->absent_days }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">
                                        Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-green-700 dark:text-green-400">
                                        Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-red-600 dark:text-red-400">
                                        Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-gray-100">
                                        Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                            {{ $payroll->status === 'paid' ? 'Lunas' : 'Draft' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-1">

                                            {{-- Detail --}}
                                            <div class="group relative inline-flex">
                                                <a href="{{ route('payrolls.show', $payroll) }}"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </a>
                                                <span
                                                    class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                                                    Detail

                                                    <span
                                                        class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700">
                                                    </span>
                                                </span>
                                            </div>

                                            {{-- Download PDF (only when paid) --}}
                                            @if ($payroll->status === 'paid')
                                                <div class="group relative inline-flex">
                                                    <a href="{{ route('payrolls.pdf', $payroll) }}"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                                                        <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                        </svg>
                                                    </a>
                                                    <span
                                                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                                                        Download&nbsp;PDF

                                                        <span
                                                            class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700">
                                                        </span>
                                                    </span>
                                                </div>
                                            @endif

                                            {{-- Mark Lunas --}}
                                            @if ($payroll->status !== 'paid')
                                                <div class="group relative inline-flex">
                                                    <form method="POST"
                                                        action="{{ route('payrolls.mark-paid', $payroll) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit"
                                                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 transition hover:bg-green-50 hover:text-green-600 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                                                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                    <span
                                                        class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                                                        Tandai&nbsp;Lunas

                                                        <span
                                                            class="absolute left-1/2 top-full -translate-x-1/2 border-4 border-transparent border-t-gray-900 dark:border-t-gray-700">
                                                        </span>
                                                    </span>
                                                </div>
                                            @else
                                                <span
                                                    class="ml-1 text-xs text-gray-400 dark:text-gray-500">{{ $payroll->paid_at?->format('d M Y') }}</span>
                                            @endif

                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11"
                                        class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Belum ada data penggajian. Silahkan buat penggajian terlebih dahulu.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan {{ $payrolls->firstItem() ?? 0 }}–{{ $payrolls->lastItem() ?? 0 }}
                        dari {{ $payrolls->total() }} data
                    </p>
                    {{ $payrolls->links() }}
                </div>
            </div>

        </div>
    </div>

    {{-- WA Report Modal --}}
    <div id="waModal"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none"
        role="dialog" aria-modal="true" aria-labelledby="wa-modal-title">

        <div id="waModalBackdrop"
            class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 opacity-0"
            onclick="closeWaModal()"></div>

        <div class="relative mx-auto w-full max-w-md transform rounded-2xl bg-white shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 dark:bg-gray-800"
            data-modal-content>

            <button type="button" onclick="closeWaModal()"
                class="absolute right-4 top-4 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                aria-label="Tutup">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <div class="flex flex-col items-center px-6 pb-4 pt-8 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/30">
                    <svg class="h-7 w-7 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>

                <h3 id="wa-modal-title" class="text-lg font-bold text-gray-900 dark:text-gray-100">Kirim Laporan Gaji?</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Masukkan nomor WhatsApp tujuan untuk mengirim laporan gaji.
                </p>
            </div>

            <form id="waForm" method="POST" action="{{ route('payrolls.send-wa-report') }}" class="space-y-4 px-6 pb-4">
                @csrf
                <input type="hidden" name="period_month" id="waMonth">
                <input type="hidden" name="period_year" id="waYear">
                <div>
                    <label class="mb-1 block text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nomor WhatsApp</label>
                    <input type="text" name="phone" id="waPhone" placeholder="6281234567890"
                        class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-green-500 focus:ring-green-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                </div>
            </form>

            <div class="border-t border-gray-100 dark:border-gray-700"></div>

            <div class="flex items-center justify-end gap-3 px-6 py-4">
                <x-ui.button type="button" variant="success" size="md" onclick="document.getElementById('waForm').submit()">
                    Ya, Kirim
                </x-ui.button>
                <x-ui.button type="button" variant="secondary" size="md" onclick="closeWaModal()">
                    Batal
                </x-ui.button>
            </div>
        </div>
    </div>

    <script>
        function showFlash(type, message) {
            const el = document.getElementById('flash-' + type);
            el.textContent = message;
            el.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            setTimeout(() => el.classList.add('hidden'), 5000);
        }

        function sendWaReport(month, year) {
            document.getElementById('waMonth').value = month;
            document.getElementById('waYear').value = year;
            const modal = document.getElementById('waModal');
            const backdrop = document.getElementById('waModalBackdrop');
            const content = modal.querySelector('[data-modal-content]');
            modal.style.display = 'flex';
            requestAnimationFrame(() => {
                backdrop.classList.remove('opacity-0');
                content.classList.remove('scale-95', 'opacity-0');
            });
        }

        function closeWaModal() {
            const modal = document.getElementById('waModal');
            const backdrop = document.getElementById('waModalBackdrop');
            const content = modal.querySelector('[data-modal-content]');
            backdrop.classList.add('opacity-0');
            content.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
    </script>

</x-app-layout>
