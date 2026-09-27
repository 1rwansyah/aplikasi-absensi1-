@props([
    'title',
    'subtitle' => null,
    'badge' => null,
])

<div {{ $attributes->merge([
    'class' => 'group relative flex min-h-[24rem] flex-col overflow-hidden rounded-3xl border border-gray-200/70 bg-gradient-to-br from-red-50/70 via-white to-white shadow-sm transition duration-200 hover:shadow-md dark:border-gray-700 dark:from-red-950/20 dark:via-gray-800 dark:to-gray-800',
]) }}>
    <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-red-400/15 blur-2xl"></div>

    <div class="relative flex flex-shrink-0 items-start justify-between gap-3 px-5 pb-1 pt-5 sm:px-6 sm:pt-6">
        <div class="flex min-w-0 items-start gap-3">
            <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">
                {{ $icon ?? '' }}
            </div>
            <div class="min-w-0 pt-0.5">
                <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white sm:text-lg">{{ $title }}</h2>
                @if ($subtitle)
                    <p class="mt-0.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @if ($badge !== null)
            <span class="inline-flex flex-shrink-0 items-center rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 ring-1 ring-inset ring-red-100 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900/50">
                {{ $badge }}
            </span>
        @endif
    </div>

    <div class="relative min-h-0 flex-1 overflow-y-auto px-5 pb-5 pt-3 sm:px-6 sm:pb-6">
        {{ $slot }}
    </div>
</div>
