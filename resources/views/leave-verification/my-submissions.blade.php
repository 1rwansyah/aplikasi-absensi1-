<x-app-layout>
    <x-attendance.employee-page page-id="leave-submissions-page">
        <div class="space-y-6" x-data="submissionEvidenceViewer">

            @php
                $hasFilter = ($filters['has_month'] ?? false) || filled($filters['status'] ?? null);
                $summaryCards = [
                    [
                        'label' => 'Semua',
                        'value' => $summary['total'],
                        'status' => null,
                        'icon_bg' => 'bg-gray-100 dark:bg-gray-700/60',
                        'icon' => 'text-gray-600 dark:text-gray-300',
                        'path' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                    ],
                    [
                        'label' => 'Menunggu',
                        'value' => $summary['pending'],
                        'status' => 'pending',
                        'icon_bg' => 'bg-amber-100 dark:bg-amber-900/40',
                        'icon' => 'text-amber-600 dark:text-amber-400',
                        'path' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                    [
                        'label' => 'Disetujui',
                        'value' => $summary['done'],
                        'status' => 'done',
                        'icon_bg' => 'bg-emerald-100 dark:bg-emerald-900/40',
                        'icon' => 'text-emerald-600 dark:text-emerald-400',
                        'path' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                    [
                        'label' => 'Ditolak',
                        'value' => $summary['no_done'],
                        'status' => 'no_done',
                        'icon_bg' => 'bg-red-100 dark:bg-red-900/40',
                        'icon' => 'text-red-600 dark:text-red-400',
                        'path' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                    ],
                ];
            @endphp

            {{-- Header --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-red-900/10 bg-gradient-to-br from-red-700 via-red-600 to-rose-600 px-5 py-6 text-white shadow-lg shadow-red-900/10 sm:px-7 sm:py-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-50">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                            Pengajuan Karyawan
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Status Pengajuan</h1>
                        <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-red-50/90">
                            Pantau perkembangan izin dan sakit Anda, mulai dari menunggu hingga selesai diverifikasi.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Total pengajuan</p>
                            <p class="mt-0.5 text-xl font-bold">{{ $summary['total'] }} <span class="text-xs font-medium text-red-100">data</span></p>
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

            {{-- Summary --}}
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ($summaryCards as $card)
                    @php
                        $cardQuery = array_filter([
                            'month' => $filters['month'],
                            'status' => $card['status'],
                        ], fn ($value) => filled($value));
                        $isActive = $card['status']
                            ? $filters['status'] === $card['status']
                            : ! filled($filters['status'] ?? null);
                    @endphp
                        <a href="{{ route('leave-verification.my-submissions', $cardQuery) }}"
                        class="group relative overflow-hidden rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800
                            {{ $isActive ? 'border-red-300 ring-2 ring-red-500/20 dark:border-red-700' : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' }}">
                        <span class="absolute inset-x-0 top-0 h-0.5 {{ $isActive ? 'bg-red-500' : 'bg-transparent' }}"></span>
                        <div class="flex items-center gap-3">
                            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl ring-1 ring-inset ring-black/5 {{ $card['icon_bg'] }}">
                                <svg class="h-5 w-5 {{ $card['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $card['path'] }}" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                                <p class="text-xl font-bold tabular-nums text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                            </div>
                            <svg class="ml-auto h-4 w-4 text-gray-300 transition-transform group-hover:translate-x-0.5 group-hover:text-gray-500 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- List panel --}}
            <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 bg-gray-50/70 px-4 py-5 sm:px-6 dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-50 text-red-600 ring-1 ring-red-100 dark:bg-red-950/30 dark:text-red-400 dark:ring-red-900/50">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707L14 14v5l-4 2v-7L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-gray-900 dark:text-white">Filter Pengajuan</h2>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Temukan pengajuan berdasarkan periode dan status.</p>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('leave-verification.my-submissions') }}"
                        class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Periode</span>
                            <x-ui.select name="month" class="w-full sm:!w-full">
                                <option value="" @selected(! filled($filters['month'] ?? null))>Semua Bulan</option>
                                @foreach ($monthOptions as $monthOption)
                                    <option value="{{ $monthOption['value'] }}" @selected(($filters['month'] ?? null) === $monthOption['value'])>
                                        {{ $monthOption['label'] }}
                                    </option>
                                @endforeach
                            </x-ui.select>
                        </label>

                        <label class="block">
                            <span class="mb-1.5 block text-xs font-semibold text-gray-600 dark:text-gray-300">Status Verifikasi</span>
                            <x-ui.select name="status" class="w-full sm:!w-full">
                                <option value="">Semua Status</option>
                                <option value="pending" @selected($filters['status'] === 'pending')>Menunggu</option>
                                <option value="done" @selected($filters['status'] === 'done')>Disetujui</option>
                                <option value="no_done" @selected($filters['status'] === 'no_done')>Ditolak</option>
                            </x-ui.select>
                        </label>

                        <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                            <x-ui.button type="submit" variant="primary" size="md" class="min-h-11 flex-1 lg:flex-none">
                                Terapkan
                            </x-ui.button>

                            @if ($hasFilter)
                                <a href="{{ route('leave-verification.my-submissions') }}"
                                    class="inline-flex min-h-11 items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="space-y-4 bg-gray-50/50 p-4 sm:p-5 dark:bg-gray-900/20">
                    @forelse ($submissions as $submission)
                        @php
                            $isPending = $submission->verification_status->isPending();
                            $isApproved = $submission->verification_status->isDone();
                            $isRejected = $submission->verification_status->isRejected();
                            $canResubmitToday = $isRejected
                                && $submission->date->isSameDay(\App\Support\AppTime::today())
                                && \App\Support\AppTime::now()->format('H:i:s') < '23:00:00';
                            $cardAccent = match (true) {
                                $isApproved => 'border-t-emerald-500',
                                $isRejected => 'border-t-red-500',
                                default => 'border-t-amber-500',
                            };
                            $calendarTone = match (true) {
                                $isApproved => [
                                    'header' => 'bg-emerald-600',
                                    'body' => 'bg-emerald-50 dark:bg-emerald-950/30',
                                    'day' => 'text-emerald-800 dark:text-emerald-200',
                                    'border' => 'border-emerald-200 dark:border-emerald-800/60',
                                ],
                                $isRejected => [
                                    'header' => 'bg-red-600',
                                    'body' => 'bg-red-50 dark:bg-red-950/30',
                                    'day' => 'text-red-800 dark:text-red-200',
                                    'border' => 'border-red-200 dark:border-red-800/60',
                                ],
                                default => [
                                    'header' => 'bg-amber-500',
                                    'body' => 'bg-amber-50 dark:bg-amber-950/30',
                                    'day' => 'text-amber-800 dark:text-amber-200',
                                    'border' => 'border-amber-200 dark:border-amber-800/60',
                                ],
                            };
                        @endphp
                        <article class="overflow-hidden rounded-2xl border border-t-2 border-gray-200 {{ $cardAccent }} bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:p-5 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1 space-y-3">
                                    <div class="flex items-start gap-3">
                                        <time datetime="{{ $submission->date->toDateString() }}"
                                            class="w-[4.75rem] shrink-0 overflow-hidden rounded-2xl border shadow-sm {{ $calendarTone['border'] }}">
                                            <span class="block px-2 py-1.5 text-center text-[10px] font-bold uppercase tracking-[0.14em] text-white {{ $calendarTone['header'] }}">
                                                {{ $submission->date->translatedFormat('M Y') }}
                                            </span>
                                            <span class="flex h-12 items-center justify-center text-2xl font-extrabold tabular-nums {{ $calendarTone['body'] }} {{ $calendarTone['day'] }}">
                                                {{ $submission->date->format('d') }}
                                            </span>
                                        </time>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">
                                                Tanggal Pengajuan
                                            </p>
                                            <time datetime="{{ $submission->date->toDateString() }}"
                                                class="mt-1 block text-base font-bold leading-tight text-gray-900 sm:text-lg dark:text-white">
                                                {{ $submission->date->translatedFormat('l, j F Y') }}
                                            </time>
                                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                                    {{ $submission->type->label() }}
                                                </span>
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $submission->verification_status->badgeClasses() }}">
                                                    {{ $submission->verification_status->label() }}
                                                </span>
                                            </div>
                                            <p class="mt-2 flex items-center gap-1.5 text-xs text-gray-400">
                                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Dikirim pukul {{ $submission->created_at->translatedFormat('H:i') }}
                                            </p>
                                        </div>
                                    </div>

                                    @if ($submission->leaveNoteText())
                                        <div class="rounded-xl border border-gray-100 bg-gray-50/70 px-3.5 py-3 dark:border-gray-700 dark:bg-gray-900/30">
                                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                                Keterangan
                                            </p>
                                            <p class="mt-1 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                                {{ $submission->leaveNoteText() }}
                                            </p>
                                        </div>
                                    @endif

                                    @if ($submission->hasDoctorNote())
                                        <x-attendance.doctor-note-link :attendance="$submission" modal
                                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-xl border border-red-100 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-300 dark:hover:bg-red-950/40" />
                                    @endif
                                </div>

                                <div class="w-full shrink-0 lg:max-w-md">
                                    @if ($isApproved)
                                        <div class="rounded-2xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-4 dark:border-emerald-800/50 dark:bg-emerald-950/30">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/50">
                                                    <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-emerald-900 dark:text-emerald-200">
                                                        Pengajuan disetujui
                                                    </p>
                                                    <p class="mt-0.5 text-xs text-emerald-700 dark:text-emerald-400">
                                                        Diverifikasi oleh {{ $submission->verifiedBy?->name ?? 'HR/Admin' }}
                                                        @if ($submission->verified_at)
                                                            · {{ $submission->verified_at->translatedFormat('j M Y, H:i') }}
                                                        @endif
                                                    </p>
                                                    <p class="mt-2 text-xs leading-relaxed text-emerald-800 dark:text-emerald-300">
                                                        Pengajuan telah selesai diproses dan tercatat pada riwayat absensi Anda.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @elseif ($isRejected)
                                        <div class="rounded-2xl border border-red-200/80 bg-red-50/90 px-4 py-4 dark:border-red-800/50 dark:bg-red-950/30">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-red-100 dark:bg-red-900/50">
                                                    <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <p class="text-sm font-bold text-red-900 dark:text-red-200">
                                                        Pengajuan ditolak
                                                    </p>
                                                    <p class="mt-0.5 text-xs text-red-700 dark:text-red-400">
                                                        Diverifikasi oleh {{ $submission->verifiedBy?->name ?? 'HR/Admin' }}
                                                        @if ($submission->verified_at)
                                                            · {{ $submission->verified_at->translatedFormat('j M Y, H:i') }}
                                                        @endif
                                                    </p>
                                                    <p class="mt-2 text-xs leading-relaxed text-red-800 dark:text-red-300">
                                                        @if ($submission->isRejectedSick())
                                                            Pengajuan sakit Anda telah ditolak.
                                                        @else
                                                            Pengajuan izin Anda telah ditolak dan tercatat sebagai alfa.
                                                        @endif
                                                        @if ($canResubmitToday)
                                                            Anda dapat mengajukan ulang dengan bukti yang lebih lengkap sebelum pukul 23.00 hari ini.
                                                        @endif
                                                    </p>
                                                    @if (filled($submission->rejection_reason))
                                                        <div class="mt-3 rounded-xl border border-red-200 bg-white/70 p-3 dark:border-red-800/70 dark:bg-red-950/30">
                                                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide text-red-700 dark:text-red-300">
                                                                <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                                                Alasan Penolakan HR
                                                            </p>
                                                            <p class="mt-1.5 break-words whitespace-pre-line text-sm font-medium leading-relaxed text-red-950 dark:text-red-50">
                                                                {{ trim($submission->rejection_reason) }}
                                                            </p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="rounded-2xl border border-amber-200/80 bg-amber-50/90 px-4 py-4 dark:border-amber-800/50 dark:bg-amber-950/30">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-0.5 flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/50">
                                                    <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-bold text-amber-900 dark:text-amber-200">
                                                        Menunggu verifikasi
                                                    </p>
                                                    <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
                                                        Diajukan {{ $submission->created_at->translatedFormat('j M Y, H:i') }}
                                                    </p>
                                                    <p class="mt-2 text-xs leading-relaxed text-amber-800 dark:text-amber-300">
                                                        HR sedang memeriksa keterangan dan bukti yang Anda kirim. Pantau halaman ini untuk melihat hasilnya.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-700/60">
                                <svg class="h-7 w-7 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="mt-4 text-sm font-semibold text-gray-800 dark:text-gray-200">Belum ada pengajuan</p>
                            <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">
                                Tidak ada izin atau sakit untuk filter ini. Ajukan dari halaman Absensi jika diperlukan.
                            </p>
                            <a href="{{ route('attendance.index') }}"
                                class="mt-5 inline-flex min-h-11 items-center justify-center rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                                Ke halaman Absensi
                            </a>
                        </div>
                    @endforelse
                </div>

                @if ($submissions->hasPages())
                    <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Menampilkan {{ $submissions->firstItem() }}–{{ $submissions->lastItem() }}
                            dari {{ $submissions->total() }} pengajuan
                        </p>
                        <div>{{ $submissions->withQueryString()->links() }}</div>
                    </div>
                @elseif ($submissions->total() > 0)
                    <div class="border-t border-gray-100 px-4 py-3 sm:px-6 dark:border-gray-700">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $submissions->total() }} pengajuan
                        </p>
                    </div>
                @endif
            </div>

            {{-- Evidence preview modal --}}
            <div x-cloak x-show="isOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @keydown.escape.window="closeEvidence()"
                @click.self="closeEvidence()"
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/85 p-3 backdrop-blur-sm sm:p-6"
                role="dialog" aria-modal="true" aria-labelledby="evidence-modal-title">
                <div class="flex h-[92dvh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-gray-900 shadow-2xl"
                    @click.stop>
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <h2 id="evidence-modal-title" class="truncate text-sm font-semibold text-white sm:text-base"
                                x-text="title"></h2>
                            <p class="mt-0.5 text-xs text-gray-400"
                                x-text="isImage ? 'Scroll untuk zoom · Geser gambar untuk melihat area lain' : 'Preview dokumen PDF'"></p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <a :href="url" target="_blank" rel="noopener noreferrer"
                                class="hidden min-h-9 items-center rounded-lg border border-white/15 px-3 text-xs font-medium text-gray-200 transition hover:bg-white/10 sm:inline-flex">
                                Buka di tab baru
                            </a>
                            <button type="button" @click="closeEvidence()"
                                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-300 transition hover:bg-white/10 hover:text-white"
                                aria-label="Tutup preview">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="relative min-h-0 flex-1 overflow-hidden bg-black/40">
                        {{-- Image viewer --}}
                        <div x-show="isImage"
                            x-ref="imageViewport"
                            @wheel.prevent="handleWheel($event)"
                            @pointerdown="startPan($event)"
                            @pointermove="movePan($event)"
                            @pointerup="endPan($event)"
                            @pointercancel="endPan($event)"
                            class="flex h-full w-full select-none items-center justify-center overflow-hidden touch-none"
                            :class="scale > 1 ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'">
                            <img x-ref="evidenceImage" :src="url" :alt="title"
                                draggable="false"
                                @load="resetZoom()"
                                @dblclick.prevent="toggleZoomAt($event.clientX, $event.clientY)"
                                class="max-h-full max-w-full select-none object-contain will-change-transform"
                                :style="imageTransform">
                        </div>

                        {{-- PDF viewer --}}
                        <div x-show="!isImage" class="h-full w-full bg-white">
                            <iframe :src="url" :title="title" class="h-full w-full border-0"></iframe>
                        </div>

                        {{-- Image controls --}}
                        <div x-show="isImage"
                            class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1 rounded-xl border border-white/10 bg-gray-950/80 p-1.5 text-white shadow-xl backdrop-blur">
                            <button type="button" @click="zoomFromCenter(-zoomStep)"
                                class="flex h-9 w-9 items-center justify-center rounded-lg transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="scale <= minScale" aria-label="Perkecil gambar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                </svg>
                            </button>
                            <button type="button" @click="resetZoom()"
                                class="min-w-14 rounded-lg px-2 py-2 text-xs font-semibold tabular-nums transition hover:bg-white/10"
                                x-text="Math.round(scale * 100) + '%'"></button>
                            <button type="button" @click="zoomFromCenter(zoomStep)"
                                class="flex h-9 w-9 items-center justify-center rounded-lg transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="scale >= maxScale" aria-label="Perbesar gambar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center justify-between gap-3 border-t border-white/10 px-4 py-3 sm:hidden">
                        <p class="text-[11px] text-gray-400" x-text="isImage ? 'Ketuk 2× untuk memperbesar/perkecil' : 'Jika preview tidak tampil, buka di tab baru.'"></p>
                        <a :href="url" target="_blank" rel="noopener noreferrer"
                            class="shrink-0 rounded-lg border border-white/15 px-3 py-2 text-xs font-medium text-gray-200">
                            Buka di tab baru
                        </a>
                    </div>
                </div>
            </div>

            {{-- Subtle help --}}
            <div class="rounded-2xl border border-blue-100 bg-blue-50/60 px-5 py-4 dark:border-blue-900/40 dark:bg-blue-950/20">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/50 dark:text-blue-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-bold text-blue-900 dark:text-blue-100">Informasi pengajuan</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-blue-700 dark:text-blue-300">
                            Pastikan data dan bukti yang dikirim jelas agar proses verifikasi berjalan lancar.
                        </p>
                    </div>
                </div>
                <ul class="mt-3 space-y-2 border-t border-blue-100 pt-3 text-sm leading-relaxed text-blue-900/80 dark:border-blue-900/50 dark:text-blue-200/80">
                    <li class="flex gap-2">
                        <span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-400"></span>
                        <span>Status akan diperbarui setelah pengajuan diperiksa oleh HR/Admin.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-400"></span>
                        <span>Jika ditolak, alasan penolakan akan ditampilkan di halaman ini dan dikirim melalui email.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-2 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-blue-400"></span>
                        <span>Pengajuan yang ditolak dapat diajukan ulang sesuai batas waktu yang ditampilkan.</span>
                    </li>
                </ul>
            </div>
        </div>
    </x-attendance.employee-page>

    @once
        @push('scripts')
            <script>
                document.addEventListener('alpine:init', () => {
                    Alpine.data('submissionEvidenceViewer', () => ({
                        isOpen: false,
                        isImage: true,
                        url: '',
                        title: '',
                        scale: 1,
                        minScale: 1,
                        maxScale: 5,
                        zoomStep: 0.5,
                        translateX: 0,
                        translateY: 0,
                        isDragging: false,
                        pointerId: null,
                        pointerStartX: 0,
                        pointerStartY: 0,
                        previousPointerX: 0,
                        previousPointerY: 0,
                        pointerMoved: false,
                        lastTapAt: 0,
                        lastTapX: 0,
                        lastTapY: 0,
                        previousBodyOverflow: '',

                        get imageTransform() {
                            return `transform: translate3d(${this.translateX}px, ${this.translateY}px, 0) scale(${this.scale});`;
                        },

                        openEvidence(data) {
                            this.url = data.url;
                            this.title = data.title;
                            this.isImage = data.isImage === '1';
                            this.resetZoom();
                            this.previousBodyOverflow = document.body.style.overflow;
                            document.body.style.overflow = 'hidden';
                            this.isOpen = true;
                        },

                        closeEvidence() {
                            if (!this.isOpen) {
                                return;
                            }

                            this.isOpen = false;
                            this.stopDragging();
                            this.resetZoom();
                            document.body.style.overflow = this.previousBodyOverflow;
                        },

                        resetZoom() {
                            this.scale = this.minScale;
                            this.translateX = 0;
                            this.translateY = 0;
                        },

                        clamp(value, minimum, maximum) {
                            return Math.min(maximum, Math.max(minimum, value));
                        },

                        zoomFromCenter(delta) {
                            const viewport = this.$refs.imageViewport;
                            if (!viewport) {
                                return;
                            }

                            const rect = viewport.getBoundingClientRect();
                            this.zoomAt(rect.left + (rect.width / 2), rect.top + (rect.height / 2), this.scale + delta);
                        },

                        zoomAt(clientX, clientY, requestedScale) {
                            const viewport = this.$refs.imageViewport;
                            if (!viewport) {
                                return;
                            }

                            const nextScale = this.clamp(requestedScale, this.minScale, this.maxScale);
                            if (nextScale === this.scale) {
                                return;
                            }

                            const rect = viewport.getBoundingClientRect();
                            const pointX = clientX - (rect.left + (rect.width / 2));
                            const pointY = clientY - (rect.top + (rect.height / 2));
                            const ratio = nextScale / this.scale;

                            this.translateX = pointX - ((pointX - this.translateX) * ratio);
                            this.translateY = pointY - ((pointY - this.translateY) * ratio);
                            this.scale = nextScale;

                            if (nextScale === this.minScale) {
                                this.translateX = 0;
                                this.translateY = 0;
                            } else {
                                this.constrainPan();
                            }
                        },

                        handleWheel(event) {
                            const direction = event.deltaY < 0 ? this.zoomStep : -this.zoomStep;
                            this.zoomAt(event.clientX, event.clientY, this.scale + direction);
                        },

                        toggleZoomAt(clientX, clientY) {
                            const nextScale = this.scale > this.minScale ? this.minScale : 2.5;
                            this.zoomAt(clientX, clientY, nextScale);
                        },

                        startPan(event) {
                            this.pointerId = event.pointerId;
                            this.pointerStartX = event.clientX;
                            this.pointerStartY = event.clientY;
                            this.previousPointerX = event.clientX;
                            this.previousPointerY = event.clientY;
                            this.pointerMoved = false;

                            if (this.scale > this.minScale) {
                                this.isDragging = true;
                                event.currentTarget.setPointerCapture?.(event.pointerId);
                            }
                        },

                        movePan(event) {
                            if (this.pointerId !== event.pointerId) {
                                return;
                            }

                            const totalDistance = Math.hypot(
                                event.clientX - this.pointerStartX,
                                event.clientY - this.pointerStartY,
                            );

                            if (totalDistance > 6) {
                                this.pointerMoved = true;
                            }

                            if (!this.isDragging || this.scale <= this.minScale) {
                                return;
                            }

                            this.translateX += event.clientX - this.previousPointerX;
                            this.translateY += event.clientY - this.previousPointerY;
                            this.previousPointerX = event.clientX;
                            this.previousPointerY = event.clientY;
                            this.constrainPan();
                        },

                        endPan(event) {
                            if (this.pointerId !== event.pointerId) {
                                return;
                            }

                            const isTouchTap = event.pointerType === 'touch' && !this.pointerMoved;
                            this.stopDragging();

                            if (!isTouchTap) {
                                return;
                            }

                            const now = Date.now();
                            const closeToPreviousTap = Math.hypot(
                                event.clientX - this.lastTapX,
                                event.clientY - this.lastTapY,
                            ) < 48;

                            if ((now - this.lastTapAt) < 320 && closeToPreviousTap) {
                                this.toggleZoomAt(event.clientX, event.clientY);
                                this.lastTapAt = 0;
                                return;
                            }

                            this.lastTapAt = now;
                            this.lastTapX = event.clientX;
                            this.lastTapY = event.clientY;
                        },

                        stopDragging() {
                            this.isDragging = false;
                            this.pointerId = null;
                        },

                        constrainPan() {
                            const viewport = this.$refs.imageViewport;
                            const image = this.$refs.evidenceImage;
                            if (!viewport || !image || this.scale <= this.minScale) {
                                return;
                            }

                            const maxX = Math.max(0, ((image.clientWidth * this.scale) - viewport.clientWidth) / 2);
                            const maxY = Math.max(0, ((image.clientHeight * this.scale) - viewport.clientHeight) / 2);

                            this.translateX = this.clamp(this.translateX, -maxX, maxX);
                            this.translateY = this.clamp(this.translateY, -maxY, maxY);
                        },
                    }));
                });
            </script>
        @endpush
    @endonce
</x-app-layout>
