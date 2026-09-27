@php
    $navigationTitle = match (true) {
        request()->routeIs('attendance.index') => 'Absensi',
        request()->routeIs('attendance.history') => 'Riwayat Absensi',
        request()->routeIs('leave-verification.my-submissions') => 'Status Pengajuan',
        request()->routeIs('my-payrolls.*') => 'Slip Gaji',
        request()->routeIs('profile.edit') => 'Profil Saya',
        request()->routeIs('dashboard') => 'Dashboard',
        request()->routeIs('employees.*') => 'Data Karyawan',
        request()->routeIs('admin.attendance.*') => 'Admin Absensi',
        request()->routeIs('leave-verification.*') => 'Verifikasi Pengajuan',
        request()->routeIs('payrolls.*') => 'Penggajian',
        request()->routeIs('activity-log.*') => 'Log Aktivitas',
        request()->routeIs('settings.*', 'work-calendars.*') => 'Pengaturan',
        default => 'SADAR-PRI',
    };
    $navigationOnAdmin = \App\Support\NavigationContext::onAdminRoute();
    $navigationSpace = $navigationOnAdmin ? 'Portal Admin' : 'Portal Karyawan';
    $navigationRole = $navigationOnAdmin ? 'Administrator' : 'Karyawan';
    $navigationPhotoUrl = Auth::user()->employee?->profilePhotoUrl();
@endphp

<nav x-data="{ open: false }"
    class="sticky top-0 z-50 border-b border-gray-200/70 bg-white/90 shadow-[0_1px_0_rgba(15,23,42,0.03)] backdrop-blur-xl transition-colors duration-300 ease-in-out dark:border-gray-700/70 dark:bg-gray-900/90 dark:shadow-none">
    <div class="mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-[4.5rem] items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    id="sidebar-toggle-btn"
                    type="button"
                    class="inline-flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl border border-gray-200/80 bg-white text-gray-500 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-red-800 dark:hover:bg-red-950/40 dark:hover:text-red-300 dark:focus:ring-offset-gray-900"
                    aria-label="Toggle Sidebar"
                    aria-expanded="true"
                >
                    <svg id="sidebar-icon-open" class="hidden h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg id="sidebar-icon-close" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex shrink-0 items-center md:hidden">
                    <a href="{{ auth()->user()->homeUrl() }}" class="rounded-xl p-1 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                        <img src="{{ asset('images/logo/logo_SADAR.png') }}" alt="SADAR-PRI Logo" class="block h-9 w-auto object-contain">
                    </a>
                </div>

                <div class="hidden min-w-0 items-center gap-3 sm:flex">
                    <span class="hidden h-8 w-px bg-gray-200 dark:bg-gray-700 sm:block"></span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] {{ $navigationOnAdmin ? 'bg-red-50 text-red-700 ring-1 ring-red-100 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900/50' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700' }}">
                                {{ $navigationSpace }}
                            </span>
                        </div>
                        <p class="mt-1 truncate text-sm font-bold tracking-tight text-gray-900 dark:text-white">{{ $navigationTitle }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 ms-auto">
                <script>
                    function toggleTheme() {
                        const html = document.documentElement;
                        if (html.classList.contains('dark')) {
                            html.classList.remove('dark');
                            localStorage.theme = 'light';
                        } else {
                            html.classList.add('dark');
                            localStorage.theme = 'dark';
                        }
                    }
                </script>

                <button onclick="toggleTheme()"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-gray-200/80 bg-white text-gray-500 shadow-sm transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-red-800 dark:hover:bg-red-950/40 dark:hover:text-red-300"
                    aria-label="Toggle Dark Mode">
                    <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="block h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex max-w-[13rem] items-center gap-2.5 rounded-2xl border border-gray-200/80 bg-white px-1.5 py-1.5 text-left shadow-sm transition hover:border-red-200 hover:bg-red-50/60 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:hover:border-red-800 dark:hover:bg-red-950/30 sm:max-w-[17rem] sm:pr-3">
                            @if ($navigationPhotoUrl)
                                <img src="{{ $navigationPhotoUrl }}" alt="Foto profil {{ Auth::user()->name }}"
                                    class="h-9 w-9 flex-shrink-0 rounded-xl object-cover ring-1 ring-gray-200 dark:ring-gray-600">
                            @else
                                <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-rose-600 text-white shadow-sm">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0Zm4 10a7 7 0 00-14 0" />
                                    </svg>
                                </span>
                            @endif
                            <span class="hidden min-w-0 sm:block">
                                <span class="block truncate text-sm font-semibold leading-tight text-gray-800 dark:text-gray-100">{{ Auth::user()->name }}</span>
                                <span class="mt-0.5 block text-[11px] leading-tight text-gray-400 dark:text-gray-500">{{ $navigationRole }}</span>
                            </span>
                            <svg class="mr-1 hidden h-4 w-4 flex-shrink-0 text-gray-400 sm:block" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        @php
                            $navUser = auth()->user();
                            $navHasEmp = $navUser->employee !== null;
                            $navIsDual = $navUser->isAdmin() && $navHasEmp;
                            $navOnAdmin = \App\Support\NavigationContext::onAdminRoute();
                        @endphp

                        @if ($navIsDual)
                            @if ($navOnAdmin)
                                <x-dropdown-link :href="route('attendance.index')">
                                    Absensi Karyawan
                                </x-dropdown-link>
                            @else
                                <x-dropdown-link :href="route('dashboard')">
                                    Admin Panel
                                </x-dropdown-link>
                            @endif
                        @endif

                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>
                        <x-logout-form variant="dropdown" />
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
