@props([
    'pageId' => 'attendance-page-root',
])

<div
    id="{{ $pageId }}"
    data-app-timezone="{{ \App\Support\AppTime::timezone() }}"
    class="flex min-h-full min-w-0 w-full flex-1 flex-col overflow-x-hidden bg-[#f4f5f7] dark:bg-gray-900"
>
    <div class="mx-auto flex min-w-0 w-full max-w-none flex-1 flex-col px-4 py-5 sm:px-6 lg:px-8">
        {{ $slot }}
    </div>
</div>
