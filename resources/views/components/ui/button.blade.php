@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
])

@php
    $isIconVariant = in_array($variant, ['icon', 'icon-amber', 'icon-danger']);

    $baseClasses = 'inline-flex items-center justify-center gap-2 font-medium transition shadow-sm';

    $sizeClasses = $isIconVariant
        ? ''
        : match ($size) {
            'sm' => 'px-3 py-1.5 text-xs rounded-lg',
            'md' => 'px-4 py-2 text-sm rounded-xl',
            'lg' => 'px-5 py-2.5 text-sm rounded-xl',
            default => 'px-4 py-2 text-sm rounded-xl',
        };

    $variantClasses = match ($variant) {
        'primary'
            => 'bg-red-600 text-white hover:bg-red-700 dark:bg-gray-800 dark:text-gray-100 dark:border dark:border-gray-700 dark:hover:bg-gray-700',

        'secondary'
            => 'border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700',

        'ghost' => 'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800',

        'danger'
            => 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30',

        'success'
            => 'bg-green-50 text-green-600 hover:bg-green-100 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30',

        'icon'
            => 'inline-flex h-9 w-9 items-center justify-center rounded-xl text-gray-400 hover:bg-gray-50 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-300',

        'icon-amber'
            => 'inline-flex h-9 w-9 items-center justify-center rounded-xl text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-500/10',

        'icon-danger'
            => 'inline-flex h-9 w-9 items-center justify-center rounded-xl text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10',

        default => 'bg-red-600 text-white hover:bg-red-700',
    };
@endphp

<button type="{{ $type }}"
    {{ $attributes->merge([
        'class' => "{$baseClasses} {$sizeClasses} {$variantClasses}",
    ]) }}>
    {{ $slot }}
</button>
