<x-app-layout>
    <div class="py-6">
        <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Verifikasi Izin & Sakit</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kelola dan verifikasi pengajuan izin/sakit karyawan</p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 px-4 py-4 sm:px-6 dark:border-gray-700">
                    <form method="GET" action="{{ route('leave-verification.index') }}"
                        class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center" id="filter-form">

                        <x-ui.search-input name="search" value="{{ $search ?? '' }}" placeholder="Cari karyawan..." />

                        <input type="date" name="date" value="{{ $filters['date'] }}"
                            class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 sm:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">

                        <x-ui.select name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" @selected($filters['status'] === 'pending')>Menunggu</option>
                            <option value="done" @selected($filters['status'] === 'done')>Disetujui</option>
                            <option value="no_done" @selected($filters['status'] === 'no_done')>Ditolak</option>
                        </x-ui.select>

                        <x-ui.button type="submit" variant="primary" size="md" class="w-full min-h-11 sm:w-auto">
                            Filter
                        </x-ui.button>

                        @if ($search || $filters['status'] || ($filters['date'] && ! $filters['is_today']))
                            <a href="{{ $filters['date'] ? route('leave-verification.index', ['date' => $filters['date']]) : route('leave-verification.index') }}"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-500 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </a>
                        @endif
                    </form>
                </div>

                <div class="space-y-3 p-4 md:hidden">
                    @forelse($leaves as $leave)
                        <article class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $leave->user->name }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $leave->date->format('d M Y') }} · {{ $leave->user->employee?->employee_code ?? '-' }}
                                    </p>
                                </div>
                                <span class="flex-shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $leave->verification_status->badgeClasses() }}">
                                    {{ $leave->verification_status->label() }}
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $leave->status->badgeClasses() }}">
                                    {{ $leave->type->label() }}
                                </span>
                                @if ($leave->leaveNoteText())
                                    <p class="min-w-0 break-words text-sm text-gray-600 dark:text-gray-300">{{ Str::limit($leave->leaveNoteText(), 80) }}</p>
                                @endif
                            </div>
                            @if ($leave->verifiedBy)
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Diverifikasi {{ $leave->verifiedBy->name }}
                                    @if ($leave->verified_at)
                                        · {{ $leave->verified_at->format('d M Y H:i') }}
                                    @endif
                                </p>
                            @endif
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <x-attendance.doctor-note-preview :attendance="$leave" />
                                </div>
                                @include('leave-verification.partials.row-actions', ['leave' => $leave, 'compact' => false])
                            </div>
                        </article>
                    @empty
                        <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada pengajuan izin/sakit</p>
                    @endforelse
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full border-collapse">
                        <thead class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Tanggal
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Karyawan
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Jenis
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Keterangan
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Bukti
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Status
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Diverifikasi Oleh
                                </th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse($leaves as $leave)
                                <tr class="bg-white transition hover:bg-gray-50/80 dark:bg-gray-800 dark:hover:bg-gray-700/50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                        {{ $leave->date->format('d M Y') }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $leave->user->name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $leave->user->employee?->employee_code ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $leave->status->badgeClasses() }}">
                                            {{ $leave->type->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        <div class="max-w-xs" title="{{ $leave->leaveNoteText() }}">
                                            {{ Str::limit($leave->leaveNoteText(), 80) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <x-attendance.doctor-note-preview :attendance="$leave" />
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $leave->verification_status->badgeClasses() }}">
                                            {{ $leave->verification_status->label() }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        @if($leave->verifiedBy)
                                            <div>{{ $leave->verifiedBy->name }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $leave->verified_at?->format('d M Y H:i') }}
                                            </div>
                                        @else
                                            <span class="text-gray-400 dark:text-gray-500">-</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-center">
                                        @include('leave-verification.partials.row-actions', ['leave' => $leave, 'compact' => true])
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tidak ada pengajuan izin/sakit</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($leaves->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        {{ $leaves->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('modals')
        @include('leave-verification.partials.preview-modal')
        @include('leave-verification.partials.verify-confirm-modal')
    @endpush
</x-app-layout>

<script>
    function openLeavePreview(button) {
        const modal = document.getElementById('leave-preview-modal');
        const nameEl = document.getElementById('leave-preview-name');
        const metaEl = document.getElementById('leave-preview-meta');
        const noteEl = document.getElementById('leave-preview-note');
        const proofSection = document.getElementById('leave-preview-proof-section');
        const proofEl = document.getElementById('leave-preview-proof');
        const approveBtn = document.getElementById('leave-preview-approve-btn');
        const rejectBtn = document.getElementById('leave-preview-reject-btn');

        const leaveId = button.dataset.leaveId || '';
        const isPending = button.dataset.pending === '1';
        const employeeName = button.dataset.name || '—';
        const leaveDate = button.dataset.date || '—';
        const leaveType = button.dataset.typeValue || '';

        if (isPending && leaveId) {
            approveBtn.classList.remove('hidden');
            rejectBtn.classList.remove('hidden');
            approveBtn.onclick = () => {
                closeLeavePreview();
                window.LeaveVerificationModule?.openApprove(leaveId, employeeName, leaveDate);
            };
            rejectBtn.onclick = () => {
                closeLeavePreview();
                window.LeaveVerificationModule?.openReject(leaveId, employeeName, leaveDate, leaveType);
            };
        } else {
            approveBtn.classList.add('hidden');
            rejectBtn.classList.add('hidden');
            approveBtn.onclick = null;
            rejectBtn.onclick = null;
        }

        nameEl.textContent = button.dataset.name || '—';
        metaEl.textContent = [
            button.dataset.date,
            button.dataset.type,
            button.dataset.status,
        ].filter(Boolean).join(' · ');

        noteEl.textContent = button.dataset.note || '—';

        const proofUrl = button.dataset.proofUrl || '';
        const isImage = button.dataset.proofImage === '1';

        if (proofUrl) {
            proofSection.classList.remove('hidden');
            if (isImage) {
                proofEl.innerHTML = `
                    <a href="${proofUrl}" target="_blank" rel="noopener noreferrer" class="inline-block">
                        <img src="${proofUrl}" alt="Bukti pengajuan"
                            class="max-h-[28rem] w-full rounded-xl border border-gray-200 object-contain bg-gray-50 dark:border-gray-600 dark:bg-gray-900">
                    </a>`;
            } else {
                proofEl.innerHTML = `
                    <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-600">
                        <iframe src="${proofUrl}#toolbar=0&navpanes=0"
                            class="h-[28rem] w-full bg-white"
                            title="Pratinjau PDF"></iframe>
                    </div>
                    <a href="${proofUrl}" target="_blank" rel="noopener noreferrer"
                        class="mt-2 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Buka PDF di tab baru
                    </a>`;
            }
        } else {
            proofSection.classList.add('hidden');
            proofEl.innerHTML = '';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeLeavePreview() {
        const modal = document.getElementById('leave-preview-modal');
        const proofEl = document.getElementById('leave-preview-proof');

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
        proofEl.innerHTML = '';
    }

    document.getElementById('leave-preview-modal')?.addEventListener('click', (event) => {
        if (event.target.id === 'leave-preview-modal') {
            closeLeavePreview();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeLeavePreview();
        }
    });
</script>
