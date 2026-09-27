<x-app-layout>
    <x-admin.leaflet-assets />

    <div class="py-6"
        data-open-manual-attendance-modal="{{ ($openManualAttendanceModal ?? false) ? 'true' : 'false' }}"
        data-open-manual-edit-attendance-modal="{{ ($openManualEditAttendanceModal ?? false) ? 'true' : 'false' }}">
        <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ ($view ?? 'monitoring') === 'history' ? 'Riwayat Absensi' : 'Monitoring Absensi' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if (($view ?? 'monitoring') === 'history')
                        Semua catatan absensi karyawan — filter bulan, status, dan cari nama.
                    @else
                        Rekap kehadiran karyawan — wajah, lokasi, dan waktu.
                    @endif
                </p>
            </div>

            {{-- Tabs --}}
            <div class="flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800/80">
                <a href="{{ route('admin.attendance.index') }}"
                    class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition {{ ($view ?? 'monitoring') === 'monitoring' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    Monitoring
                </a>
                <a href="{{ route('admin.attendance.index', ['view' => 'history']) }}"
                    class="flex-1 rounded-lg px-4 py-2.5 text-center text-sm font-medium transition {{ ($view ?? 'monitoring') === 'history' ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                    Riwayat
                </a>
            </div>

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

            @if (($view ?? 'monitoring') === 'history')
                @include('admin.attendance.partials.history-panel')
            @else

            {{-- Holiday Banner --}}
            @if ($isHoliday)
                <div
                    class="flex items-start gap-4 rounded-2xl border border-amber-200 bg-amber-50 px-6 py-4 shadow-sm dark:border-amber-700/50 dark:bg-amber-900/20">
                    <div
                        class="mt-0.5 flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-800/40">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Hari Libur &mdash;
                            {{ $holidayName }}</p>
                        <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                            @if ($hasHolidayRecords)
                                Terdapat {{ $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $rows->total() : $rows->count() }} karyawan yang tetap hadir pada hari libur ini.
                            @else
                                Tidak ada jadwal absensi umum. Tidak ada karyawan yang tercatat hadir.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            {{-- Summary Widgets --}}
            @if (!$isHoliday || $hasHolidayRecords)
                @php
                    $cards = [
                        [
                            'label' => 'Total Karyawan',
                            'value' => $summary['total'],
                            'color' => 'text-gray-900 dark:text-gray-100',
                            'filter' => null,
                            'border' => 'border-gray-100 dark:border-gray-700',
                            'bg' => 'bg-white dark:bg-gray-800',
                        ],
                        [
                            'label' => 'Hadir',
                            'value' => $summary['hadir'],
                            'color' => 'text-green-700 dark:text-green-400',
                            'filter' => 'hadir',
                            'border' => 'border-green-100 dark:border-green-900/40',
                            'bg' => 'bg-green-50/80 dark:bg-green-900/20',
                        ],
                        [
                            'label' => 'Telat',
                            'value' => $summary['telat'],
                            'color' => 'text-amber-700 dark:text-amber-400',
                            'filter' => 'telat',
                            'border' => 'border-amber-100 dark:border-amber-900/40',
                            'bg' => 'bg-amber-50/80 dark:bg-amber-900/20',
                        ],
                        [
                            'label' => 'Alfa',
                            'value' => $summary['alfa'],
                            'color' => 'text-red-700 dark:text-red-400',
                            'filter' => 'alfa',
                            'border' => 'border-red-100 dark:border-red-900/40',
                            'bg' => 'bg-red-50/80 dark:bg-red-900/20',
                        ],
                        [
                            'label' => 'Sakit',
                            'value' => $summary['sakit'],
                            'color' => 'text-blue-700 dark:text-blue-400',
                            'filter' => 'sakit',
                            'border' => 'border-blue-100 dark:border-blue-900/40',
                            'bg' => 'bg-blue-50/80 dark:bg-blue-900/20',
                        ],
                        [
                            'label' => 'Izin',
                            'value' => $summary['izin'],
                            'color' => 'text-purple-700 dark:text-purple-400',
                            'filter' => 'izin',
                            'border' => 'border-purple-100 dark:border-purple-900/40',
                            'bg' => 'bg-purple-50/80 dark:bg-purple-900/20',
                        ],
                    ];
                @endphp
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @foreach ($cards as $card)
                        @if ($card['filter'])
                            <a href="{{ route('admin.attendance.index', ['date' => $date->toDateString(), 'status' => $card['filter']]) }}"
                                class="rounded-2xl border p-4 shadow-sm transition hover:shadow-md {{ $card['border'] }} {{ $card['bg'] }}">
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</p>
                            </a>
                        @else
                            <div class="rounded-2xl border p-4 shadow-sm {{ $card['border'] }} {{ $card['bg'] }}">
                                <p
                                    class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ $card['label'] }}</p>
                                <p class="mt-2 text-2xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif {{-- end !$isHoliday || $hasHolidayRecords --}}

            {{-- Date + Filter Toolbar (always visible) --}}
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 px-4 py-4 sm:px-6 dark:border-gray-700">
                    <form method="GET" action="{{ route('admin.attendance.index') }}"
                        class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center" id="filter-form">

                        <x-ui.search-input name="search" value="{{ $search ?? '' }}" placeholder="Cari karyawan..." />

                        <input type="date" name="date" value="{{ $date->toDateString() }}"
                            class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 sm:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">

                        @if (!$isHoliday)
                            <x-ui.select name="status">
                                <option value="">Semua Status</option>
                                <option value="hadir" @selected($filter === 'hadir')>Hadir</option>
                                <option value="telat" @selected($filter === 'telat')>Telat</option>
                                <option value="alfa" @selected($filter === 'alfa')>Alfa</option>
                                <option value="sakit" @selected($filter === 'sakit')>Sakit</option>
                                <option value="izin" @selected($filter === 'izin')>Izin</option>
                            </x-ui.select>
                        @endif

                        <x-ui.button type="submit" variant="primary" size="md" class="w-full min-h-11 sm:w-auto">
                            Filter
                        </x-ui.button>

                        @if ($search || $filter)
                            <a href="{{ route('admin.attendance.index', ['date' => $date->toDateString()]) }}"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </a>
                        @endif

                        <x-ui.button type="button" variant="secondary" size="md" class="w-full min-h-11 sm:ml-auto sm:w-auto"
                            data-action="open-manual-attendance-create">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Absensi
                        </x-ui.button>

                        <x-ui.button type="button" variant="primary" size="md" class="w-full min-h-11 sm:w-auto" onclick="openWhatsappReportModal()">
                            Kirim Rekap WA
                        </x-ui.button>
                    </form>
                </div>

                {{-- Holiday empty state (no records) --}}
                @if ($isHoliday && !$hasHolidayRecords)
                    <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                        <div
                            class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                            <svg class="h-7 w-7 text-amber-500 dark:text-amber-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Hari Libur &mdash; Tidak
                                ada jadwal absensi umum</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $holidayName }} &bull; Tidak
                                ada karyawan yang tercatat hadir hari ini.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Legend Anomali --}}
            <div
                class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Keterangan Peringatan Anomali Lokasi</h4>
                <div class="flex flex-wrap gap-x-6 gap-y-3 text-sm">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-semibold text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200">Suspicious</span>
                        <span class="text-gray-600 dark:text-gray-400">Akurasi GPS terdeteksi buruk (>100 meter)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-200">High
                            Risk</span>
                        <span class="text-gray-600 dark:text-gray-400">Akurasi buruk & posisinya pas di pinggir batas
                            radius</span>
                    </div>
                    {{-- Fitur dimatikan sementara
                    <div class="flex items-center gap-2">
                        <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-semibold text-purple-800 dark:bg-purple-900/50 dark:text-purple-200">Pattern Anomaly</span>
                        <span class="text-gray-600 dark:text-gray-400">Koordinat terlalu identik dengan user lain (indikasi pakai Fake GPS yang sama)</span>
                    </div>
                    --}}
                </div>
            </div>

            {{-- Table Card (only when there are rows to show) --}}
            @if (!$isHoliday || $hasHolidayRecords)
                <div
                    class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="space-y-3 p-4 md:hidden">
                        @forelse ($rows as $row)
                            @php $attendance = $row->attendance; @endphp
                            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $row->user->name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">{{ $row->user->email }}</p>
                                    </div>
                                    <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $row->status()->badgeClasses() }}">
                                        {{ $row->statusLabel() }}
                                    </span>
                                </div>
                                <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Masuk</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $row->clockIn() ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Pulang</dt>
                                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $row->clockOut() ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Lembur</dt>
                                        <dd class="text-gray-800 dark:text-gray-200">
                                            @if ($attendance?->isRegular() && ($attendance->overtime_hours ?? 0) > 0)
                                                {{ number_format($attendance->overtime_hours, 2) }} jam
                                            @else
                                                —
                                            @endif
                                        </dd>
                                    </div>
                                </dl>
                                <div class="mt-3 flex items-center justify-end">
                                    @include('admin.attendance.partials.row-actions', [
                                        'row' => $row,
                                        'attendance' => $attendance,
                                        'payrollLockedAttendanceIds' => $payrollLockedAttendanceIds,
                                        'compact' => false,
                                    ])
                                </div>
                            </article>
                        @empty
                            <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if ($isHoliday)
                                    Tidak ada karyawan yang cocok dengan filter ini.
                                @else
                                    Tidak ada data untuk filter ini.
                                @endif
                            </p>
                        @endforelse
                    </div>

                    {{-- Table --}}
                    <div class="hidden overflow-x-auto md:block">
                        <table class="w-full border-collapse">
                            <thead
                                class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-700/50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Karyawan</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Masuk</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Pulang</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Lembur</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Status</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Verifikasi</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($rows as $row)
                                    @php $attendance = $row->attendance; @endphp
                                    <tr
                                        class="bg-white hover:bg-gray-50/80 dark:bg-gray-800 dark:hover:bg-gray-700/50 {{ $isHoliday ? 'ring-inset' : '' }}">
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                                {{ $row->user->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $row->user->email }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                                {{ $row->clockIn() ?? '—' }}
                                            </div>
                                            @if ($attendance?->clockInLocationLabel())
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2 max-w-[200px]"
                                                    title="{{ $attendance->clockInLocationLabel() }}">
                                                    {{ $attendance->clockInLocationLabel() }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                                {{ $row->clockOut() ?? '—' }}
                                            </div>
                                            @if ($attendance?->clockOutLocationLabel())
                                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2 max-w-[200px]"
                                                    title="{{ $attendance->clockOutLocationLabel() }}">
                                                    {{ $attendance->clockOutLocationLabel() }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm">
                                            @if ($attendance?->isRegular() && ($attendance->overtime_hours ?? 0) > 0)
                                                <span class="font-medium text-amber-600 dark:text-amber-400">
                                                    {{ number_format($attendance->overtime_hours, 2) }} jam
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col items-start gap-1">
                                                <span
                                                    class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $row->status()->badgeClasses() }}">
                                                    {{ $row->statusLabel() }}
                                                </span>
                                                @if ($attendance && $attendance->validation_status === 'suspicious')
                                                    <span
                                                        class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-semibold text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200"
                                                        title="{{ $attendance->suspicious_reason }}">
                                                        Suspicious
                                                    </span>
                                                @elseif ($attendance && $attendance->validation_status === 'high_risk')
                                                    <span
                                                        class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-200"
                                                        title="{{ $attendance->suspicious_reason }}">
                                                        High Risk
                                                    </span>
                                                @elseif ($attendance && $attendance->validation_status === 'pattern_suspicious')
                                                    <span
                                                        class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-semibold text-purple-800 dark:bg-purple-900/50 dark:text-purple-200"
                                                        title="{{ $attendance->suspicious_reason }}">
                                                        Pattern Anomaly
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($attendance?->isRegular())
                                                <div class="flex flex-col gap-2">
                                                    <div class="flex items-center gap-2">
                                                        @if ($attendance->clockInVerificationPhotoUrl())
                                                            <img src="{{ $attendance->clockInVerificationPhotoUrl() }}"
                                                                alt="Foto masuk"
                                                                class="h-10 w-10 rounded-lg border border-gray-200 object-cover dark:border-gray-600">
                                                        @endif
                                                        @if ($attendance->clockOutVerificationPhotoUrl())
                                                            <img src="{{ $attendance->clockOutVerificationPhotoUrl() }}"
                                                                alt="Foto pulang"
                                                                class="h-10 w-10 rounded-lg border border-gray-200 object-cover dark:border-gray-600">
                                                        @endif
                                                        @if (!$attendance->clockInVerificationPhotoUrl() && !$attendance->clockOutVerificationPhotoUrl())
                                                            <span class="text-xs text-gray-400">—</span>
                                                        @endif
                                                    </div>
                                                    <div class="flex flex-wrap gap-1.5">
                                                        @if ($attendance->clock_in_face_distance !== null)
                                                            <x-attendance.face-match-badge :percent="$attendance->clockInFaceMatchPercent()"
                                                                label="Masuk" />
                                                        @endif
                                                        @if ($attendance->clock_out_face_distance !== null)
                                                            <x-attendance.face-match-badge :percent="$attendance->clockOutFaceMatchPercent()"
                                                                label="Pulang" />
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4">
                                            @include('admin.attendance.partials.row-actions', [
                                                'row' => $row,
                                                'attendance' => $attendance,
                                                'payrollLockedAttendanceIds' => $payrollLockedAttendanceIds,
                                                'compact' => true,
                                            ])
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                            @if ($isHoliday)
                                                Tidak ada karyawan yang cocok dengan filter ini.
                                            @else
                                                Tidak ada data untuk filter ini.
                                            @endif
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                        {{-- Footer --}}
                        <div
                            class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                            <div class="space-y-1">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Menampilkan
                                    {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}
                                    dari {{ $rows->total() }} karyawan
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Tanggal: {{ $date->translatedFormat('l, d F Y') }}
                                </p>
                            </div>

                            <div>
                                {{ $rows->withQueryString()->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            @endif {{-- end !$isHoliday || $hasHolidayRecords --}}

            @endif {{-- end monitoring view --}}

        </div>
    </div>

    @include('admin.attendance.partials.whatsapp-report-modal')
    @include('admin.attendance.partials.location-map-modal', ['geofence' => $geofence])
    @include('admin.attendance.partials.create-modal')
    @include('admin.attendance.partials.edit-modal')

    <div id="admin-attendance-detail-modal"
        class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
        <div
            class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                <div>
                    <h3 id="admin-attendance-detail-name" class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    </h3>
                    <p id="admin-attendance-detail-status" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
                </div>
                <button type="button" data-admin-detail-close
                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                    <span class="sr-only">Tutup</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div id="admin-attendance-detail-body" class="space-y-4 p-6 text-sm text-gray-700 dark:text-gray-300">
            </div>
        </div>
    </div>
</x-app-layout>
