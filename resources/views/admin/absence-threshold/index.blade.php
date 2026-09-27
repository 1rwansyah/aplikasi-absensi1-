<x-app-layout>
    <div class="py-6">
        <div class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <section class="relative overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

                <div class="grid gap-5 px-5 py-5 sm:px-6 sm:py-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-700 ring-1 ring-inset ring-red-100 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900/50">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Monitoring SDM
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Alfa &amp; Izin</h1>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Karyawan dengan alfa ≥ {{ $threshold }} atau izin ≥ {{ $threshold }} pada {{ $periodLabel }}. Hitungan dipisah (bukan digabung).
                        </p>
                    </div>

                    <div class="flex flex-col items-stretch gap-2 sm:items-end">
                        <form method="GET" action="{{ route('admin.absence-threshold.index') }}"
                            class="flex flex-wrap items-end gap-2">
                            <div>
                                <label for="filter-month" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-gray-400">Bulan</label>
                                <select id="filter-month" name="month" onchange="this.form.submit()"
                                    class="min-h-11 rounded-2xl border-gray-200 bg-gray-50 text-sm font-semibold text-gray-800 shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    @foreach ($months as $num => $label)
                                        <option value="{{ $num }}" @selected((int) $num === (int) $month)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="filter-year" class="mb-1.5 block text-[10px] font-semibold uppercase tracking-wider text-gray-400">Tahun</label>
                                <select id="filter-year" name="year" onchange="this.form.submit()"
                                    class="min-h-11 rounded-2xl border-gray-200 bg-gray-50 text-sm font-semibold text-gray-800 shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                    @foreach ($years as $y)
                                        <option value="{{ $y }}" @selected((int) $y === (int) $year)>{{ $y }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>

                        <a href="{{ route('admin.absence-threshold.export.excel', ['month' => $month, 'year' => $year]) }}"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 shadow-sm transition hover:bg-emerald-100 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-200 dark:hover:bg-emerald-950/50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3 3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </a>
                    </div>
                </div>
            </section>

            {{-- Summary --}}
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="relative overflow-hidden rounded-[1.5rem] border border-gray-200/80 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-slate-400 to-slate-600"></div>
                    <div class="flex items-start justify-between gap-3 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Kena threshold</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-gray-900 dark:text-white">{{ $summary['total'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Karyawan di {{ $periodLabel }}</p>
                        </div>
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-[1.5rem] border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-white px-5 py-4 shadow-sm dark:border-rose-900/40 dark:from-rose-950/40 dark:via-gray-800 dark:to-gray-800">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-rose-500 to-red-600"></div>
                    <div class="flex items-start justify-between gap-3 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-300">Alfa ≥ {{ $threshold }}</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-rose-700 dark:text-rose-200">{{ $summary['alpha_threshold'] }}</p>
                            <p class="mt-1 text-xs text-rose-500/80 dark:text-rose-300/70">Mencapai batas alfa</p>
                        </div>
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-[1.5rem] border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-white px-5 py-4 shadow-sm dark:border-violet-900/40 dark:from-violet-950/40 dark:via-gray-800 dark:to-gray-800">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-violet-500 to-purple-600"></div>
                    <div class="flex items-start justify-between gap-3 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-300">Izin ≥ {{ $threshold }}</p>
                            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight text-violet-700 dark:text-violet-200">{{ $summary['permission_threshold'] }}</p>
                            <p class="mt-1 text-xs text-violet-500/80 dark:text-violet-300/70">Mencapai batas izin</p>
                        </div>
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-950/60 dark:text-violet-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- List --}}
            <section class="overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                @if (count($rows) === 0)
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-gray-100">
                            Tidak ada karyawan dengan alfa/izin ≥ {{ $threshold }} di bulan ini
                        </p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Coba ganti filter bulan atau tahun untuk melihat periode lain.
                        </p>
                    </div>
                @else
                    {{-- Mobile cards --}}
                    <div class="space-y-3 p-4 md:hidden">
                        @foreach ($rows as $index => $row)
                            <article
                                x-data="{ open: false }"
                                class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $row['employee_code'] }}</p>
                                    </div>
                                    <button type="button" @click="open = !open"
                                        class="inline-flex min-h-9 items-center rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        <span x-text="open ? 'Tutup' : 'Detail'"></span>
                                    </button>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums ring-1 ring-inset',
                                        'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/50' => $row['alpha_count'] >= $threshold,
                                        'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' => $row['alpha_count'] < $threshold,
                                    ])>Alfa {{ $row['alpha_count'] }}</span>
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums ring-1 ring-inset',
                                        'bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/50' => $row['permission_count'] >= $threshold,
                                        'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' => $row['permission_count'] < $threshold,
                                    ])>Izin {{ $row['permission_count'] }}</span>
                                </div>
                                <div x-show="open" x-cloak class="mt-3 space-y-3 border-t border-gray-200/80 pt-3 dark:border-gray-700">
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-300">Tanggal alfa</p>
                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            @forelse ($row['alpha_dates'] as $date)
                                                <span class="rounded-lg bg-rose-100 px-2 py-1 text-[11px] font-semibold tabular-nums text-rose-800 dark:bg-rose-950/50 dark:text-rose-200">
                                                    {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M') }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400">—</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-300">Tanggal izin</p>
                                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                                            @forelse ($row['permission_dates'] as $date)
                                                <span class="rounded-lg bg-violet-100 px-2 py-1 text-[11px] font-semibold tabular-nums text-violet-800 dark:bg-violet-950/50 dark:text-violet-200">
                                                    {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M') }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400">—</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full border-collapse">
                            <thead class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/40">
                                <tr>
                                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Karyawan</th>
                                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Kode</th>
                                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Alfa</th>
                                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Izin</th>
                                    <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Detail</th>
                                </tr>
                            </thead>
                            @foreach ($rows as $index => $row)
                                <tbody x-data="{ open: false }" class="border-b border-gray-100 dark:border-gray-700/80">
                                    <tr class="bg-white dark:bg-gray-800">
                                        <td class="px-5 py-4">
                                            <p class="font-semibold text-gray-900 dark:text-white">{{ $row['name'] }}</p>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span class="rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold tabular-nums text-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                                {{ $row['employee_code'] }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span @class([
                                                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums ring-1 ring-inset',
                                                'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/50' => $row['alpha_count'] >= $threshold,
                                                'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' => $row['alpha_count'] < $threshold,
                                            ])>{{ $row['alpha_count'] }}</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <span @class([
                                                'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold tabular-nums ring-1 ring-inset',
                                                'bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-950/40 dark:text-violet-300 dark:ring-violet-900/50' => $row['permission_count'] >= $threshold,
                                                'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' => $row['permission_count'] < $threshold,
                                            ])>{{ $row['permission_count'] }}</span>
                                        </td>
                                        <td class="px-5 py-4">
                                            <button type="button" @click="open = !open"
                                                class="inline-flex min-h-9 items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 text-xs font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700">
                                                <span x-text="open ? 'Sembunyikan' : 'Lihat tanggal'"></span>
                                                <svg class="h-3.5 w-3.5 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="open" x-cloak class="bg-gray-50/80 dark:bg-gray-900/40">
                                        <td colspan="5" class="px-5 py-4">
                                            <div class="grid gap-4 md:grid-cols-2">
                                                <div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-300">Tanggal alfa</p>
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        @forelse ($row['alpha_dates'] as $date)
                                                            <span class="rounded-lg bg-rose-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-rose-800 dark:bg-rose-950/50 dark:text-rose-200">
                                                                {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}
                                                            </span>
                                                        @empty
                                                            <span class="text-xs text-gray-400">Tidak ada</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-600 dark:text-violet-300">Tanggal izin</p>
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        @forelse ($row['permission_dates'] as $date)
                                                            <span class="rounded-lg bg-violet-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-violet-800 dark:bg-violet-950/50 dark:text-violet-200">
                                                                {{ \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') }}
                                                            </span>
                                                        @empty
                                                            <span class="text-xs text-gray-400">Tidak ada</span>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            @endforeach
                        </table>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
