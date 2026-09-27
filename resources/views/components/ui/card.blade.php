@props([
    'variant' => 'default',
    'padding' => true,
])
@php
$baseClasses = 'rounded-2xl border shadow-sm';

$variantClasses = match($variant) {
    'green' => 'border-green-100 bg-green-50 dark:border-green-800/40 dark:bg-green-900/20',
    'amber' => 'border-amber-100 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-900/20',
    'red' => 'border-red-100 bg-red-50 dark:border-red-800/40 dark:bg-red-900/20',
    'blue' => 'border-blue-100 bg-blue-50 dark:border-blue-800/40 dark:bg-blue-900/20',
    'purple' => 'border-purple-100 bg-purple-50 dark:border-purple-800/40 dark:bg-purple-900/20',
    'primary' => 'border-red-200 bg-red-600 dark:border-gray-700 dark:bg-gray-800',
    default => 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800',
};

$paddingClass = $padding ? 'p-5' : '';
@endphp

<div {{ $attributes->merge(['class' => "{$baseClasses} {$variantClasses} {$paddingClass}"]) }}>
    {{ $slot }}
</div>
