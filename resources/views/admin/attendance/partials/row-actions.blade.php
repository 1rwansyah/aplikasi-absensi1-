@props(['row', 'attendance', 'payrollLockedAttendanceIds' => [], 'compact' => false])

@php
    $btnSize = $compact ? 'h-8 w-8' : 'h-11 w-11';
@endphp

@if ($attendance)
    <div class="flex items-center gap-1">
        <div class="group relative inline-flex">
            <button type="button"
                onclick="openAdminAttendanceDetail(@js($row->user->name), @js([
                    'status' => $row->statusLabel(),
                    'clock_in' => $row->clockIn(),
                    'clock_out' => $row->clockOut(),
                    'clock_in_report' => $attendance->clockInReportHtml(),
                    'clock_out_report' => $attendance->clockOutReportHtml(),
                    'clock_in_photo' => $attendance->clockInVerificationPhotoUrl(),
                    'clock_out_photo' => $attendance->clockOutVerificationPhotoUrl(),
                    'clock_in_lat' => $attendance->clock_in_latitude,
                    'clock_in_lng' => $attendance->clock_in_longitude,
                    'clock_out_lat' => $attendance->clock_out_latitude,
                    'clock_out_lng' => $attendance->clock_out_longitude,
                    'clock_in_location' => $attendance->clockInLocationLabel(),
                    'clock_out_location' => $attendance->clockOutLocationLabel(),
                    'clock_in_face_distance' => $attendance->clock_in_face_distance,
                    'clock_out_face_distance' => $attendance->clock_out_face_distance,
                    'clock_in_face_match_percent' => $attendance->clockInFaceMatchPercent(),
                    'clock_out_face_match_percent' => $attendance->clockOutFaceMatchPercent(),
                    'leave_note' => $attendance->leaveNoteHtml(),
                    'doctor_note_url' => $attendance->hasDoctorNote() ? $attendance->doctorNoteViewUrl() : null,
                ]))"
                class="inline-flex {{ $btnSize }} items-center justify-center rounded-lg text-gray-400 transition hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-gray-800 dark:hover:text-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
            <span
                class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                Detail
            </span>
        </div>

        @if (! in_array($attendance->id, $payrollLockedAttendanceIds, true))
            <div class="group relative inline-flex">
                <button type="button"
                    data-action="open-manual-attendance-edit"
                    data-attendance-id="{{ $attendance->id }}"
                    class="inline-flex {{ $btnSize }} items-center justify-center rounded-lg text-gray-400 transition hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-gray-800 dark:hover:text-amber-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </button>
                <span
                    class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                    Edit
                </span>
            </div>
        @else
            <div class="group relative inline-flex">
                <span
                    class="inline-flex {{ $btnSize }} cursor-not-allowed items-center justify-center rounded-lg text-gray-300 dark:text-gray-600"
                    title="Payroll lunas">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </span>
                <span
                    class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2.5 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 dark:bg-gray-700">
                    Payroll lunas
                </span>
            </div>
        @endif
    </div>
@else
    <span class="text-xs text-gray-400">—</span>
@endif
