{{--
    Clickable attendance summary cards + date modal.
    Expects: $payroll, $attendanceDates (from PayrollAttendanceDateBreakdown)
--}}
@php
    $attendanceDates = $attendanceDates ?? [
        'present' => [],
        'late' => [],
        'absent' => [],
        'sick' => [],
        'leave' => [],
    ];

    $cards = [
        [
            'key' => null,
            'count' => $payroll->formattedWorkDays(),
            'label' => 'Hari Kerja',
            'box' => 'rounded-xl bg-gray-50 p-3 text-center dark:bg-gray-700/50',
            'value' => 'text-2xl font-bold text-gray-900 dark:text-gray-100',
            'caption' => 'mt-1 text-xs text-gray-500 dark:text-gray-400',
        ],
        [
            'key' => 'present',
            'count' => (int) $payroll->present_days,
            'label' => 'Hadir',
            'box' => 'rounded-xl bg-green-50 p-3 text-center dark:bg-green-900/20',
            'value' => 'text-2xl font-bold text-green-700 dark:text-green-400',
            'caption' => 'mt-1 text-xs text-green-600 dark:text-green-400',
        ],
        [
            'key' => 'late',
            'count' => (int) ($payroll->late_days ?? 0),
            'label' => 'Telat',
            'box' => 'rounded-xl bg-orange-50 p-3 text-center dark:bg-orange-900/20',
            'value' => 'text-2xl font-bold text-orange-600 dark:text-orange-400',
            'caption' => 'mt-1 text-xs text-orange-500 dark:text-orange-400',
        ],
        [
            'key' => 'absent',
            'count' => (int) $payroll->absent_days,
            'label' => 'Alfa',
            'box' => 'rounded-xl bg-red-50 p-3 text-center dark:bg-red-900/20',
            'value' => 'text-2xl font-bold text-red-600 dark:text-red-400',
            'caption' => 'mt-1 text-xs text-red-500 dark:text-red-400',
        ],
        [
            'key' => 'sick',
            'count' => (int) ($payroll->sick_days ?? 0),
            'label' => 'Sakit',
            'box' => 'rounded-xl bg-blue-50 p-3 text-center dark:bg-blue-900/20',
            'value' => 'text-2xl font-bold text-blue-700 dark:text-blue-400',
            'caption' => 'mt-1 text-xs text-blue-600 dark:text-blue-400',
        ],
        [
            'key' => 'leave',
            'count' => (int) ($payroll->leave_days ?? 0),
            'label' => 'Izin',
            'box' => 'rounded-xl bg-purple-50 p-3 text-center dark:bg-purple-900/20',
            'value' => 'text-2xl font-bold text-purple-700 dark:text-purple-400',
            'caption' => 'mt-1 text-xs text-purple-600 dark:text-purple-400',
        ],
    ];
@endphp

<div class="grid grid-cols-3 gap-3 sm:grid-cols-6 lg:grid-cols-3">
    @foreach ($cards as $card)
        @php
            $dates = $card['key'] ? ($attendanceDates[$card['key']] ?? []) : [];
            $clickable = $card['key'] !== null && $card['count'] > 0;
        @endphp

        @if ($clickable)
            <button
                type="button"
                data-attendance-dates-trigger
                data-title="Tanggal {{ $card['label'] }}"
                data-dates='@json($dates)'
                class="{{ $card['box'] }} w-full cursor-pointer ring-offset-2 transition hover:ring-2 hover:ring-red-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 dark:ring-offset-gray-800 dark:hover:ring-red-700"
            >
                <p class="{{ $card['value'] }}">{{ $card['count'] }}</p>
                <p class="{{ $card['caption'] }}">{{ $card['label'] }}</p>
                <p class="mt-1 text-[10px] font-medium text-gray-400 dark:text-gray-500">Lihat tanggal</p>
            </button>
        @else
            <div class="{{ $card['box'] }}">
                <p class="{{ $card['value'] }}">{{ $card['count'] }}</p>
                <p class="{{ $card['caption'] }}">{{ $card['label'] }}</p>
            </div>
        @endif
    @endforeach
</div>

<div id="attendance-dates-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4" role="dialog"
    aria-modal="true" aria-labelledby="attendance-dates-modal-title">
    <div class="absolute inset-0 bg-black/50" data-attendance-dates-close></div>
    <div
        class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <h3 id="attendance-dates-modal-title" class="text-base font-semibold text-gray-900 dark:text-gray-100">
                Tanggal
            </h3>
            <button type="button" data-attendance-dates-close
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="max-h-80 overflow-y-auto px-5 py-4">
            <ul id="attendance-dates-list" class="space-y-2"></ul>
            <p id="attendance-dates-empty" class="hidden text-sm text-gray-500 dark:text-gray-400">Tidak ada tanggal.</p>
        </div>
        <div class="border-t border-gray-100 px-5 py-3 dark:border-gray-700">
            <button type="button" data-attendance-dates-close
                class="inline-flex w-full items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('attendance-dates-modal');
        const titleEl = document.getElementById('attendance-dates-modal-title');
        const listEl = document.getElementById('attendance-dates-list');
        const emptyEl = document.getElementById('attendance-dates-empty');

        if (!modal || !titleEl || !listEl || !emptyEl) {
            return;
        }

        function openModal(title, dates) {
            titleEl.textContent = title;
            listEl.innerHTML = '';

            if (!dates.length) {
                emptyEl.classList.remove('hidden');
            } else {
                emptyEl.classList.add('hidden');
                dates.forEach(function (item) {
                    const li = document.createElement('li');
                    li.className = 'flex items-center justify-between rounded-xl border border-gray-100 bg-gray-50 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900/40';
                    const label = document.createElement('span');
                    label.className = 'font-medium text-gray-800 dark:text-gray-100';
                    label.textContent = item.label || item.date;
                    li.appendChild(label);
                    if (item.note) {
                        const note = document.createElement('span');
                        note.className = 'text-xs text-gray-500 dark:text-gray-400';
                        note.textContent = item.note;
                        li.appendChild(note);
                    }
                    listEl.appendChild(li);
                });
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-attendance-dates-trigger]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                let dates = [];
                try {
                    dates = JSON.parse(btn.getAttribute('data-dates') || '[]');
                } catch (e) {
                    dates = [];
                }
                openModal(btn.getAttribute('data-title') || 'Tanggal', dates);
            });
        });

        modal.querySelectorAll('[data-attendance-dates-close]').forEach(function (el) {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    })();
</script>
