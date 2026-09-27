@php
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
@endphp

<div id="manual-attendance-create-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
    style="display:none" data-modal>

    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 opacity-0"
        data-backdrop data-action="close-modal" data-modal-id="manual-attendance-create-modal"></div>

    <div class="relative mx-auto w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl dark:bg-gray-800 transition-all duration-300 ease-out transform scale-95 opacity-0"
        data-modal-content>

        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">Tambah Absensi Manual</h3>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Buat record absensi untuk karyawan yang belum memiliki data pada tanggal masuk tertentu.
                </p>
            </div>

            <button type="button" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                data-action="close-modal" data-modal-id="manual-attendance-create-modal">✕</button>
        </div>

        <form id="manual-attendance-create-form" method="POST" action="{{ route('admin.attendance.store') }}"
            @submit="window.syncAttendanceEditors?.($el)"
            x-data="{
                type: '{{ old('type', 'regular') }}',
                clockInDate: '{{ $defaultClockInDate }}',
                clockOutDateTouched: {{ old('clock_out_date') && old('clock_out_date') !== $defaultClockInDate ? 'true' : 'false' }},
                employees: @js($employeeOptions),
                selectedUserId: '{{ $oldUserId ?? '' }}',
                employeeQuery: @js($oldEmployeeLabel),
                showSuggestions: false,
                employeeError: {{ $errors->has('user_id') ? 'true' : 'false' }},
                isRegular() { return this.type === 'regular'; },
                isLeave() { return this.type === 'sick' || this.type === 'permission'; },
                get filteredEmployees() {
                    const query = this.employeeQuery.trim().toLowerCase();
                    if (!query) {
                        return [];
                    }
                    return this.employees
                        .filter(employee =>
                            employee.name.toLowerCase().includes(query) ||
                            employee.code.toLowerCase().includes(query) ||
                            employee.label.toLowerCase().includes(query)
                        )
                        .slice(0, 10);
                },
                syncClockOutDate(event) {
                    this.clockInDate = event.target.value;
                    if (!this.clockOutDateTouched && this.$refs.clockOutDate) {
                        this.$refs.clockOutDate.value = event.target.value;
                    }
                    window.ManualAttendanceModule?.refreshSchedulePreview('create');
                },
                markClockOutDateTouched() {
                    this.clockOutDateTouched = true;
                },
                onEmployeeInput() {
                    this.selectedUserId = '';
                    this.showSuggestions = true;
                    this.employeeError = false;
                },
                selectEmployee(employee) {
                    this.selectedUserId = String(employee.user_id);
                    this.employeeQuery = employee.label;
                    this.showSuggestions = false;
                    this.employeeError = false;
                    window.ManualAttendanceModule?.refreshSchedulePreview('create');
                },
                closeSuggestions() {
                    window.setTimeout(() => {
                        this.showSuggestions = false;
                    }, 150);
                },
                validateEmployeeSelection(event) {
                    if (!this.selectedUserId) {
                        event.preventDefault();
                        this.employeeError = true;
                    }
                },
            }"
            @submit="validateEmployeeSelection">
            @csrf

            <div class="px-6 py-5">
                @if (session('error') && ($openManualAttendanceModal ?? false))
                    <div
                        class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any() && ($openManualAttendanceModal ?? false))
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
                    'mode' => 'create',
                    'employees' => $employees,
                    'types' => $types,
                    'statuses' => $statuses,
                    'defaultDate' => $date,
                ])
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                <x-ui.button type="submit" variant="primary">Simpan Absensi</x-ui.button>
                <x-ui.button type="button" variant="secondary" data-action="close-modal"
                    data-modal-id="manual-attendance-create-modal">Batal</x-ui.button>
            </div>
        </form>
    </div>
</div>
