<div id="toast-stack"
    class="pointer-events-none fixed inset-x-4 top-4 z-[200] flex flex-col gap-3 sm:inset-x-auto sm:right-6 sm:top-6 sm:w-full sm:max-w-sm"
    @if (session('success') || session('error'))
        data-flash='@json(['success' => session('success'), 'error' => session('error')])'
    @endif
    aria-live="polite"
    aria-atomic="true">
</div>
