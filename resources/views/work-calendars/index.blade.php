<x-app-layout>
    @php
        $totalWorkDays = collect($months)->sum('work_days');
        $totalHolidays = collect($months)->sum('holiday');
        $totalHalfDays = collect($months)->sum('half_day');
        $totalFullDays = collect($months)->sum('full_day');
        $workDaysLabel = rtrim(rtrim(number_format($totalWorkDays, 1), '0'), '.');
    @endphp

    <div class="py-6">
        <div class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <section class="relative overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

                <div class="grid gap-5 px-5 py-5 sm:px-6 sm:py-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-700 ring-1 ring-inset ring-red-100 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900/50">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Pengaturan Sistem
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Kalender Kerja</h1>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Kelola hari kerja, setengah hari, dan libur nasional untuk tahun {{ $year }}. Klik tanggal untuk mengubah tipe hari.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-3.5 py-3 dark:border-gray-700 dark:bg-gray-900/50">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Hari kerja</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $workDaysLabel }}</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 px-3.5 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/30">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-300">Full day</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-emerald-800 dark:text-emerald-200">{{ $totalFullDays }}</p>
                        </div>
                        <div class="rounded-2xl border border-amber-100 bg-amber-50/80 px-3.5 py-3 dark:border-amber-900/40 dark:bg-amber-950/30">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-300">Half day</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-amber-800 dark:text-amber-200">{{ $totalHalfDays }}</p>
                        </div>
                        <div class="rounded-2xl border border-rose-100 bg-rose-50/80 px-3.5 py-3 dark:border-rose-900/40 dark:bg-rose-950/30">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-300">Libur</p>
                            <p class="mt-1 text-xl font-bold tabular-nums text-rose-800 dark:text-rose-200">{{ $totalHolidays }}</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Toolbar --}}
            <section class="flex flex-col gap-3 rounded-[1.75rem] border border-gray-200/80 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <form method="GET" action="{{ route('work-calendars.index') }}" class="flex items-center gap-3">
                    <label for="calendar-year" class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tahun</label>
                    <select id="calendar-year" name="year" onchange="this.form.submit()"
                        class="min-h-11 rounded-2xl border-gray-200 bg-gray-50 text-sm font-semibold text-gray-800 shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @foreach (range(now()->year - 1, now()->year + 2) as $y)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    @if ($yearGenerated)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/50">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Kalender {{ $year }} sudah dibuat
                        </span>

                        <form method="POST" action="{{ route('work-calendars.sync-holidays') }}">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit"
                                class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-red-800 dark:hover:bg-red-950/30 dark:hover:text-red-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                Sync Ulang Hari Libur
                                @if ($lastImport)
                                    <span class="hidden text-xs font-medium text-gray-400 sm:inline">({{ $lastImport->synced_at->diffForHumans() }})</span>
                                @endif
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('work-calendars.generate') }}">
                            @csrf
                            <input type="hidden" name="year" value="{{ $year }}">
                            <button type="submit"
                                class="inline-flex min-h-11 items-center gap-2 rounded-2xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-900/10 transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Buat Kalender {{ $year }}
                            </button>
                        </form>
                    @endif
                </div>
            </section>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200">
                    {{ session('error') }}
                </div>
            @endif

            @if ($yearGenerated)
                {{-- Legend --}}
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/50">
                        <span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span> Full Day
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/50" title="Half Day = 1 hari kerja payroll, dengan jam kerja lebih pendek">
                        <span class="h-2.5 w-2.5 rounded-sm bg-amber-500"></span> Half Day <span class="font-medium text-amber-600/70 dark:text-amber-300/70">(1 HK)</span>
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/50">
                        <span class="h-2.5 w-2.5 rounded-sm bg-rose-500"></span> Libur
                    </span>
                    <span class="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1.5 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/50">
                        <span class="h-2 w-2 rounded-full bg-sky-500"></span> Override manual
                    </span>
                </div>
            @endif

            @if ($yearGenerated)
                {{-- Calendar Grid --}}
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($months as $monthNum => $month)
                        @php
                            $monthWorkDays = rtrim(rtrim(number_format($month['work_days'], 1), '0'), '.');
                            $isCurrentMonth = (int) $monthNum === (int) now()->month && (int) $year === (int) now()->year;
                        @endphp
                        <article @class([
                            'overflow-hidden rounded-[1.5rem] border bg-white shadow-sm transition hover:shadow-md dark:bg-gray-800',
                            'border-red-200 ring-1 ring-red-100 dark:border-red-900/50 dark:ring-red-900/30' => $isCurrentMonth,
                            'border-gray-200/80 dark:border-gray-700' => ! $isCurrentMonth,
                        ])>
                            <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-3.5 dark:border-gray-700">
                                <div>
                                    <h2 class="text-sm font-bold tracking-tight text-gray-900 dark:text-white">
                                        {{ $month['name'] }}
                                        <span class="font-semibold text-gray-400 dark:text-gray-500">{{ $year }}</span>
                                    </h2>
                                    @if ($isCurrentMonth)
                                        <p class="mt-0.5 text-[11px] font-semibold uppercase tracking-wide text-red-600 dark:text-red-300">Bulan ini</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 text-[11px] font-semibold">
                                    <span class="rounded-full bg-gray-100 px-2 py-1 tabular-nums text-gray-700 dark:bg-gray-900 dark:text-gray-200">{{ $monthWorkDays }} HK</span>
                                    <span class="rounded-full bg-rose-50 px-2 py-1 tabular-nums text-rose-700 dark:bg-rose-950/40 dark:text-rose-300">{{ $month['holiday'] }} libur</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-7 border-b border-gray-100 bg-gray-50/80 px-1 dark:border-gray-700 dark:bg-gray-900/40">
                                @foreach (['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $index => $dayLabel)
                                    <div @class([
                                        'py-2 text-center text-[10px] font-bold uppercase tracking-wide',
                                        'text-rose-500 dark:text-rose-400' => $index === 6,
                                        'text-gray-400 dark:text-gray-500' => $index !== 6,
                                    ])>{{ $dayLabel }}</div>
                                @endforeach
                            </div>

                            <div class="grid grid-cols-7 gap-px bg-gray-100/80 p-px dark:bg-gray-700/80">
                                @foreach ($month['cells'] as $day)
                                    @if ($day === null)
                                        <div class="min-h-[3.25rem] bg-white dark:bg-gray-800"></div>
                                    @else
                                        @php
                                            $isToday = $day->date->isToday();
                                            $isSunday = $day->date->isSunday();
                                            $tone = match ($day->type->value) {
                                                'full_day' => [
                                                    'cell' => 'bg-emerald-50/90 text-emerald-800 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-200 dark:hover:bg-emerald-900/40',
                                                    'dot' => 'bg-emerald-500',
                                                ],
                                                'half_day' => [
                                                    'cell' => 'bg-amber-50/90 text-amber-800 hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-200 dark:hover:bg-amber-900/40',
                                                    'dot' => 'bg-amber-500',
                                                ],
                                                'holiday' => [
                                                    'cell' => 'bg-rose-50/90 text-rose-800 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-200 dark:hover:bg-rose-900/40',
                                                    'dot' => 'bg-rose-500',
                                                ],
                                                default => [
                                                    'cell' => 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300',
                                                    'dot' => 'bg-gray-300',
                                                ],
                                            };
                                        @endphp
                                        <button type="button"
                                            class="relative flex min-h-[3.25rem] flex-col items-center justify-center gap-0.5 px-1 py-1.5 text-xs font-semibold transition {{ $tone['cell'] }}"
                                            data-calendar-date="{{ $day->date->format('Y-m-d') }}"
                                            data-calendar-date-label="{{ $day->date->translatedFormat('l, d F Y') }}"
                                            data-calendar-type="{{ $day->type->value }}"
                                            data-calendar-name="{{ $day->name ?? '' }}"
                                            data-calendar-note="{{ $day->note ?? '' }}"
                                            data-update-url="{{ route('work-calendars.update', $day) }}"
                                            title="{{ $day->date->format('d M') }}{{ $day->name ? ' — '.$day->name : '' }}">
                                            <span @class([
                                                'flex h-7 w-7 items-center justify-center rounded-full tabular-nums',
                                                'bg-red-600 font-bold text-white shadow-sm shadow-red-900/20' => $isToday,
                                                'text-rose-600 dark:text-rose-300' => ! $isToday && $isSunday && $day->type->value !== 'holiday',
                                            ])>{{ $day->date->format('j') }}</span>

                                            @if ($day->name)
                                                <span class="max-w-full truncate px-0.5 text-[9px] font-medium leading-tight opacity-70">{{ $day->name }}</span>
                                            @else
                                                <span class="h-1 w-1 rounded-full {{ $tone['dot'] }} opacity-70"></span>
                                            @endif

                                            @if ($day->is_manual_override)
                                                <span class="absolute right-1 top-1 h-1.5 w-1.5 rounded-full bg-sky-500 ring-2 ring-white dark:ring-gray-800"></span>
                                            @endif
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-[1.75rem] border border-dashed border-gray-200 bg-white px-6 py-12 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-gray-800 dark:text-gray-100">Kalender {{ $year }} belum dibuat</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Buat kalender terlebih dahulu, lalu sinkronkan hari libur nasional.</p>
                </div>
            @endif
        </div>
    </div>

    @include('work-calendars.partials.edit-modal')
</x-app-layout>
