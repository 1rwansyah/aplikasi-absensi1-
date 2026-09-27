@props([
    'name' => null,
    'id' => null,
    'rows' => 5,
    'required' => false,
    'placeholder' => '',
    'value' => '',
    'reportField' => false,
    'textareaClass' => 'w-full rounded-xl border border-gray-200 px-4 py-3 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:placeholder-gray-500',
])

@php
    $inputId = $id ?? ($name ?: 'attendance-report');
@endphp

<div {{ $attributes->class(['ckeditor-container'])->merge(['data-attendance-ckeditor' => true]) }}>
    <textarea @if ($name) name="{{ $name }}" @endif id="{{ $inputId }}" rows="{{ $rows }}"
        @if ($required) required @endif @if ($reportField) data-attendance-report @endif
        placeholder="{{ $placeholder }}" class="{{ $textareaClass }}">{{ $value }}</textarea>
</div>
