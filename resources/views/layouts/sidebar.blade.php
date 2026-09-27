@php
    $authUser = auth()->user();
    $isAdmin = auth()->check() && $authUser->isAdmin();
    $hasEmployee = auth()->check() && $authUser->employee !== null;

    $onAdminRoute = \App\Support\NavigationContext::onAdminRoute();

    // employee (±hr): employee space by default, admin space only when on admin routes
    // admin only: always admin space
    $showAdminSpace = $onAdminRoute ? $isAdmin : !$hasEmployee;

    $homeRoute = $showAdminSpace ? route('dashboard') : route('attendance.index');
    $sidebarPhotoUrl = $authUser->employee?->profilePhotoUrl();

    $isSettingsActive =
        request()->routeIs('settings.work-hours.*') ||
        request()->routeIs('settings.security-schedules.*') ||
        request()->routeIs('settings.location.*') ||
        request()->routeIs('work-calendars.*');

    $adminLink = function (bool $active): string {
        return $active
            ? 'bg-white text-red-700 shadow-md shadow-red-950/15'
            : 'text-red-50/90 hover:bg-white/10 hover:text-white';
    };

    $employeeLink = function (bool $active): string {
        return $active
            ? 'bg-white text-red-700 shadow-md shadow-red-950/10 dark:bg-slate-700 dark:text-white'
            : 'text-red-100 hover:translate-x-0.5 hover:bg-white/10 hover:text-white dark:text-slate-300 dark:hover:bg-slate-700/50';
    };
@endphp

<aside id="app-sidebar"
    class="relative flex h-full w-full flex-col overflow-hidden bg-gradient-to-b from-red-600 via-red-700 to-red-900 shadow-2xl shadow-red-950/20 dark:from-slate-800 dark:via-slate-900 dark:to-slate-950 dark:shadow-black/30">
    <div class="pointer-events-none absolute -right-16 top-24 h-44 w-44 rounded-full border-[28px] border-white/5"></div>
    <div class="pointer-events-none absolute -left-20 bottom-24 h-48 w-48 rounded-full border-[32px] border-white/[0.04]"></div>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-32 bg-gradient-to-b from-white/10 to-transparent"></div>

    <div class="relative flex h-[4.5rem] flex-shrink-0 items-center border-b border-white/10 px-5">
        <a href="{{ $homeRoute }}" class="group flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/15 transition group-hover:bg-white/15">
                <img src="{{ asset('images/logo/logo_SADAR.png') }}" alt="SADAR-PRI Logo"
                    class="block h-7 w-auto object-contain">
            </span>
            <span class="min-w-0">
                <span class="block text-base font-extrabold uppercase tracking-[0.16em] text-white">SADAR-PRI</span>
                <span class="mt-0.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-red-100/85 dark:text-slate-400">
                    {{ $showAdminSpace ? 'Admin Portal' : 'Employee Portal' }}
                </span>
            </span>
        </a>
    </div>

    <nav class="relative flex-1 space-y-1 overflow-y-auto px-3.5 py-5 scrollbar-none">

        @if ($showAdminSpace)
            <p class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-red-100/70 dark:text-slate-500">
                Menu Admin
            </p>

            <a href="{{ route('dashboard') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $adminLink(request()->routeIs('dashboard')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
                @if (request()->routeIs('dashboard'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @endif
            </a>

            <a href="{{ route('employees.index') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $adminLink(request()->routeIs('employees.*')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Karyawan
                @if (request()->routeIs('employees.*'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @endif
            </a>

            <a href="{{ route('admin.attendance.index') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $adminLink(request()->routeIs('admin.attendance.*')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Admin Absensi
                @if (request()->routeIs('admin.attendance.*'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @endif
            </a>

            <a href="{{ route('admin.absence-threshold.index') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $adminLink(request()->routeIs('admin.absence-threshold.*')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                </svg>
                Alfa &amp; Izin
                @if (($absenceThresholdCount ?? 0) > 0)
                    <span
                        class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-bold tabular-nums {{ request()->routeIs('admin.absence-threshold.*') ? 'bg-red-600 text-white' : 'bg-white/20 text-white ring-1 ring-white/25' }}"
                        title="{{ $absenceThresholdCount }} karyawan kena threshold bulan ini">
                        {{ $absenceThresholdCount > 99 ? '99+' : $absenceThresholdCount }}
                    </span>
                @elseif (request()->routeIs('admin.absence-threshold.*'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @endif
            </a>

            <a href="{{ route('leave-verification.index', ['status' => 'pending']) }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $adminLink(request()->routeIs('leave-verification.*')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Verifikasi
                @if (($pendingLeaveVerificationCount ?? 0) > 0)
                    <span
                        class="ml-auto inline-flex min-w-[1.5rem] items-center justify-center rounded-full px-1.5 py-0.5 text-[11px] font-bold tabular-nums {{ request()->routeIs('leave-verification.*') ? 'bg-red-600 text-white' : 'bg-white/20 text-white ring-1 ring-white/25' }}"
                        title="{{ $pendingLeaveVerificationCount }} pengajuan menunggu verifikasi">
                        {{ $pendingLeaveVerificationCount > 99 ? '99+' : $pendingLeaveVerificationCount }}
                    </span>
                @elseif (request()->routeIs('leave-verification.index'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @endif
            </a>

            <a href="{{ route('payrolls.index') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $adminLink(request()->routeIs('payrolls.*')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Penggajian
                @if (request()->routeIs('payrolls.*'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @endif
            </a>

            <a href="{{ route('activity-log.index') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $adminLink(request()->routeIs('activity-log.*')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2M12 8v4l3 3" />
                </svg>
                Log Aktivitas
                @if (request()->routeIs('activity-log.*'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500"></span>
                @endif
            </a>

            <div class="my-3 border-t border-white/10"></div>

            <p class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-red-100/70 dark:text-slate-500">
                Sistem
            </p>

            <div
                x-data="{
                    open: localStorage.getItem('settingsMenuOpen') === '1',
                    toggle() {
                        this.open = !this.open;
                        localStorage.setItem('settingsMenuOpen', this.open ? '1' : '0');
                    },
                }"
            >
                <button type="button" @click="toggle()"
                    class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-colors duration-150 {{ $adminLink($isSettingsActive) }}">
                    <svg class="mr-3 h-5 w-5 flex-shrink-0 opacity-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Pengaturan
                    <svg class="ml-auto h-4 w-4 flex-shrink-0 transition-transform duration-150"
                        :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                <div x-show="open" x-cloak class="mt-1.5 space-y-0.5 rounded-2xl bg-black/10 p-1.5 ring-1 ring-white/10">
                    <a href="{{ route('settings.work-hours.edit') }}"
                        class="flex w-full items-center rounded-xl px-3.5 py-2 text-sm font-medium transition-colors duration-150
                            {{ request()->routeIs('settings.work-hours.*') ? 'bg-white/20 text-white' : 'text-red-100/90 hover:bg-white/10 hover:text-white dark:text-slate-400 dark:hover:text-slate-200' }}">
                        <span class="mr-3 h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('settings.work-hours.*') ? 'bg-white' : 'bg-red-300/60 dark:bg-slate-500' }}"></span>
                        Jam Kerja
                    </a>
                    <a href="{{ route('settings.security-schedules.index') }}"
                        class="flex w-full items-center rounded-xl px-3.5 py-2 text-sm font-medium transition-colors duration-150
                            {{ request()->routeIs('settings.security-schedules.*') ? 'bg-white/20 text-white' : 'text-red-100/90 hover:bg-white/10 hover:text-white dark:text-slate-400 dark:hover:text-slate-200' }}">
                        <span class="mr-3 h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('settings.security-schedules.*') ? 'bg-white' : 'bg-red-300/60 dark:bg-slate-500' }}"></span>
                        Jadwal Security
                    </a>
                    <a href="{{ route('settings.location.edit') }}"
                        class="flex w-full items-center rounded-xl px-3.5 py-2 text-sm font-medium transition-colors duration-150
                            {{ request()->routeIs('settings.location.*') ? 'bg-white/20 text-white' : 'text-red-100/90 hover:bg-white/10 hover:text-white dark:text-slate-400 dark:hover:text-slate-200' }}">
                        <span class="mr-3 h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('settings.location.*') ? 'bg-white' : 'bg-red-300/60 dark:bg-slate-500' }}"></span>
                        Lokasi GPS
                    </a>
                    <a href="{{ route('work-calendars.index') }}"
                        class="flex w-full items-center rounded-xl px-3.5 py-2 text-sm font-medium transition-colors duration-150
                            {{ request()->routeIs('work-calendars.*') ? 'bg-white/20 text-white' : 'text-red-100/90 hover:bg-white/10 hover:text-white dark:text-slate-400 dark:hover:text-slate-200' }}">
                        <span class="mr-3 h-1.5 w-1.5 flex-shrink-0 rounded-full {{ request()->routeIs('work-calendars.*') ? 'bg-white' : 'bg-red-300/60 dark:bg-slate-500' }}"></span>
                        Kalender Kerja
                    </a>
                </div>
            </div>
        @else
            <p class="mb-3 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-red-200/80 dark:text-slate-500">
                Menu Karyawan
            </p>

            <a href="{{ route('attendance.index') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $employeeLink(request()->routeIs('attendance.index')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Absensi
                @if (request()->routeIs('attendance.index'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500 dark:bg-white"></span>
                @endif
            </a>

            <a href="{{ route('attendance.history') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $employeeLink(request()->routeIs('attendance.history')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Riwayat Absensi
                @if (request()->routeIs('attendance.history'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500 dark:bg-white"></span>
                @endif
            </a>

            <a href="{{ route('leave-verification.my-submissions') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $employeeLink(request()->routeIs('leave-verification.my-submissions')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Status Pengajuan
                @if (request()->routeIs('leave-verification.my-submissions'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500 dark:bg-white"></span>
                @endif
            </a>

            <a href="{{ route('my-payrolls.index') }}"
                class="flex w-full items-center rounded-2xl px-3.5 py-2.5 text-sm font-semibold transition-all duration-200 {{ $employeeLink(request()->routeIs('my-payrolls.*')) }}">
                <svg class="mr-3 h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z" />
                </svg>
                Slip Gaji
                @if (request()->routeIs('my-payrolls.*'))
                    <span class="ml-auto h-1.5 w-1.5 rounded-full bg-red-500 dark:bg-white"></span>
                @endif
            </a>
        @endif

    </nav>

    <div class="relative flex-shrink-0 border-t border-white/10 p-4">
        <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/10 p-3 shadow-inner shadow-black/5 backdrop-blur-sm">
            @if ($sidebarPhotoUrl)
                <img src="{{ $sidebarPhotoUrl }}" alt="Foto profil {{ $authUser->name }}"
                    class="h-10 w-10 flex-shrink-0 rounded-xl object-cover shadow-sm ring-2 ring-white/25">
            @else
                <span class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-white/10 text-red-50 shadow-sm ring-1 ring-white/15">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0Zm4 10a7 7 0 00-14 0" />
                    </svg>
                </span>
            @endif
            <span class="min-w-0">
                <span class="block truncate text-sm font-semibold text-white">{{ $authUser->name }}</span>
                <span class="mt-0.5 block truncate text-[11px] text-red-100/85">{{ $authUser->email }}</span>
            </span>
        </div>
    </div>
</aside>
