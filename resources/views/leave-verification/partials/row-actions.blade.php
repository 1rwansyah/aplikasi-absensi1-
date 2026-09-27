@props(['leave', 'compact' => false])

@php
    $btnSize = $compact
        ? 'h-6 w-6 rounded-md'
        : 'h-11 w-11 rounded-xl';
@endphp

<div class="flex items-center {{ $compact ? 'justify-center gap-1' : 'gap-2' }}">
    <div class="group relative inline-flex">
        <button type="button"
            onclick="openLeavePreview(this)"
            aria-label="Preview"
            data-name="{{ $leave->user->name }}"
            data-date="{{ $leave->date->format('d M Y') }}"
            data-type="{{ $leave->type->label() }}"
            data-type-value="{{ $leave->type->value }}"
            data-status="{{ $leave->verification_status->label() }}"
            data-note="{{ e($leave->leaveNoteText() ?? '') }}"
            data-proof-url="{{ $leave->hasDoctorNote() ? $leave->doctorNoteViewUrl() : '' }}"
            data-proof-image="{{ $leave->doctorNoteIsImage() ? '1' : '0' }}"
            data-leave-id="{{ $leave->id }}"
            data-pending="{{ $leave->verification_status->isPending() ? '1' : '0' }}"
            class="inline-flex {{ $btnSize }} items-center justify-center bg-blue-600 text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
        </button>
        <span
            class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
            Preview
        </span>
    </div>

    @if ($leave->verification_status->isPending())
        <form method="POST" action="{{ route('leave-verification.verify', $leave) }}" class="hidden" id="approve-form-{{ $leave->id }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="verification_status" value="done">
        </form>
        <form method="POST" action="{{ route('leave-verification.verify', $leave) }}" class="hidden" id="reject-form-{{ $leave->id }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="verification_status" value="no_done">
            <input type="hidden" name="rejection_reason" value="">
        </form>

        <div class="group relative inline-flex">
            <button type="button"
                onclick="event.preventDefault(); window.LeaveVerificationModule?.openApprove({{ $leave->id }}, @js($leave->user->name), @js($leave->date->format('d M Y')))"
                aria-label="Setujui"
                class="inline-flex {{ $btnSize }} items-center justify-center bg-green-600 text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </button>
            <span
                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                Setujui
            </span>
        </div>
        <div class="group relative inline-flex">
            <button type="button"
                onclick="event.preventDefault(); window.LeaveVerificationModule?.openReject({{ $leave->id }}, @js($leave->user->name), @js($leave->date->format('d M Y')), @js($leave->type->value))"
                aria-label="Tolak"
                class="inline-flex {{ $btnSize }} items-center justify-center bg-red-600 text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <span
                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                Tolak
            </span>
        </div>
    @endif
</div>
