<x-app-layout>

    <x-attendance.employee-page page-id="attendance-history-page">

        <div class="space-y-6" x-data="attendanceEvidenceViewer">

            @php
                $hasFilter =
                    filled($filters['date'] ?? null) ||
                    ($filters['has_month'] ?? false) ||
                    filled($filters['type'] ?? null) ||
                    filled($filters['status'] ?? null);
                $recordTotal = method_exists($records, 'total') ? $records->total() : $records->count();
            @endphp

            {{-- Page Header --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-red-900/10 bg-gradient-to-br from-red-700 via-red-600 to-rose-600 px-5 py-6 text-white shadow-lg shadow-red-900/10 sm:px-7 sm:py-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-20 left-1/3 h-40 w-40 rounded-full bg-white/5 blur-2xl"></div>

                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-50">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                            Rekam Kehadiran
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Riwayat Absensi</h1>
                        <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-red-50/90">
                            Pantau catatan kehadiran, jam kerja, lembur, serta pengajuan izin dan sakit Anda.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Data ditemukan</p>
                            <p class="mt-0.5 text-xl font-bold">{{ $recordTotal }} <span class="text-xs font-medium text-red-100">catatan</span></p>
                        </div>
                        <a href="{{ route('attendance.index') }}"
                            class="inline-flex min-h-12 items-center justify-center gap-2 rounded-2xl border border-white/20 bg-white px-4 py-3 text-sm font-semibold text-red-700 shadow-sm transition hover:bg-red-50">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                            Absensi
                        </a>
                    </div>
                </div>
            </section>

            {{-- Statistics Cards --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                {{-- Hadir --}}
                <div class="group rounded-2xl border border-gray-200 border-t-2 border-t-emerald-500 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:border-t-emerald-500 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 ring-1 ring-emerald-100 dark:bg-emerald-900/30 dark:ring-emerald-800/50">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Hadir</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $statistics['hadir'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Telat --}}
                <div class="group rounded-2xl border border-gray-200 border-t-2 border-t-amber-500 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:border-t-amber-500 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 ring-1 ring-amber-100 dark:bg-amber-900/30 dark:ring-amber-800/50">
                            <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Telat</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $statistics['telat'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Izin --}}
                <div class="group rounded-2xl border border-gray-200 border-t-2 border-t-violet-500 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:border-t-violet-500 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 ring-1 ring-violet-100 dark:bg-violet-900/30 dark:ring-violet-800/50">
                            <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Izin</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $statistics['izin'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Sakit --}}
                <div class="group rounded-2xl border border-gray-200 border-t-2 border-t-blue-500 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:border-t-blue-500 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 ring-1 ring-blue-100 dark:bg-blue-900/30 dark:ring-blue-800/50">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Sakit</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $statistics['sakit'] }}</p>
                        </div>
                    </div>
                </div>

                {{-- Alpha --}}
                <div class="group col-span-2 rounded-2xl border border-gray-200 border-t-2 border-t-red-500 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:col-span-1 dark:border-gray-700 dark:border-t-red-500 dark:bg-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 ring-1 ring-red-100 dark:bg-red-900/30 dark:ring-red-800/50">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Alpha</p>
                            <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $statistics['alpha'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter + Table Card --}}
            <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Filter Bar --}}
                <div class="border-b border-gray-100 bg-gray-50/70 px-4 py-5 sm:px-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100 dark:bg-red-950/30 dark:text-red-400 dark:ring-red-900/50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14 14v5l-4 2v-7L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Filter Riwayat</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Pilih periode, jenis, atau status absensi.</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('attendance.history') }}"
                        class="grid w-full gap-3 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_1fr_1fr_auto] xl:items-end">

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Tanggal Tertentu</span>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                </svg>
                                <input type="date" name="date" value="{{ $filters['date'] ?? '' }}"
                                    aria-label="Pilih tanggal absensi"
                                    class="min-h-11 w-full rounded-xl border-gray-200 bg-white pl-10 pr-3 text-sm text-gray-700 shadow-sm transition focus:border-red-500 focus:ring-red-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                            </div>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Periode</span>
                            <x-ui.select name="month" class="w-full">
                                <option value="">Semua Bulan</option>
                                @foreach (range(1, 12) as $monthNumber)
                                    @php
                                        $monthValue = now()->year . '-' . str_pad($monthNumber, 2, '0', STR_PAD_LEFT);
                                        $monthLabel = \Carbon\Carbon::create(now()->year, $monthNumber, 1)->translatedFormat('F Y');
                                    @endphp
                                    <option value="{{ $monthValue }}" @selected($filters['month'] === $monthValue)>
                                        {{ $monthLabel }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Jenis Absensi</span>
                            <x-ui.select name="type" class="w-full">
                                <option value="">Semua Jenis</option>
                                @foreach ($types as $typeOption)
                                    <option value="{{ $typeOption->value }}" @selected($filters['type'] === $typeOption->value)>
                                        {{ $typeOption->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Status</span>
                            <x-ui.select name="status" class="w-full">
                                <option value="">Semua Status</option>
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected($filters['status'] === $statusOption->value)>
                                        {{ $statusOption->label() }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </label>

                        <div class="flex gap-2 sm:col-span-2 xl:col-span-1">
                            <x-ui.button type="submit" variant="primary" size="md" class="min-h-11 flex-1 xl:flex-none">
                                Terapkan
                            </x-ui.button>

                            @if ($hasFilter)
                                <a href="{{ route('attendance.history') }}"
                                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="space-y-4 bg-gray-50/50 p-4 lg:hidden dark:bg-gray-900/20">
                    @forelse ($records as $record)
                        <article class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start gap-3 border-b border-gray-100 p-4 dark:border-gray-700">
                                <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-gray-900 text-white dark:bg-gray-700">
                                    <span class="text-lg font-bold leading-none">{{ $record->date->format('d') }}</span>
                                    <span class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-gray-300">
                                        {{ $record->date->translatedFormat('M') }}
                                    </span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        {{ $record->date->translatedFormat('l, Y') }}
                                    </p>
                                    <div class="mt-1 flex flex-wrap items-center gap-2">
                                        <p class="font-bold text-gray-900 dark:text-white">{{ $record->typeLabel() }}</p>
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $record->statusBadgeClasses() }}">
                                            {{ $record->statusLabel() }}
                                        </span>
                                    </div>
                                    @if ($record->isRegular())
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $record->shiftLabel() }}</p>
                                    @endif
                                </div>
                            </div>

                            <div class="p-4">
                                <dl class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-emerald-50/80 px-3 py-2.5 dark:bg-emerald-950/20">
                                        <dt class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            Masuk
                                        </dt>
                                        <dd class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $record->isLeave() ? '—' : ($record->formattedClockIn() ?? '—') }}
                                        </dd>
                                    </div>
                                    <div class="rounded-xl bg-amber-50/80 px-3 py-2.5 dark:bg-amber-950/20">
                                        <dt class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            Pulang
                                        </dt>
                                        <dd class="mt-1 text-sm font-bold text-gray-900 dark:text-white">
                                            {{ $record->isLeave() ? '—' : ($record->formattedClockOut() ?? '—') }}
                                        </dd>
                                    </div>
                                </dl>

                                @if ($record->isRegular() && ($record->overtime_hours ?? 0) > 0)
                                    <div class="mt-3 flex items-center justify-between rounded-xl border border-amber-100 bg-amber-50/50 px-3 py-2 text-xs dark:border-amber-900/40 dark:bg-amber-950/20">
                                        <span class="font-medium text-gray-600 dark:text-gray-300">Total lembur</span>
                                        <span class="font-bold text-amber-700 dark:text-amber-300">{{ number_format($record->overtime_hours, 2) }} jam</span>
                                    </div>
                                @endif

                                @if ($record->isLeave())
                                    <div class="mt-3 flex items-center justify-between rounded-xl border border-gray-100 px-3 py-2.5 dark:border-gray-700">
                                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Verifikasi pengajuan</span>
                                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $record->verification_status->badgeClasses() }}">
                                            {{ $record->verification_status->label() }}
                                        </span>
                                    </div>
                                @endif

                                @if ($record->isLeave() && $record->leaveNoteText())
                                    <div class="mt-3">
                                        <x-attendance.note-snippet
                                            :text="$record->leaveNoteText()"
                                            title="Keterangan izin/sakit"
                                            :meta="$record->date->translatedFormat('d M Y').' · '.$record->typeLabel()"
                                        />
                                    </div>
                                @elseif ($record->clockInReportText())
                                    <div class="mt-3">
                                        <x-attendance.note-snippet
                                            :text="$record->clockInReportText()"
                                            title="Laporan masuk"
                                            :meta="$record->date->translatedFormat('d M Y').' · '.$record->typeLabel()"
                                        />
                                    </div>
                                @endif

                                @if ($record->hasDoctorNote())
                                    <x-attendance.doctor-note-link :attendance="$record" modal
                                        class="mt-3 inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-red-100 bg-red-50 px-4 py-2 text-xs font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-300 dark:hover:bg-red-950/40" />
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="flex flex-col items-center py-12 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                                </svg>
                            </span>
                            <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Riwayat tidak ditemukan</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Coba ubah atau reset filter yang digunakan.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Table --}}
                <div class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[1100px] border-collapse">
                        <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Tanggal</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Jenis</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Jam Kerja</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Masuk</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Pulang</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Lembur</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Verifikasi</th>
                                <th class="px-5 py-4 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($records as $record)
                                <tr class="bg-white transition-colors hover:bg-red-50/30 dark:bg-gray-800 dark:hover:bg-gray-700/40">
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $record->date->translatedFormat('d M Y') }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $record->date->translatedFormat('l') }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $record->typeLabel() }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $record->isRegular() ? $record->shiftLabel() : '—' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm">
                                        <span class="inline-flex min-w-14 items-center justify-center rounded-lg bg-emerald-50 px-2.5 py-1.5 font-semibold text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                            {{ $record->isLeave() ? '—' : ($record->formattedClockIn() ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm">
                                        <span class="inline-flex min-w-14 items-center justify-center rounded-lg bg-amber-50 px-2.5 py-1.5 font-semibold text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                            {{ $record->isLeave() ? '—' : ($record->formattedClockOut() ?? '—') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-sm">
                                        @if ($record->isRegular() && ($record->overtime_hours ?? 0) > 0)
                                            <span class="font-medium text-amber-600 dark:text-amber-400">
                                                {{ number_format($record->overtime_hours, 2) }} jam
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $record->statusBadgeClasses() }}">
                                            {{ $record->statusLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        @if ($record->isLeave())
                                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $record->verification_status->badgeClasses() }}">
                                                {{ $record->verification_status->label() }}
                                            </span>
                                            @if ($record->verification_status->isDone() && $record->verifiedBy)
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    oleh {{ $record->verifiedBy->name }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="max-w-xs px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                        @if ($record->isLeave())
                                            @if ($record->leaveNoteText())
                                                <x-attendance.note-snippet
                                                    :text="$record->leaveNoteText()"
                                                    title="Keterangan izin/sakit"
                                                    :meta="$record->date->translatedFormat('d M Y').' · '.$record->typeLabel()"
                                                />
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                            @if ($record->hasDoctorNote())
                                                <x-attendance.doctor-note-link :attendance="$record" :show-icon="false" modal
                                                    class="mt-1 text-xs dark:text-red-400 dark:hover:text-red-300" />
                                            @endif
                                        @elseif ($record->clockInReportText())
                                            <x-attendance.note-snippet
                                                :text="$record->clockInReportText()"
                                                title="Laporan masuk"
                                                :meta="$record->date->translatedFormat('d M Y').' · '.$record->typeLabel()"
                                            />
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-14 text-center">
                                        <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Riwayat tidak ditemukan</p>
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Coba ubah atau reset filter yang digunakan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($records, 'links') && $records->hasPages())
                    <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Menampilkan {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }}
                            dari {{ $records->total() }} data
                        </p>
                        {{ $records->links() }}
                    </div>
                @endif

            </div>

            <x-attendance.evidence-viewer />

        </div>

    </x-attendance.employee-page>

</x-app-layout>
