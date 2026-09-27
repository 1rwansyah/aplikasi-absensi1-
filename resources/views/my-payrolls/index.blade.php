<x-app-layout>
    <x-attendance.employee-page page-id="my-payrolls-page">
        <div class="space-y-6">

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

            {{-- Page header --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-red-900/10 bg-gradient-to-br from-red-700 via-red-600 to-rose-600 px-5 py-6 text-white shadow-lg shadow-red-900/10 sm:px-7 sm:py-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>
                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-50">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                            Payroll Karyawan
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Slip Gaji</h1>
                        <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-red-50/90">
                            Lihat rincian pendapatan, potongan, status pembayaran, dan unduh slip gaji Anda.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Slip tersedia</p>
                        <p class="mt-0.5 text-xl font-bold">{{ $payrolls->total() }} <span class="text-xs font-medium text-red-100">dokumen</span></p>
                    </div>
                </div>
            </section>

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

            {{-- Filter + Table --}}
            <div
                class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Header + Filter --}}
                <div class="border-b border-gray-100 bg-gray-50/70 px-4 py-5 sm:px-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100 dark:bg-red-950/30 dark:text-red-400 dark:ring-red-900/50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14 14v5l-4 2v-7L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Filter Slip Gaji</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pilih bulan dan tahun periode payroll.</p>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('my-payrolls.index') }}"
                        class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                        <label class="block w-full sm:w-auto">
                            <span class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Bulan</span>
                            <select name="period_month"
                                class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 sm:w-44 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                                <option value="">Semua Bulan</option>
                                @foreach ($months as $num => $label)
                                    @if ($num > 0)
                                        <option value="{{ $num }}" @selected(request('period_month') == $num)>
                                            {{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </label>

                        <label class="block w-full sm:w-auto">
                            <span class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Tahun</span>
                            <input type="number" name="period_year" placeholder="Semua"
                                value="{{ request('period_year') }}" min="2020"
                                class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 sm:w-28 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500">
                        </label>

                        <x-ui.button type="submit" variant="primary" size="md" class="w-full min-h-11 sm:w-auto">
                            Terapkan
                        </x-ui.button>

                        @if (request()->hasAny(['period_month', 'period_year']))
                            <a href="{{ route('my-payrolls.index') }}"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Mobile / tablet cards (< lg) --}}
                <div class="space-y-4 bg-gray-50/50 p-4 lg:hidden dark:bg-gray-900/20">
                    @forelse ($payrolls as $payroll)
                        @php
                            $statusClass =
                                $payroll->status === 'paid'
                                    ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/20 dark:text-green-400 dark:ring-green-500/30'
                                    : 'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-900/20 dark:text-yellow-400 dark:ring-yellow-500/30';
                        @endphp
                        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3 border-b border-gray-100 p-4 dark:border-gray-700">
                                <div class="w-16 shrink-0 overflow-hidden rounded-xl border border-red-200 shadow-sm dark:border-red-800/60">
                                    <span class="block bg-red-600 px-2 py-1 text-center text-[9px] font-bold uppercase tracking-wider text-white">
                                        {{ $payroll->period_year }}
                                    </span>
                                    <span class="flex h-10 items-center justify-center bg-red-50 text-sm font-extrabold uppercase text-red-700 dark:bg-red-950/30 dark:text-red-300">
                                        {{ \Illuminate\Support\Str::limit($months[$payroll->period_month] ?? $payroll->period_month, 3, '') }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Periode Gaji</p>
                                    <p class="mt-1 font-bold text-gray-900 dark:text-gray-100">
                                        {{ $months[$payroll->period_month] ?? $payroll->period_month }}
                                        {{ $payroll->period_year }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $payroll->formattedWorkDays() }} hari kerja</p>
                                </div>
                                <span
                                    class="inline-flex flex-shrink-0 items-center rounded-lg px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                    {{ $payroll->status === 'paid' ? 'Lunas' : 'Draft' }}
                                </span>
                            </div>

                            <div class="p-4">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Take Home Pay</p>
                                <p class="mt-1 break-words text-2xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                                    Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}
                                </p>
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                    <div class="rounded-xl bg-emerald-50 px-3 py-2 dark:bg-emerald-950/20">
                                        <p class="text-emerald-600 dark:text-emerald-400">Tunjangan</p>
                                        <p class="mt-0.5 font-bold text-emerald-700 dark:text-emerald-300">+ Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="rounded-xl bg-red-50 px-3 py-2 dark:bg-red-950/20">
                                        <p class="text-red-500 dark:text-red-400">Potongan</p>
                                        <p class="mt-0.5 font-bold text-red-600 dark:text-red-300">- Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 border-t border-gray-100 p-4 dark:border-gray-700">
                                <a href="{{ route('my-payrolls.show', $payroll) }}"
                                    class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-xl border border-gray-200 px-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0A9 9 0 113 12a9 9 0 0118 0z" />
                                    </svg>
                                    Detail
                                </a>
                                <a href="{{ route('my-payrolls.pdf', $payroll) }}"
                                    class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-3 text-sm font-semibold text-white transition hover:bg-red-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M7 20h10a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Unduh PDF
                                </a>
                            </div>
                        </article>
                    @empty
                        <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                            Belum ada data slip gaji Anda.
                        </p>
                    @endforelse
                </div>

                {{-- Desktop table (lg+) --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[1050px] border-collapse">
                        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Periode</th>
                                <th
                                    class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Hari Kerja</th>
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
                                    class="bg-white transition-colors hover:bg-red-50/30 dark:bg-gray-800 dark:hover:bg-gray-700/40">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ $months[$payroll->period_month] ?? $payroll->period_month }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $payroll->period_year }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-gray-700 dark:text-gray-300">
                                        {{ $payroll->formattedWorkDays() }}</td>
                                    <td class="px-6 py-4 text-right text-sm text-gray-700 dark:text-gray-300">
                                        Rp {{ number_format($payroll->basic_salary, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-green-700 dark:text-green-400">
                                        Rp {{ number_format($payroll->total_allowance, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm text-red-600 dark:text-red-400">
                                        Rp {{ number_format($payroll->total_deduction, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <span class="inline-flex rounded-xl bg-gray-900 px-3 py-2 font-bold text-white shadow-sm dark:bg-gray-700">
                                            Rp {{ number_format($payroll->roundedNetSalary(), 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex items-center rounded-lg px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $statusClass }}">
                                            {{ $payroll->status === 'paid' ? 'Lunas' : 'Draft' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="{{ route('my-payrolls.show', $payroll) }}"
                                                class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 text-xs font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0A9 9 0 113 12a9 9 0 0118 0z" />
                                                </svg>
                                                Detail
                                            </a>
                                            <a href="{{ route('my-payrolls.pdf', $payroll) }}"
                                                class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-red-50 px-3 text-xs font-semibold text-red-600 transition hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                                PDF
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-14 text-center">
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Slip gaji belum tersedia</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dokumen payroll Anda akan tampil setelah diterbitkan.</p>
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
    </x-attendance.employee-page>
</x-app-layout>
