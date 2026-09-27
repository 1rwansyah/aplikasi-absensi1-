@props(['type', 'message'])

@php
    $classes = $type === 'success'
        ? 'border-green-200 bg-green-50 text-green-700'
        : 'border-red-200 bg-red-50 text-red-700';
@endphp

<div {{ $attributes->merge(['class' => "rounded-xl border px-4 py-3 text-sm font-medium {$classes}"]) }}>
    {{ $message }}
</div>
