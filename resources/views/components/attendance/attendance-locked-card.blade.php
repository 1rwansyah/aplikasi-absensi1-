@props([
    'attendance' => null,
])

@php
    $isLeave = $attendance && !$attendance->isRegular();
    $isComplete = $attendance?->isRegular() && $attendance->clock_out_time;
    $isPendingLeave = $isLeave && $attendance->verification_status->isPending();
    $isApprovedLeave = $isLeave && $attendance->verification_status->isDone();
    $isRejectedLeave = $isLeave && $attendance->verification_status->isRejected();

    $cardAccentClass = match (true) {
        $isPendingLeave => 'border-t-2 border-t-amber-500',
        $isApprovedLeave, $isComplete => 'border-t-2 border-t-emerald-500',
        $isRejectedLeave => 'border-t-2 border-t-red-500',
        default => 'border-t-2 border-t-gray-400',
    };
    $iconClass = match (true) {
        $isPendingLeave => 'from-amber-400 to-amber-600 shadow-amber-600/20',
        $isApprovedLeave, $isComplete => 'from-emerald-500 to-emerald-700 shadow-emerald-600/20',
        $isRejectedLeave => 'from-red-500 to-red-700 shadow-red-600/20',
        default => 'from-gray-500 to-gray-700 shadow-gray-600/20',
    };
    $softBackgroundClass = match (true) {
        $isPendingLeave => 'from-amber-50/80 via-white to-white dark:from-amber-950/20',
        $isApprovedLeave, $isComplete => 'from-emerald-50/70 via-white to-white dark:from-emerald-950/20',
        $isRejectedLeave => 'from-red-50/70 via-white to-white dark:from-red-950/20',
        default => 'from-gray-50 via-white to-white dark:from-gray-800',
    };
@endphp

<x-attendance.attendance-card {{ $attributes->merge(['class' => $cardAccentClass]) }}>
    <div class="relative overflow-hidden bg-gradient-to-br {{ $softBackgroundClass }} dark:via-gray-800 dark:to-gray-800">
        <div class="pointer-events-none absolute -right-12 -top-16 h-36 w-36 rounded-full border-[22px] border-gray-100/50 dark:border-white/[0.03]"></div>

        <div class="relative flex flex-col gap-5 px-5 py-6 sm:flex-row sm:items-start sm:px-7 sm:py-7">
            <div class="relative shrink-0">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg {{ $iconClass }}">
                    @if ($isPendingLeave)
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="9" stroke-width="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 2" />
                        </svg>
                    @elseif ($isApprovedLeave || $isComplete)
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3 3 7-7m4 4a9 9 0 11-18 0 9 9 0 0118 0Z" />
                        </svg>
                    @else
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    @endif
                </span>
                <span class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-lg border-2 border-white bg-gray-800 text-white dark:border-gray-800">
                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11V8a4 4 0 018 0v3m-9 0h10v9H7v-9Z" />
                    </svg>
                </span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-lg font-bold tracking-tight text-gray-900 sm:text-xl dark:text-gray-100">
                        Absensi &amp; Izin Dikunci
                    </h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V8a4 4 0 018 0v3m-9 0h10v9H7v-9Z" />
                        </svg>
                        Terkunci hari ini
                    </span>
                </div>

            @if ($isLeave)
                <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    Pengajuan {{ strtolower($attendance->type->label()) }} hari ini sudah tercatat.
                </p>

                <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-gray-200/80 bg-white/80 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-gray-700 dark:bg-gray-800/80">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Status Verifikasi</p>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $isPendingLeave ? 'Pengajuan sedang menunggu pemeriksaan HR.' : 'Status terbaru pengajuan Anda.' }}
                        </p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-semibold {{ $attendance->verification_status->badgeClasses() }}">
                        @if ($isPendingLeave)
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-50"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                            </span>
                        @endif
                        {{ $attendance->verification_status->label() }}
                    </span>
                </div>

                @if ($attendance->verification_status->isDone() && $attendance->verifiedBy)
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Disetujui oleh {{ $attendance->verifiedBy->name }}
                    </p>
                @elseif ($attendance->verification_status->isRejected() && $attendance->verifiedBy)
                    <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Ditolak oleh {{ $attendance->verifiedBy->name }}
                    </p>
                @endif
            @elseif ($isComplete)
                <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    Absensi hari ini sudah lengkap. Terima kasih, sampai jumpa besok!
                </p>
                @if ($attendance->overtime_hours > 0)
                    <p class="mt-3 inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                        Lembur: {{ number_format($attendance->overtime_hours, 2) }} jam
                    </p>
                @endif
            @else
                <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    Anda tidak dapat melakukan absensi atau pengajuan izin lagi hari ini.
                </p>
            @endif
            </div>
        </div>
    </div>
</x-attendance.attendance-card>
