{{-- Admin attendance history panel (multi-user), mirrors employee history filters. --}}
@php
    $historyFilters = $historyFilters ?? [];
    $historyStatistics = $historyStatistics ?? [
        'hadir' => 0,
        'telat' => 0,
        'izin' => 0,
        'sakit' => 0,
        'alpha' => 0,
    ];
    $hasHistoryFilter =
        filled($historyFilters['date'] ?? null) ||
        ($historyFilters['has_month'] ?? false) ||
        filled($historyFilters['type'] ?? null) ||
        filled($historyFilters['status'] ?? null) ||
        filled($historyFilters['search'] ?? null);
@endphp

<div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Hadir</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $historyStatistics['hadir'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Telat</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $historyStatistics['telat'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Izin</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $historyStatistics['izin'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Sakit</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $historyStatistics['sakit'] }}</p>
    </div>
    <div class="col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:col-span-1 dark:border-gray-700 dark:bg-gray-800">
        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Alfa</p>
        <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $historyStatistics['alpha'] }}</p>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="border-b border-gray-100 px-4 py-4 sm:px-6 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.attendance.index') }}"
            class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <input type="hidden" name="view" value="history">

            <x-ui.search-input name="search" value="{{ $historyFilters['search'] ?? '' }}"
                placeholder="Cari karyawan... (kosong = semua)" />

            <input type="date" name="date" value="{{ $historyFilters['date'] ?? '' }}"
                class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 sm:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200"
                title="Filter per tanggal (mengalahkan filter bulan)">

            <x-ui.select name="month">
                <option value="">Semua Bulan</option>
                @foreach (range(1, 12) as $monthNumber)
                    @php
                        $monthValue = now()->year . '-' . str_pad($monthNumber, 2, '0', STR_PAD_LEFT);
                        $monthLabel = \Carbon\Carbon::create(now()->year, $monthNumber, 1)->translatedFormat('F Y');
                    @endphp
                    <option value="{{ $monthValue }}" @selected(($historyFilters['month'] ?? null) === $monthValue)>
                        {{ $monthLabel }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="type">
                <option value="">Semua Jenis</option>
                @foreach ($types as $typeOption)
                    <option value="{{ $typeOption->value }}" @selected(($historyFilters['type'] ?? null) === $typeOption->value)>
                        {{ $typeOption->label() }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="status">
                <option value="">Semua Status</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption->value }}" @selected(($historyFilters['status'] ?? null) === $statusOption->value)>
                        {{ $statusOption->label() }}
                    </option>
                @endforeach
            </x-ui.select>

            <x-ui.button type="submit" variant="primary" size="md" class="w-full min-h-11 sm:w-auto">
                Filter
            </x-ui.button>

            @if ($hasHistoryFilter)
                <a href="{{ route('admin.attendance.index', ['view' => 'history']) }}"
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
        </form>
    </div>

    <div class="space-y-3 p-4 md:hidden">
        @forelse ($historyRecords as $row)
            @php $attendance = $row->attendance; @endphp
            <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $row->user->name }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ $attendance?->date?->translatedFormat('d M Y') ?? '—' }}
                            · {{ $attendance?->typeLabel() ?? '—' }}
                        </p>
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
            <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada riwayat untuk filter ini.</p>
        @endforelse
    </div>

    <div class="hidden overflow-x-auto md:block">
        <table class="w-full border-collapse">
            <thead class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-700/50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Nama</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jenis</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Masuk</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Pulang</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($historyRecords as $row)
                    @php $attendance = $row->attendance; @endphp
                    <tr class="bg-white transition hover:bg-gray-50/80 dark:bg-gray-800 dark:hover:bg-gray-700/50">
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row->user->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row->user->email }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-200">
                            {{ $attendance?->date?->translatedFormat('d M Y') ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ $attendance?->typeLabel() ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ $row->clockIn() ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                            {{ $row->clockOut() ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $row->status()->badgeClasses() }}">
                                {{ $row->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
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
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            Tidak ada riwayat untuk filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($historyRecords instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
        <div
            class="flex flex-col gap-3 border-t border-gray-100 px-6 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Menampilkan
                {{ $historyRecords->firstItem() ?? 0 }}–{{ $historyRecords->lastItem() ?? 0 }}
                dari {{ $historyRecords->total() }} absensi
            </p>
            <div>
                {{ $historyRecords->withQueryString()->links() }}
            </div>
        </div>
    @endif
</div>
