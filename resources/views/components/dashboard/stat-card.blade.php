@props([
    'label',
    'value',
    'accent' => 'blue',
    'hint' => null,
])

@php
    $tones = [
        'blue' => [
            'bar' => 'bg-blue-500',
            'icon' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300',
            'value' => 'text-gray-900 dark:text-white',
        ],
        'green' => [
            'bar' => 'bg-emerald-500',
            'icon' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300',
            'value' => 'text-gray-900 dark:text-white',
        ],
        'emerald' => [
            'bar' => 'bg-teal-500',
            'icon' => 'bg-teal-50 text-teal-600 dark:bg-teal-950/50 dark:text-teal-300',
            'value' => 'text-gray-900 dark:text-white',
        ],
        'amber' => [
            'bar' => 'bg-amber-500',
            'icon' => 'bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300',
            'value' => 'text-gray-900 dark:text-white',
        ],
        'red' => [
            'bar' => 'bg-red-500',
            'icon' => 'bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-300',
            'value' => 'text-gray-900 dark:text-white',
        ],
        'violet' => [
            'bar' => 'bg-violet-500',
            'icon' => 'bg-violet-50 text-violet-600 dark:bg-violet-950/50 dark:text-violet-300',
            'value' => 'text-gray-900 dark:text-white',
        ],
        'slate' => [
            'bar' => 'bg-slate-500',
            'icon' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
            'value' => 'text-gray-900 dark:text-white',
        ],
    ];

    $tone = $tones[$accent] ?? $tones['blue'];
@endphp

<div {{ $attributes->merge([
    'class' => 'group relative flex h-full min-h-[8rem] flex-col overflow-hidden rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800',
]) }}>
    <div class="absolute inset-x-0 top-0 h-1 {{ $tone['bar'] }}"></div>

    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400">{{ $label }}</p>
            <p class="mt-2 text-3xl font-bold tabular-nums tracking-tight {{ $tone['value'] }}">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $hint }}</p>
            @endif
        </div>
        <div class="{{ $tone['icon'] }} flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl">
            {{ $icon }}
        </div>
    </div>
</div>
