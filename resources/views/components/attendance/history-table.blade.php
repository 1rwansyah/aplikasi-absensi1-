@props([
    'records',
    'subtitle' => null,
    'viewAllUrl' => null,
    'showNote' => false,
    'showShift' => false,
    'showHeader' => true,
    'emptyMessage' => 'Belum ada riwayat absensi.',
])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>
    @if ($showHeader)
    <div class="flex flex-col gap-2 border-b border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Riwayat Absensi</h3>
            @if ($subtitle)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
            @endif
        </div>
        @if ($viewAllUrl)
            <a href="{{ $viewAllUrl }}"
                class="inline-flex items-center gap-1 text-sm font-semibold text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                Lihat semua
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        @endif
    </div>
    @endif

    @if ($records->isEmpty())
        <div class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $emptyMessage }}
        </div>
    @else
        <div class="space-y-3 p-4 lg:hidden">
            @foreach ($records as $record)
                <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $record->date->translatedFormat('d M Y') }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $record->typeLabel() }}@if ($showShift && $record->isRegular()) · {{ $record->shiftLabel() }}@endif</p>
                        </div>
                        <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $record->statusBadgeClasses() }}">
                            {{ $record->statusLabel() }}
                        </span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Masuk</dt>
                            <dd class="text-gray-800 dark:text-gray-200">{{ $record->isLeave() ? '—' : ($record->formattedClockIn() ?? '—') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Pulang</dt>
                            <dd class="text-gray-800 dark:text-gray-200">{{ $record->isLeave() ? '—' : ($record->formattedClockOut() ?? '—') }}</dd>
                        </div>
                    </dl>
                    @if ($showNote)
                        @if ($record->isLeave() && $record->leaveNoteText())
                            <div class="mt-2">
                                <x-attendance.note-snippet
                                    :text="$record->leaveNoteText()"
                                    title="Keterangan izin/sakit"
                                    :meta="$record->date->translatedFormat('d M Y').' · '.$record->typeLabel()"
                                />
                            </div>
                        @elseif ($record->clockInReportText())
                            <div class="mt-2">
                                <x-attendance.note-snippet
                                    :text="$record->clockInReportText()"
                                    title="Laporan masuk"
                                    :meta="$record->date->translatedFormat('d M Y').' · '.$record->typeLabel()"
                                />
                            </div>
                        @endif
                    @endif
                </article>
            @endforeach
        </div>

        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full border-collapse">
                <thead class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Tanggal</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Jenis</th>
                        @if ($showShift)
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Jam Kerja</th>
                        @endif
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Masuk</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Pulang</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Lembur</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Status</th>
                        @if ($showNote)
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Keterangan</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($records as $record)
                        <tr class="bg-white hover:bg-gray-50/80 dark:bg-gray-800 dark:hover:bg-gray-700/50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-white">
                                {{ $record->date->translatedFormat('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">{{ $record->typeLabel() }}</td>
                            @if ($showShift)
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $record->isRegular() ? $record->shiftLabel() : '—' }}
                                </td>
                            @endif
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $record->isLeave() ? '—' : ($record->formattedClockIn() ?? '—') }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
                                {{ $record->isLeave() ? '—' : ($record->formattedClockOut() ?? '—') }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if ($record->isRegular() && ($record->overtime_hours ?? 0) > 0)
                                    <span class="font-medium text-amber-600 dark:text-amber-400">
                                        {{ number_format($record->overtime_hours, 2) }} jam
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col items-start gap-1">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $record->statusBadgeClasses() }}">
                                        {{ $record->statusLabel() }}
                                    </span>
                                    @if ($record->validation_status === 'suspicious')
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2 py-0.5 text-[10px] font-semibold text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-200" title="{{ $record->suspicious_reason }}">
                                            Suspicious
                                        </span>
                                    @elseif ($record->validation_status === 'high_risk')
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-200" title="{{ $record->suspicious_reason }}">
                                            High Risk
                                        </span>
                                    @elseif ($record->validation_status === 'pattern_suspicious')
                                        <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-semibold text-purple-800 dark:bg-purple-900/50 dark:text-purple-200" title="{{ $record->suspicious_reason }}">
                                            Pattern Anomaly
                                        </span>
                                    @endif
                                </div>
                            </td>
                            @if ($showNote)
                                <td class="max-w-xs px-6 py-4 text-sm text-gray-600 dark:text-gray-300">
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
                                            <x-attendance.doctor-note-link :attendance="$record" :show-icon="false" class="mt-1 text-xs" />
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
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if (method_exists($records, 'links'))
            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                {{ $records->links() }}
            </div>
        @endif
    @endif
</div>
