@props(['clockOutOpensAt', 'clockOutWindow'])

<div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-800/50 dark:bg-amber-950/30">
    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">Belum Waktunya Absen Pulang</p>
        <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-400">
            Dibuka mulai <strong>{{ $clockOutOpensAt }}</strong> · rentang {{ $clockOutWindow }}
        </p>
    </div>
</div>
