@props(['label', 'time' => null, 'badgeText' => null, 'badgeClass' => 'bg-green-100 text-green-800', 'note' => null])

<div class="rounded-xl border border-gray-100 bg-gray-50/50 p-5 dark:border-gray-700 dark:bg-gray-700/50">
    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $label }}</p>
    <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ $time ?? '—' }}</p>
    @if ($badgeText)
        <span class="mt-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
            {{ $badgeText }}
        </span>
    @endif
    @if ($note)
        <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">{{ $note }}</p>
    @endif
</div>
