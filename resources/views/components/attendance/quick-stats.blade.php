@props([
    'settings',
    'hasFaceRegistered' => false,
    'needsFaceDescriptorSync' => false,
    'profilePhotoUrl' => null,
    'holidayLabel' => null,
    'todaySchedule' => null,
])

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4 xl:grid-cols-4">
    <x-attendance.attendance-card class="group relative border-t-2 border-t-emerald-500 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
        <div class="pointer-events-none absolute -right-5 -top-5 h-20 w-20 rounded-full bg-emerald-50 dark:bg-emerald-900/10"></div>
        <div class="relative">
            <div class="flex items-center justify-between gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:ring-emerald-800/50">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 3H5a2 2 0 00-2 2v3m18 0V5a2 2 0 00-2-2h-3M3 16v3a2 2 0 002 2h3m8 0h3a2 2 0 002-2v-3M15 11a3 3 0 11-6 0 3 3 0 016 0Z" />
                    </svg>
                </span>
                @if ($profilePhotoUrl && ($hasFaceRegistered || $needsFaceDescriptorSync))
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Aktif
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Perlu HR
                    </span>
                @endif
            </div>
            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Verifikasi Wajah</p>
            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                {{ $profilePhotoUrl && ($hasFaceRegistered || $needsFaceDescriptorSync) ? 'Siap digunakan' : 'Belum tersedia' }}
            </p>
            <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                {{ $profilePhotoUrl && ($hasFaceRegistered || $needsFaceDescriptorSync) ? 'Kamera aktif saat absensi' : 'Foto profil belum siap' }}
            </p>
        </div>
    </x-attendance.attendance-card>

    <x-attendance.attendance-card class="group relative border-t-2 border-t-blue-500 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
        <div class="pointer-events-none absolute -right-5 -top-5 h-20 w-20 rounded-full bg-blue-50 dark:bg-blue-900/10"></div>
        <div class="relative">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-100 dark:bg-blue-900/20 dark:text-blue-400 dark:ring-blue-800/50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 21s7-5.1 7-12a7 7 0 10-14 0c0 6.9 7 12 7 12Z" />
                    <circle cx="12" cy="9" r="2.5" stroke-width="2" />
                </svg>
            </span>
            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Lokasi GPS</p>
            <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">
                {{ $settings->hasGeofence() ? $settings->formattedAttendanceRadius() : 'Tercatat' }}
            </p>
            <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                {{ $settings->hasGeofence() ? 'Radius dari lokasi kantor' : 'GPS digunakan saat absensi' }}
            </p>
        </div>
    </x-attendance.attendance-card>

    <x-attendance.attendance-card class="group relative min-w-0 border-t-2 border-t-violet-500 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
        <div class="pointer-events-none absolute -right-5 -top-5 h-20 w-20 rounded-full bg-violet-50 dark:bg-violet-900/10"></div>
        <div class="relative">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 ring-1 ring-inset ring-violet-100 dark:bg-violet-900/20 dark:text-violet-400 dark:ring-violet-800/50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9" stroke-width="2" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7v5l3 2" />
                </svg>
            </span>
            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Jam Kerja</p>
            @if ($todaySchedule && !$todaySchedule->is_off)
                <p class="mt-1 break-words text-lg font-bold text-gray-900 dark:text-white">{{ $todaySchedule->name }}</p>
                <p class="mt-1 break-words text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                    Masuk {{ $todaySchedule->formattedClockInWindow() }}
                    <span class="block">Pulang {{ $todaySchedule->formattedClockOutWindow() }}</span>
                </p>
            @else
                <p class="mt-1 break-words text-lg font-bold text-gray-900 dark:text-white">{{ $settings->formattedOfficeStart() }} – {{ $settings->formattedClockOutLimit() }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Rentang kerja reguler</p>
            @endif
        </div>
    </x-attendance.attendance-card>

    <x-attendance.attendance-card class="group relative border-t-2 border-t-amber-500 p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
        <div class="pointer-events-none absolute -right-5 -top-5 h-20 w-20 rounded-full bg-amber-50 dark:bg-amber-900/10"></div>
        <div class="relative">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 ring-1 ring-inset ring-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-800/50">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0Z" />
                </svg>
            </span>
            <p class="mt-5 text-xs font-semibold uppercase tracking-[0.12em] text-gray-400 dark:text-gray-500">Status Hari Ini</p>
            @if ($todaySchedule?->is_off)
                <p class="mt-1 text-lg font-bold text-gray-900 dark:text-white">Libur</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tidak perlu melakukan absensi</p>
            @elseif ($holidayLabel)
                <p class="mt-1 text-lg font-bold text-amber-700 dark:text-amber-400">Libur</p>
                <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $holidayLabel }}">{{ $holidayLabel }}</p>
            @else
                <p class="mt-1 text-lg font-bold text-emerald-700 dark:text-emerald-400">Buka</p>
                <p class="mt-1 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Siap melakukan absensi
                </p>
            @endif
        </div>
    </x-attendance.attendance-card>
</div>
