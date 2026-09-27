@props(['attendance'])

@if ($attendance->hasDoctorNote())
    <div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
        @if ($attendance->doctorNoteIsImage())
            <a href="{{ $attendance->doctorNoteViewUrl() }}" target="_blank" rel="noopener noreferrer"
                class="group relative inline-flex"
                title="Buka bukti {{ $attendance->type->label() }}">
                <img src="{{ $attendance->doctorNoteViewUrl() }}" alt="Bukti {{ $attendance->type->label() }}"
                    class="h-10 w-10 rounded-lg border border-gray-200 object-cover dark:border-gray-600">
                <span
                    class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                    Lihat gambar
                </span>
            </a>
        @else
            <a href="{{ $attendance->doctorNoteViewUrl() }}" target="_blank" rel="noopener noreferrer"
                class="group relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-red-50 text-red-600 transition hover:bg-red-100 dark:border-gray-600 dark:bg-red-950/40 dark:text-red-400 dark:hover:bg-red-950/60"
                title="Buka PDF bukti {{ $attendance->type->label() }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
                <span
                    class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                    Buka PDF
                </span>
            </a>
        @endif
    </div>
@else
    <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
@endif
