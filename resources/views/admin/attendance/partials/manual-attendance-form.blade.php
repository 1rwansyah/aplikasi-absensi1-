@php
    $prefix = $mode === 'edit' ? 'manual_edit' : 'manual_create';
    $attendanceModel = $attendance ?? null;
    $defaultClockInDate = old(
        'clock_in_date',
        $attendanceModel?->date->toDateString() ?? $defaultDate->toDateString(),
    );
    $inferredClockOutDate = $attendanceModel && isset($inferredClockOutDate)
        ? $inferredClockOutDate
        : ($attendanceModel?->date->toDateString() ?? $defaultDate->toDateString());
    $defaultClockOutDate = old(
        'clock_out_date',
        old('clock_in_date', $attendanceModel && $attendanceModel->clock_out_time ? $inferredClockOutDate : $defaultClockInDate),
    );
    $defaultType = old('type', $attendanceModel?->type->value ?? 'regular');
    $defaultStatus = old('status', $attendanceModel?->status->value ?? 'on_time');
    $defaultClockInTime = old('clock_in_time', $attendanceModel?->formattedClockIn());
    $defaultClockOutTime = old('clock_out_time', $attendanceModel?->formattedClockOut());
    if ($mode === 'edit') {
        $defaultClockInReport = old('clock_in_report', $attendanceModel?->clock_in_report ?? '');
        $defaultClockOutReport = old('clock_out_report', $attendanceModel?->clock_out_report ?? '');
    } else {
        $defaultClockInReport = old('clock_in_report', 'Koreksi admin');
        $defaultClockOutReport = old('clock_out_report', 'Koreksi admin');
    }
    $defaultLeaveNote = old('leave_note', $attendanceModel?->leave_note);
    $employeeLabel = $attendanceModel?->user?->employee
        ? "{$attendanceModel->user->employee->name} ({$attendanceModel->user->employee->employee_code})"
        : ($attendanceModel?->user?->name ?? '');
    $adminEditorClass = 'block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100';
@endphp

<div class="grid gap-x-6 gap-y-4 md:grid-cols-2">

    @if ($mode === 'edit')
        <input type="hidden" name="user_id" value="{{ $attendanceModel?->user_id }}">

        <div class="md:col-span-2">
            <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Karyawan</label>
            <input type="text" id="{{ $prefix }}_employee_display" readonly disabled
                value="{{ $employeeLabel }}"
                class="block w-full cursor-not-allowed rounded-xl border-gray-200 bg-gray-50 text-sm text-gray-700 dark:border-gray-600 dark:bg-gray-900/60 dark:text-gray-300">
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Karyawan tidak dapat diubah saat koreksi absensi.</p>
        </div>
    @else
        <input type="hidden" name="user_id" :value="selectedUserId">

        <div class="relative md:col-span-2">
            <label for="{{ $prefix }}_employee_search"
                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Karyawan
                <span class="text-red-500">*</span></label>
            <div class="relative">
                <input type="text" id="{{ $prefix }}_employee_search" autocomplete="off" x-model="employeeQuery"
                    @input="onEmployeeInput()" @focus="showSuggestions = true" @keydown.escape="showSuggestions = false"
                    @blur="closeSuggestions()" placeholder="Ketik nama atau kode karyawan..."
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    :class="employeeError ? 'border-red-500 focus:border-red-500 focus:ring-red-500' : ''">
                <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div x-show="showSuggestions && filteredEmployees.length > 0" x-cloak
                class="absolute z-30 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-600 dark:bg-gray-900">
                <template x-for="employee in filteredEmployees" :key="employee.user_id">
                    <button type="button" @mousedown.prevent="selectEmployee(employee)"
                        class="flex w-full items-center px-4 py-2.5 text-left text-sm text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800">
                        <span x-text="employee.label"></span>
                    </button>
                </template>
            </div>

            <p x-show="employeeError" x-cloak class="mt-1 text-xs text-red-600">
                Pilih karyawan dari daftar suggestion.
            </p>
            @error('user_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div id="{{ $prefix }}_schedule_panel"
        class="md:col-span-2 hidden rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900/40">
        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-gray-100">Informasi Jadwal</p>
        <dl class="grid gap-3 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Work Schedule</dt>
                <dd id="{{ $prefix }}_schedule_name" class="mt-1 font-medium text-gray-900 dark:text-gray-100">—</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Shift Type</dt>
                <dd id="{{ $prefix }}_schedule_shift_type" class="mt-1 font-medium text-gray-900 dark:text-gray-100">—</dd>
            </div>
            <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Jam Kerja</dt>
                <dd id="{{ $prefix }}_schedule_work_hours" class="mt-1 font-medium text-gray-900 dark:text-gray-100">—</dd>
            </div>
        </dl>
        <p id="{{ $prefix }}_schedule_off_notice" class="mt-2 hidden text-xs text-amber-700 dark:text-amber-400">
            Hari ini jadwal libur untuk karyawan ini.
        </p>
    </div>

    <div>
        <label for="{{ $prefix }}_type"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipe Absensi
            <span class="text-red-500">*</span></label>
        <select name="type" id="{{ $prefix }}_type" x-model="type" required
            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @foreach ($types as $typeOption)
                <option value="{{ $typeOption->value }}" @selected($defaultType === $typeOption->value)>
                    {{ $typeOption->label() }}
                </option>
            @endforeach
        </select>
        @error('type')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="{{ $prefix }}_status"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Status
            <span class="text-red-500">*</span></label>
        <select name="status" id="{{ $prefix }}_status" required
            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @foreach ($statuses as $statusOption)
                <option value="{{ $statusOption->value }}" @selected($defaultStatus === $statusOption->value)>
                    {{ $statusOption->label() }}
                </option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2 border-t border-gray-200 pt-4 dark:border-gray-700">
        <p class="font-semibold text-gray-900 dark:text-gray-100">Waktu Absensi</p>
    </div>

    <div>
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Clock In</p>
        <div class="space-y-4">
            <div>
                <label for="{{ $prefix }}_clock_in_date"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Masuk
                    <span class="text-red-500">*</span></label>
                <input type="date" name="clock_in_date" id="{{ $prefix }}_clock_in_date" x-model="clockInDate"
                    @change="syncClockOutDate($event)" value="{{ $defaultClockInDate }}" required
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                @error('clock_in_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="isRegular()" x-cloak>
                <label for="{{ $prefix }}_clock_in_time"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Masuk
                    <span class="text-red-500">*</span></label>
                <input type="time" name="clock_in_time" id="{{ $prefix }}_clock_in_time"
                    value="{{ $defaultClockInTime }}" :required="isRegular()"
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                @error('clock_in_time')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div x-show="isRegular()" x-cloak>
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Clock Out</p>
        <div class="space-y-4">
            <div>
                <label for="{{ $prefix }}_clock_out_date"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Pulang</label>
                <input type="date" name="clock_out_date" id="{{ $prefix }}_clock_out_date"
                    x-ref="clockOutDate" @change="markClockOutDateTouched()" value="{{ $defaultClockOutDate }}"
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                @error('clock_out_date')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Default sama dengan tanggal masuk. Untuk
                    security overnight, ubah ke H+1.</p>
            </div>

            <div>
                <label for="{{ $prefix }}_clock_out_time"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Pulang</label>
                <input type="time" name="clock_out_time" id="{{ $prefix }}_clock_out_time"
                    value="{{ $defaultClockOutTime }}"
                    class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                @error('clock_out_time')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika belum pulang. Lembur dihitung
                    otomatis jika diisi.</p>
            </div>
        </div>
    </div>

    <div x-show="isRegular()" x-cloak class="md:col-span-2 border-t border-gray-200 pt-4 dark:border-gray-700">
        <p class="mb-4 font-semibold text-gray-900 dark:text-gray-100">Laporan</p>
        <div class="grid gap-x-6 gap-y-4 md:grid-cols-2">
            <div>
                <label for="{{ $prefix }}_clock_in_report"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Laporan Masuk</label>
                <x-attendance.report-editor
                    name="clock_in_report"
                    :id="$prefix . '_clock_in_report'"
                    rows="3"
                    :value="$defaultClockInReport"
                    :textareaClass="$adminEditorClass"
                />
                @error('clock_in_report')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="{{ $prefix }}_clock_out_report"
                    class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Laporan Pulang</label>
                <x-attendance.report-editor
                    name="clock_out_report"
                    :id="$prefix . '_clock_out_report'"
                    rows="3"
                    :value="$defaultClockOutReport"
                    :textareaClass="$adminEditorClass"
                />
                @error('clock_out_report')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div x-show="isLeave()" x-cloak class="md:col-span-2">
        <label for="{{ $prefix }}_leave_note"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan Izin/Sakit</label>
        <x-attendance.report-editor
            name="leave_note"
            :id="$prefix . '_leave_note'"
            rows="3"
            :value="$defaultLeaveNote ?? ''"
            :textareaClass="$adminEditorClass"
        />
        @error('leave_note')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2 border-t border-gray-200 pt-4 dark:border-gray-700">
        <label for="{{ $prefix }}_manual_reason"
            class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan Koreksi Manual
            <span class="text-red-500">*</span></label>
        <textarea name="manual_reason" id="{{ $prefix }}_manual_reason" rows="3" required
            placeholder="Contoh: Lupa absen masuk dan pulang, server bermasalah, koreksi HR"
            class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('manual_reason') }}</textarea>
        @error('manual_reason')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
