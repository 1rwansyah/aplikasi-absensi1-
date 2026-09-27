@props([
    'title',
    'canvasId',
    'labels' => null,
    'data' => null,
    'subtitle' => null,
    'accent' => 'blue',
    'badge' => null,
])

@php
    $tones = [
        'blue' => [
            'shell' => 'from-blue-50/80 via-white to-white dark:from-blue-950/30 dark:via-gray-800 dark:to-gray-800',
            'icon' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
            'badge' => 'bg-blue-50 text-blue-700 ring-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-900/50',
            'orb' => 'bg-blue-400/20',
        ],
        'emerald' => [
            'shell' => 'from-emerald-50/80 via-white to-white dark:from-emerald-950/30 dark:via-gray-800 dark:to-gray-800',
            'icon' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300',
            'badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-900/50',
            'orb' => 'bg-emerald-400/20',
        ],
        'rose' => [
            'shell' => 'from-rose-50/80 via-white to-white dark:from-rose-950/30 dark:via-gray-800 dark:to-gray-800',
            'icon' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
            'badge' => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/50',
            'orb' => 'bg-rose-400/20',
        ],
        'slate' => [
            'shell' => 'from-slate-50/90 via-white to-white dark:from-slate-900/40 dark:via-gray-800 dark:to-gray-800',
            'icon' => 'bg-slate-100 text-slate-700 dark:bg-slate-700/60 dark:text-slate-200',
            'badge' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
            'orb' => 'bg-slate-400/20',
        ],
    ];
    $tone = $tones[$accent] ?? $tones['blue'];
@endphp

<div {{ $attributes->merge([
    'class' => "group relative flex min-h-[24rem] flex-col overflow-hidden rounded-3xl border border-gray-200/70 bg-gradient-to-br {$tone['shell']} shadow-sm transition duration-200 hover:shadow-md dark:border-gray-700",
]) }}>
    <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full {{ $tone['orb'] }} blur-2xl"></div>

    <div class="relative flex flex-shrink-0 items-start justify-between gap-3 px-5 pb-1 pt-5 sm:px-6 sm:pt-6">
        <div class="flex min-w-0 items-start gap-3">
            <div class="{{ $tone['icon'] }} flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-2xl">
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
            <span class="{{ $tone['badge'] }} inline-flex flex-shrink-0 items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset">
                {{ $badge }}
            </span>
        @endif
    </div>

    <div class="relative min-h-0 flex-1 px-4 pb-5 pt-3 sm:px-5 sm:pb-6">
        <div class="relative h-full min-h-[15rem] w-full max-w-full rounded-2xl bg-white/70 p-3 ring-1 ring-gray-100/80 dark:bg-gray-900/30 dark:ring-gray-700/60">
            <canvas
                id="{{ $canvasId }}"
                @if ($labels !== null) data-labels='@json($labels)' @endif
                @if ($data !== null) data-data='@json($data)' @endif
            ></canvas>
        </div>
    </div>
</div>
