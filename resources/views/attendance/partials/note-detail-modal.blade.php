{{--
    Attendance note detail modal — plain text, copy, close.
    Included once via x-attendance.note-snippet or history page.
--}}
<div id="attendance-note-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
    aria-hidden="true"
    role="dialog"
    aria-modal="true"
    aria-labelledby="attendance-note-modal-title">
    <div
        class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-100 px-4 py-4 sm:px-6 dark:border-gray-700">
            <div class="min-w-0 pr-3">
                <h3 id="attendance-note-modal-title" class="text-lg font-bold text-gray-900 dark:text-gray-100">Keterangan</h3>
                <p id="attendance-note-modal-meta" class="mt-1 hidden text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
            <button type="button" id="attendance-note-modal-close-x"
                class="flex-shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                <span class="sr-only">Tutup</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-4 py-5 sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Isi keterangan</p>
            <p id="attendance-note-modal-body"
                class="mt-2 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-800 dark:text-gray-200"></p>
        </div>

        <div
            class="flex flex-shrink-0 flex-col-reverse gap-2 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6 dark:border-gray-700">
            <button type="button" id="attendance-note-modal-close"
                class="min-h-11 w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                Tutup
            </button>
            <button type="button" id="attendance-note-modal-copy"
                class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto dark:focus:ring-offset-gray-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <span id="attendance-note-modal-copy-label">Salin</span>
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function () {
                const modal = document.getElementById('attendance-note-modal');
                if (!modal || modal.dataset.bound === '1') {
                    return;
                }
                modal.dataset.bound = '1';

                const titleEl = document.getElementById('attendance-note-modal-title');
                const metaEl = document.getElementById('attendance-note-modal-meta');
                const bodyEl = document.getElementById('attendance-note-modal-body');
                const copyBtn = document.getElementById('attendance-note-modal-copy');
                const copyLabel = document.getElementById('attendance-note-modal-copy-label');
                const closeBtn = document.getElementById('attendance-note-modal-close');
                const closeX = document.getElementById('attendance-note-modal-close-x');

                let copyResetTimer = null;

                function openNoteModal({ title, meta, text }) {
                    titleEl.textContent = title || 'Keterangan';
                    bodyEl.textContent = text || '';

                    if (meta) {
                        metaEl.textContent = meta;
                        metaEl.classList.remove('hidden');
                    } else {
                        metaEl.textContent = '';
                        metaEl.classList.add('hidden');
                    }

                    copyLabel.textContent = 'Salin';
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('overflow-hidden');
                }

                function closeNoteModal() {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                    modal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('overflow-hidden');
                }

                async function copyNoteText() {
                    const text = bodyEl.textContent || '';
                    if (!text) {
                        return;
                    }

                    try {
                        if (navigator.clipboard?.writeText) {
                            await navigator.clipboard.writeText(text);
                        } else {
                            const area = document.createElement('textarea');
                            area.value = text;
                            area.setAttribute('readonly', '');
                            area.style.position = 'absolute';
                            area.style.left = '-9999px';
                            document.body.appendChild(area);
                            area.select();
                            document.execCommand('copy');
                            document.body.removeChild(area);
                        }
                        copyLabel.textContent = 'Tersalin';
                        clearTimeout(copyResetTimer);
                        copyResetTimer = setTimeout(() => {
                            copyLabel.textContent = 'Salin';
                        }, 1600);
                    } catch (error) {
                        copyLabel.textContent = 'Gagal salin';
                        clearTimeout(copyResetTimer);
                        copyResetTimer = setTimeout(() => {
                            copyLabel.textContent = 'Salin';
                        }, 1600);
                    }
                }

                document.addEventListener('click', (event) => {
                    const trigger = event.target.closest('[data-attendance-note-open]');
                    if (!trigger) {
                        return;
                    }

                    event.preventDefault();
                    openNoteModal({
                        title: trigger.dataset.noteTitle || 'Keterangan',
                        meta: trigger.dataset.noteMeta || '',
                        text: trigger.dataset.noteText || '',
                    });
                });

                closeBtn?.addEventListener('click', closeNoteModal);
                closeX?.addEventListener('click', closeNoteModal);
                copyBtn?.addEventListener('click', copyNoteText);

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeNoteModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeNoteModal();
                    }
                });

                window.openAttendanceNoteModal = openNoteModal;
                window.closeAttendanceNoteModal = closeNoteModal;
            })();
        </script>
    @endpush
@endonce
