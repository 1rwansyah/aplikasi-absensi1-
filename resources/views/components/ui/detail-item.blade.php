@props(['label', 'id'])

<div>
    <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
        {{ $label }}
    </p>
    <p id="{{ $id }}" class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
        -
    </p>
</div>
