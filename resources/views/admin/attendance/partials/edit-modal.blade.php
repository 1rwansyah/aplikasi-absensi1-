@php
    $editAttendanceModel = $editAttendance ?? null;
    $editInferredClockOutDate = $editAttendanceModel
        ? app(\App\Services\AdminAttendanceService::class)->inferredClockOutDate($editAttendanceModel)
        : null;

    $oldUserId = old('user_id');
    $oldEmployeeLabel = '';
    if ($oldUserId && isset($employees)) {
        $oldEmployee = $employees->firstWhere('user_id', (int) $oldUserId);
        $oldEmployeeLabel = $oldEmployee ? "{$oldEmployee->name} ({$oldEmployee->employee_code})" : '';
    }

    $employeeOptions = ($employees ?? collect())->map(fn ($employee) => [
        'user_id' => $employee->user_id,
        'name' => $employee->name,
        'code' => $employee->employee_code,
        'label' => "{$employee->name} ({$employee->employee_code})",
    ])->values();

    $defaultClockInDate = old('clock_in_date', $date->toDateString());
    $editDefaultType = old('type', $editAttendanceModel?->type->value ?? 'regular');
@endphp

<div id="manual-attendance-edit-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    style="display:none" data-modal>

    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 opacity-0"
        data-backdrop data-action="close-modal" data-modal-id="manual-attendance-edit-modal"></div>

    <div class="relative mx-auto w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-gray-800 transition-all duration-300 ease-out transform scale-95 opacity-0"
        data-modal-content>

        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Edit Absensi Manual</h3>
                <p id="manual-attendance-edit-subtitle" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    @if ($editAttendanceModel)
                        {{ $editAttendanceModel->user?->name ?? '—' }}
                        &mdash; {{ $editAttendanceModel->date->translatedFormat('l, d F Y') }}
                    @else
                        Koreksi data absensi karyawan.
                    @endif
                </p>
            </div>

            <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                data-action="close-modal" data-modal-id="manual-attendance-edit-modal">✕</button>
        </div>

        <div id="manual-attendance-edit-loading" class="hidden px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
            Memuat data absensi...
        </div>

        <form id="manual-attendance-edit-form" method="POST" action="{{ $editAttendanceModel ? route('admin.attendance.update', $editAttendanceModel) : '#' }}"
            class="{{ $editAttendanceModel ? '' : 'hidden' }}"
            @submit="window.syncAttendanceEditors?.($el)"
            x-data="{
                type: '{{ $editDefaultType }}',
                clockInDate: '{{ old('clock_in_date', $editAttendanceModel?->date->toDateString() ?? $defaultClockInDate) }}',
                clockOutDateTouched: {{ old('clock_out_date') && old('clock_out_date') !== old('clock_in_date', $editAttendanceModel?->date->toDateString() ?? $defaultClockInDate) ? 'true' : 'false' }},
                isRegular() { return this.type === 'regular'; },
                isLeave() { return this.type === 'sick' || this.type === 'permission'; },
                syncClockOutDate(event) {
                    this.clockInDate = event.target.value;
                    if (!this.clockOutDateTouched && this.$refs.clockOutDate) {
                        this.$refs.clockOutDate.value = event.target.value;
                    }
                    window.ManualAttendanceModule?.refreshSchedulePreview('edit', { applyDefaults: false });
                },
                markClockOutDateTouched() {
                    this.clockOutDateTouched = true;
                },
            }">
            @csrf
            @method('PATCH')

            <div class="px-6 py-5">
                @if (session('error') && ($openManualEditAttendanceModal ?? false))
                    <div
                        class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any() && ($openManualEditAttendanceModal ?? false))
                    <div
                        class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @include('admin.attendance.partials.manual-attendance-form', [
                    'mode' => 'edit',
                    'attendance' => $editAttendanceModel,
                    'inferredClockOutDate' => $editInferredClockOutDate,
                    'types' => $types,
                    'statuses' => $statuses,
                    'defaultDate' => $editAttendanceModel?->date ?? $date,
                ])
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                <x-ui.button type="submit" variant="primary">Simpan Koreksi</x-ui.button>
                <x-ui.button type="button" variant="secondary" data-action="close-modal"
                    data-modal-id="manual-attendance-edit-modal">Batal</x-ui.button>
            </div>
        </form>
    </div>
</div>

<script type="application/json" id="manual-attendance-edit-prefill">@json($editAttendanceModel ? app(\App\Services\AdminAttendanceService::class)->editFormData($editAttendanceModel) : null)</script>
