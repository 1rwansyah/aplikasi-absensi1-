@props(['attendance', 'pendingClockOut' => null])

@php
    use App\Enums\AttendanceStatus;
@endphp

<x-attendance.attendance-card>
    @if ($attendance)
        @if ($attendance->isLeave())
            <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-blue-50/60 dark:from-gray-800 dark:via-gray-800 dark:to-blue-950/15">
                <div class="pointer-events-none absolute -right-12 -top-16 h-36 w-36 rounded-full border-[22px] border-blue-100/40 dark:border-blue-900/10"></div>

                <div class="relative flex flex-col gap-5 px-5 py-6 sm:flex-row sm:items-start sm:px-7 sm:py-7">
                    <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-600/20">
                        @if ($attendance->type === \App\Enums\AttendanceType::Sick)
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m7-3a7 7 0 11-14 0 7 7 0 0114 0Z" />
                            </svg>
                        @else
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l3 3v15H7V3Zm3 7h4m-4 4h4" />
                            </svg>
                        @endif
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold tracking-tight text-gray-900 sm:text-xl dark:text-gray-100">
                                Pengajuan {{ $attendance->typeLabel() }}
                            </h2>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $attendance->verification_status->badgeClasses() }}">
                                @if ($attendance->verification_status->isPending())
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                @endif
                                {{ $attendance->verification_status->label() }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs font-medium capitalize text-gray-500 dark:text-gray-400">
                            {{ $attendance->date->translatedFormat('l, d F Y') }}
                        </p>

                        @if ($attendance->leaveNoteText())
                            <div class="mt-4 rounded-2xl border border-gray-200/80 bg-white/80 p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/30">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">
                                    Keterangan
                                </p>
                                <p class="mt-1.5 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-200">
                                    {{ trim($attendance->leaveNoteText()) }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($attendance->hasDoctorNote())
                    <div class="relative border-t border-blue-100/70 bg-blue-50/40 px-5 py-4 sm:px-7 dark:border-gray-700 dark:bg-gray-900/20">
                        <x-attendance.doctor-note-link :attendance="$attendance" modal
                            class="inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm transition hover:border-blue-300 hover:bg-blue-50 sm:w-auto dark:border-blue-900 dark:bg-gray-800 dark:text-blue-300 dark:hover:bg-blue-950/20" />
                    </div>
                @endif
            </div>
        @else
            @php
                $inBadge = $attendance->clock_in_time && $attendance->isRegular()
                    ? ['text' => $attendance->status->label(), 'class' => $attendance->status->badgeClasses()]
                    : null;
                $outBadge = null;
                if ($attendance->clock_out_time) {
                    $outBadge = $attendance->status === AttendanceStatus::LateOut
                        ? ['text' => $attendance->status->label(), 'class' => $attendance->status->badgeClasses()]
                        : ['text' => 'Selesai', 'class' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300'];
                }
            @endphp
            <div class="border-b border-gray-100 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-900/20">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-800 dark:text-white">Ringkasan Absensi Hari Ini</span>
                    @if ($attendance->clock_in_time && $attendance->isRegular())
                        <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                            {{ $attendance->shiftLabel() }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 dark:divide-gray-700">
                <div class="flex flex-col justify-center px-6 py-8 sm:py-10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Jam Masuk</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-gray-900 dark:text-white sm:text-4xl">
                        {{ $attendance->formattedClockIn() ?? '—' }}
                    </p>
                    @if ($inBadge)
                        <span class="mt-3 inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $inBadge['class'] }}">
                            {{ $inBadge['text'] }}
                        </span>
                    @endif
                </div>
                <div class="flex flex-col justify-center px-6 py-8 sm:py-10">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Jam Pulang</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-gray-900 dark:text-white sm:text-4xl">
                        {{ $attendance->formattedClockOut() ?? '—' }}
                    </p>
                    @if ($outBadge)
                        <span class="mt-3 inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $outBadge['class'] }}">
                            {{ $outBadge['text'] }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
    @elseif ($pendingClockOut?->isRegular() && $pendingClockOut->clock_in_time && !$pendingClockOut->clock_out_time)
        <div class="border-b border-gray-100 bg-amber-50/60 px-6 py-4 dark:border-gray-700 dark:bg-amber-950/20">
            <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                Tugas Security — {{ $pendingClockOut->date->translatedFormat('l, d F Y') }}
            </p>
            <p class="mt-1 text-xs text-amber-700/90 dark:text-amber-300/90">
                Absen masuk tercatat. Selesaikan absen pulang setelah 24 jam tugas.
            </p>
        </div>
        <div class="grid grid-cols-1 divide-y divide-gray-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 dark:divide-gray-700">
            <div class="flex flex-col justify-center px-6 py-8 sm:py-10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Jam Masuk</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-gray-900 dark:text-white sm:text-4xl">
                    {{ $pendingClockOut->formattedClockIn() ?? '—' }}
                </p>
            </div>
            <div class="flex flex-col justify-center px-6 py-8 sm:py-10">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Jam Pulang</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-gray-900 dark:text-white sm:text-4xl">—</p>
                <span class="mt-3 inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    Menunggu absen pulang
                </span>
            </div>
        </div>
    @else
        <div class="relative overflow-hidden bg-gradient-to-br from-white via-white to-red-50/70 dark:from-gray-800 dark:via-gray-800 dark:to-red-950/20">
            <div class="pointer-events-none absolute -right-14 -top-16 h-40 w-40 rounded-full border-[24px] border-red-100/60 dark:border-red-900/20"></div>
            <div class="pointer-events-none absolute bottom-0 right-28 h-16 w-16 rounded-full bg-red-100/30 blur-xl dark:bg-red-900/10"></div>

            <div class="relative flex flex-col gap-5 px-5 py-6 sm:flex-row sm:items-center sm:px-7 sm:py-7">
                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-red-700 text-white shadow-lg shadow-red-600/20">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 14v3m-1.5-1.5h3" />
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        Menunggu absensi
                    </span>
                    <h2 class="mt-3 text-lg font-bold tracking-tight text-gray-900 sm:text-xl dark:text-white">
                        Belum melakukan absensi hari ini
                    </h2>
                    <div class="mt-2 flex items-center gap-2 text-sm font-medium text-gray-500 dark:text-gray-300">
                        <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>{{ \App\Support\AppTime::now()->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="relative flex items-start gap-3 border-t border-red-100/80 bg-red-50/50 px-5 py-4 sm:items-center sm:px-7 dark:border-gray-700 dark:bg-gray-900/20">
                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-red-600 shadow-sm ring-1 ring-gray-200 sm:mt-0 dark:bg-gray-800 dark:text-red-400 dark:ring-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v16m0 0-4-4m4 4 4-4" />
                    </svg>
                </span>
                <p class="text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                    Gunakan formulir di bawah untuk <strong class="font-semibold text-gray-800 dark:text-gray-100">absen masuk</strong> atau mengajukan <strong class="font-semibold text-gray-800 dark:text-gray-100">izin/sakit</strong>.
                </p>
            </div>
        </div>
    @endif
</x-attendance.attendance-card>
