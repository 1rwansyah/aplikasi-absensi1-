@props([
    'name' => 'search',
    'value' => '',
    'placeholder' => 'Cari...',
])

<div class="relative w-full sm:w-auto">
    <input
        type="text"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'w-full rounded-xl border-gray-300 py-2 pl-4 pr-10 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 sm:w-64 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500 dark:focus:border-gray-500 dark:focus:ring-gray-500']) }}
    />
    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </div>
</div>
