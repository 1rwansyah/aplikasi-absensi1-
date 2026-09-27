<x-app-layout>
    @php
        $statusChartData = [
            $statusDistribution['present'],
            $statusDistribution['late'],
            $statusDistribution['absent'],
            $statusDistribution['leave'],
        ];
        $todayLabel = now()->timezone(config('app.timezone'))->translatedFormat('l, d F Y');
        $attendanceRate = $activeEmployees > 0
            ? round(($presentToday / $activeEmployees) * 100)
            : 0;
    @endphp

    <div class="py-6">
        <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-red-900/10 bg-gradient-to-br from-red-700 via-red-600 to-rose-600 px-5 py-6 text-white shadow-lg shadow-red-900/10 sm:px-7 sm:py-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-50">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                            Admin Portal
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Dashboard Kehadiran</h1>
                        <p class="mt-1.5 max-w-xl text-sm leading-relaxed text-red-50/90">
                            Ringkasan kehadiran karyawan hari ini beserta tren mingguan dan bulanan.
                        </p>
                        <p class="mt-3 text-xs font-medium text-red-100/90">{{ $todayLabel }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Aktif</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums">{{ $activeEmployees }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Hadir</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums">{{ $presentToday }}</p>
                        </div>
                        <div class="col-span-2 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm sm:col-span-1">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Tingkat hadir</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums">{{ $attendanceRate }}%</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Statistik --}}
            <div class="grid grid-cols-1 items-stretch gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <x-dashboard.stat-card
                    label="Total Karyawan"
                    :value="$totalEmployees"
                    accent="blue"
                    hint="Seluruh data karyawan terdaftar">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card
                    label="Karyawan Aktif"
                    :value="$activeEmployees"
                    accent="green"
                    hint="Status kepegawaian aktif">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card
                    label="Hadir Hari Ini"
                    :value="$presentToday"
                    accent="emerald"
                    hint="Absensi reguler yang sudah tercatat">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card
                    label="Terlambat Hari Ini"
                    :value="$lateToday"
                    accent="amber"
                    hint="Clock-in melewati jam masuk">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card
                    label="Tidak Hadir Hari Ini"
                    :value="$absentToday"
                    accent="red"
                    hint="Belum tercatat absensi hari ini">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.stat-card>

                <x-dashboard.stat-card
                    label="Cuti Hari Ini"
                    :value="$leaveToday"
                    accent="slate"
                    hint="Izin atau sakit yang sudah disetujui">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.stat-card>
            </div>

            {{-- Grafik --}}
            <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2">
                <x-dashboard.chart-card
                    title="Absensi Mingguan"
                    subtitle="Tren kehadiran 7 hari terakhir"
                    accent="blue"
                    :badge="array_sum($weeklyData).' hadir'"
                    canvas-id="weeklyChart"
                    :labels="$weeklyLabels"
                    :data="$weeklyData">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.chart-card>

                <x-dashboard.chart-card
                    title="Absensi Bulanan"
                    subtitle="Rekap kehadiran 12 bulan terakhir"
                    accent="emerald"
                    :badge="array_sum($monthlyData).' hadir'"
                    canvas-id="monthlyChart"
                    :labels="$monthlyLabels"
                    :data="$monthlyData">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.chart-card>
            </div>

            {{-- Distribusi & absensi terbaru --}}
            <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2">
                <x-dashboard.chart-card
                    title="Distribusi Status Hari Ini"
                    subtitle="Komposisi kehadiran aktual"
                    accent="slate"
                    :badge="array_sum($statusChartData).' data'"
                    canvas-id="statusChart"
                    :data="$statusChartData">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                        </svg>
                    </x-slot:icon>
                </x-dashboard.chart-card>

                <x-dashboard.panel-card
                    title="Absensi Terbaru"
                    subtitle="5 catatan kehadiran terakhir"
                    :badge="$recentAttendance->count().' entri'">
                    <x-slot:icon>
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </x-slot:icon>

                    <div class="space-y-2.5">
                        @forelse ($recentAttendance as $attendance)
                            @php
                                $photoUrl = $attendance->user?->employee?->profilePhotoUrl();
                            @endphp
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-gray-100/90 bg-white/80 px-3.5 py-3 shadow-sm shadow-gray-900/[0.02] transition hover:-translate-y-0.5 hover:border-red-100 hover:shadow-md dark:border-gray-700/70 dark:bg-gray-900/40 dark:hover:border-red-900/40">
                                <div class="flex min-w-0 items-center gap-3">
                                    @if ($photoUrl)
                                        <img src="{{ $photoUrl }}" alt="{{ $attendance->user->name }}"
                                            class="h-11 w-11 flex-shrink-0 rounded-2xl object-cover ring-2 ring-white dark:ring-gray-700">
                                    @else
                                        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 text-sm font-bold text-white shadow-sm">
                                            {{ strtoupper(substr($attendance->user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $attendance->user->name }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-gray-500 dark:text-gray-400">
                                            <span class="inline-flex items-center gap-1">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ $attendance->date->translatedFormat('d M Y') }}
                                            </span>
                                            @if ($attendance->formattedClockIn())
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ $attendance->formattedClockIn() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="flex-shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $attendance->statusBadgeClasses() }}">
                                    {{ $attendance->statusLabel() }}
                                </span>
                            </div>
                        @empty
                            <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white/60 px-4 py-12 text-center dark:border-gray-700 dark:bg-gray-900/30">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Belum ada data absensi</p>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Catatan kehadiran akan muncul di sini.</p>
                            </div>
                        @endforelse
                    </div>
                </x-dashboard.panel-card>
            </div>
        </div>
    </div>
</x-app-layout>
