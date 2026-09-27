<x-app-layout>

    <x-attendance.employee-page>

        <div x-data="attendanceEvidenceViewer" class="flex w-full flex-1 flex-col gap-5 lg:gap-6">

            <x-attendance.digital-clock :settings="$settings" class="w-full shadow-md" />

            <x-attendance.quick-stats :settings="$settings" :has-face-registered="$hasFaceRegistered" :needs-face-descriptor-sync="$needsFaceDescriptorSync" :profile-photo-url="$profilePhotoUrl"
                :holiday-label="$holidayLabel ?? null" :today-schedule="$todaySchedule ?? null" />

            @php
                $hasAttendance = (bool) $attendance;
                $isRegular = $attendance?->isRegular() ?? false;
                $isLeave = $hasAttendance && !$isRegular;
                $isRejectedLeave = $attendance?->isRejectedLeave() ?? false;
                $hasClockIn = (bool) $attendance?->clock_in_time;
                $hasClockOut = (bool) $attendance?->clock_out_time;

                $hasPendingClockOut = ($pendingClockOut ?? null)?->isRegular()
                    && $pendingClockOut->clock_in_time
                    && !$pendingClockOut->clock_out_time;

                $attendanceLocked = ($isLeave && ! $isRejectedLeave) || ($isRegular && $hasClockOut);
                $showLeaveForm = (! $hasAttendance || $isRejectedLeave) && ! $hasPendingClockOut;

                $showClockOutWaiting = $hasPendingClockOut && !$canClockOutNow;
                $clockOutOnly = $hasPendingClockOut
                    && !$pendingClockOut->date->isSameDay(\App\Support\AppTime::today());

                $isOffDayWithoutAttendance = ($isOffDay ?? false) && !$hasAttendance && !$hasPendingClockOut;
            @endphp

            @unless ($isRejectedLeave)
                <x-attendance.today-status-card :attendance="$attendance" :pending-clock-out="$pendingClockOut ?? null" />
            @endunless

            <div class="flex w-full flex-1 flex-col gap-5 lg:gap-6">
                @if ($holidayLabel ?? null)
                    <x-attendance.attendance-card
                        class="border-amber-200 bg-amber-50 dark:border-amber-800/50 dark:bg-amber-950/30">
                        <div class="flex gap-4 px-6 py-5 sm:items-center">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/50">
                                <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                    Hari Libur — {{ $holidayLabel }}
                                </p>
                                <p class="mt-1 text-sm leading-relaxed text-amber-700/90 dark:text-amber-300/90">
                                    Hari ini tercatat sebagai hari libur. Absensi dan pengajuan izin tetap dapat dilakukan jika diperlukan.
                                </p>
                            </div>
                        </div>
                    </x-attendance.attendance-card>
                @endif

                @if ($halfDayNotice ?? null)
                    <x-attendance.attendance-card
                        class="border-sky-200 bg-sky-50 dark:border-sky-800/50 dark:bg-sky-950/30">
                        <div class="flex gap-4 px-6 py-5 sm:items-center">
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-sky-100 dark:bg-sky-900/50">
                                <svg class="h-5 w-5 text-sky-600 dark:text-sky-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-sky-800 dark:text-sky-200">
                                    Setengah Hari
                                </p>
                                <p class="mt-1 text-sm leading-relaxed text-sky-700/90 dark:text-sky-300/90">
                                    {{ $halfDayNotice }}
                                </p>
                            </div>
                        </div>
                    </x-attendance.attendance-card>
                @endif

                @if ($isRejectedLeave)
                    <x-attendance.attendance-card
                        class="border-t-2 border-t-red-500"
                        x-data="{ showRejectedDetail: false }">
                        <div class="relative overflow-hidden bg-gradient-to-br from-red-50/90 via-white to-white dark:from-red-950/25 dark:via-gray-800 dark:to-gray-800">
                            <div class="pointer-events-none absolute -right-14 -top-16 h-40 w-40 rounded-full border-[24px] border-red-100/50 dark:border-red-900/10"></div>

                            <div class="relative flex flex-col gap-4 px-5 py-6 sm:flex-row sm:items-start sm:px-7">
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-red-700 text-white shadow-lg shadow-red-600/20">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 9l6 6m0-6-6 6m12-3a9 9 0 11-18 0 9 9 0 0118 0Z" />
                                    </svg>
                                </span>

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h2 class="text-lg font-bold tracking-tight text-red-900 sm:text-xl dark:text-red-100">
                                    @if ($attendance->isRejectedSick())
                                        Pengajuan sakit Anda telah ditolak
                                    @else
                                                Pengajuan izin Anda telah ditolak
                                    @endif
                                        </h2>
                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-inset ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800">
                                            Ditolak
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm leading-relaxed text-red-700/90 dark:text-red-300/90">
                                    @if ($attendance->isRejectedSick())
                                        Anda dapat absen reguler atau mengajukan ulang sakit hari ini.
                                    @else
                                            Pengajuan ini tercatat sebagai alfa. Anda dapat absen reguler atau mengajukan ulang izin/sakit hari ini.
                                    @endif
                                    </p>

                                    <button type="button" @click="showRejectedDetail = !showRejectedDetail"
                                        class="mt-4 inline-flex min-h-10 items-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-50 dark:border-red-900 dark:bg-gray-800 dark:text-red-300 dark:hover:bg-red-950/20">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0Zm6 0s-3 6-9 6-9-6-9-6 3-6 9-6 9 6 9 6Z" />
                                        </svg>
                                        <span x-text="showRejectedDetail ? 'Tutup detail pengajuan' : 'Lihat detail pengajuan'"></span>
                                        <svg class="h-4 w-4 transition-transform" :class="showRejectedDetail && 'rotate-180'"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div x-cloak x-show="showRejectedDetail" x-collapse
                                class="relative border-t border-red-100 bg-white/60 dark:border-red-900/40 dark:bg-gray-900/20">
                                <div class="grid gap-4 px-5 py-5 sm:px-7 lg:grid-cols-2">
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Tanggal Pengajuan</p>
                                            <p class="mt-1 text-sm font-semibold capitalize text-gray-800 dark:text-gray-200">
                                                {{ $attendance->date->translatedFormat('l, d F Y') }}
                                            </p>
                                        </div>
                                        @if ($attendance->leaveNoteText())
                                            <div>
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Keterangan</p>
                                                <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                                    {{ trim($attendance->leaveNoteText()) }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="space-y-4">
                                        @if (filled($attendance->rejection_reason))
                                            <div class="rounded-xl border border-red-100 bg-red-50/70 p-4 dark:border-red-900/50 dark:bg-red-950/20">
                                                <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-red-500 dark:text-red-400">Alasan Penolakan HR</p>
                                                <p class="mt-1.5 whitespace-pre-line text-sm font-medium leading-relaxed text-red-800 dark:text-red-200">
                                                    {{ trim($attendance->rejection_reason) }}
                                                </p>
                                            </div>
                                        @endif

                                        @if ($attendance->hasDoctorNote())
                                            <x-attendance.doctor-note-link :attendance="$attendance" modal
                                                class="inline-flex min-h-10 w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-red-950/20 dark:hover:text-red-300" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-attendance.attendance-card>
                @endif

                @if ($attendanceLocked)
                    <x-attendance.attendance-locked-card :attendance="$attendance" />
                @elseif ($isOffDayWithoutAttendance)
                    <x-attendance.attendance-off-day-card :schedule-name="$todaySchedule?->name === 'Off' ? 'Libur' : ($todaySchedule?->name ?? 'Libur')" />
                @else
                    @php
                        $reportMode = ($hasClockIn || $hasPendingClockOut) ? 'hasil-pekerjaan' : 'rencana-kerja';
                    @endphp
                  <x-attendance.attendance-regular-form class="w-full min-h-[320px] lg:min-h-[360px]"
    :settings="$settings"
    :has-face-registered="$hasFaceRegistered"
    :needs-face-descriptor-sync="$needsFaceDescriptorSync"
    :profile-photo-url="$profilePhotoUrl"
    :face-match-threshold="$faceMatchThreshold"
    :face-min-match-percent="$faceMinMatchPercent"
    :show-clock-out-waiting="$showClockOutWaiting"
    :clock-out-opens-at="$clockOutOpensAt"
    :clock-out-window="$clockOutWindow"
    :has-clock-in="$hasClockIn"
    :has-clock-out="$hasClockOut"
    :report-mode="$reportMode"
    :disable-clock-in="$clockOutOnly"
    :pending-clock-out-date="$clockOutOnly ? $pendingClockOut->date : null" />
                @endif

                @if ($showLeaveForm && !($isOffDayWithoutAttendance ?? false))
                    <x-attendance.leave-form class="w-full" />
                @endif
            </div>

            <x-attendance.attendance-card class="border-t-2 border-t-red-500">
                <div
                    class="relative flex flex-col gap-4 overflow-hidden border-b border-gray-100 bg-gradient-to-r from-red-50/70 via-white to-white px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-700 dark:from-red-950/15 dark:via-gray-800 dark:to-gray-800">
                    <div class="pointer-events-none absolute -right-10 -top-14 h-28 w-28 rounded-full border-[18px] border-red-100/40 dark:border-red-900/10"></div>
                    <div class="relative flex min-w-0 items-center gap-3.5">
                        <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-600 ring-1 ring-inset ring-red-200/70 dark:bg-red-900/25 dark:text-red-400 dark:ring-red-800/50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0Z" />
                            </svg>
                        </span>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold tracking-tight text-gray-900 sm:text-lg dark:text-white">Riwayat Terbaru</h3>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $history->count() }} catatan
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-gray-400">
                                Ringkasan aktivitas absensi dalam 7 hari terakhir
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('attendance.history') }}"
                        class="group relative inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 shadow-sm transition hover:border-red-300 hover:bg-red-50 sm:w-auto dark:border-red-900 dark:bg-gray-800 dark:text-red-400 dark:hover:bg-red-950/20">
                        Lihat semua riwayat
                        <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                @if ($history->isNotEmpty())
                    <x-attendance.history-table :records="$history" :show-header="false"
                        class="border-0 bg-transparent shadow-none dark:bg-transparent"
                        empty-message="Belum ada riwayat absensi." />
                @else
                    <div class="flex flex-col items-center px-6 py-10 text-center">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M9 5H7a2 2 0 00-2 2v12h14V7a2 2 0 00-2-2h-2m-6 0a3 3 0 006 0M9 5a3 3 0 016 0m-5 7h4m-4 4h7" />
                            </svg>
                        </span>
                        <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Belum ada riwayat absensi</p>
                        <p class="mt-1 max-w-sm text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            Catatan kehadiran akan muncul di sini setelah Anda melakukan absensi.
                        </p>
                    </div>
                @endif
            </x-attendance.attendance-card>

            <x-attendance.evidence-viewer :show-open-in-new-tab="false" />

        </div>

    </x-attendance.employee-page>

    @include('attendance.partials.face-verification-modal')

</x-app-layout>
