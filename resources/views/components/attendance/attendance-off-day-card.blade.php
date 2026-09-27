@props([
    'scheduleName' => 'Libur',
])

<x-attendance.attendance-card class="border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/30">
    <div class="flex gap-4 px-6 py-5 sm:items-center">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gray-200 dark:bg-gray-700">
            <svg class="h-5 w-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 12H4" />
            </svg>
        </div>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                Hari Libur — {{ $scheduleName }}
            </p>
            <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                Anda tidak perlu melakukan absensi masuk atau pulang hari ini. Jika ada keperluan izin/sakit, hubungi HR/Admin.
            </p>
        </div>
    </div>
</x-attendance.attendance-card>
