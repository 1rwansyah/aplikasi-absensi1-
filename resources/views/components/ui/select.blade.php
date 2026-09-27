@props([
    'name' => '',
    'id' => '',
])
@php
$baseClasses = 'w-full rounded-xl border-gray-300 py-2 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 sm:w-auto dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-gray-500 dark:focus:ring-gray-500';
@endphp

<select name="{{ $name }}" id="{{ $id }}" {{ $attributes->merge(['class' => $baseClasses]) }}>
    {{ $slot }}
</select>
