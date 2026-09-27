<div id="leave-preview-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
    <div
        class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-4 sm:px-6 dark:border-gray-700">
            <div>
                <h3 id="leave-preview-name" class="text-lg font-bold text-gray-900 dark:text-gray-100"></h3>
                <p id="leave-preview-meta" class="mt-1 text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
            <button type="button" onclick="closeLeavePreview()"
                class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                <span class="sr-only">Tutup</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div class="space-y-4 px-4 py-5 text-sm text-gray-700 sm:p-6 dark:text-gray-300">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Keterangan</p>
                <p id="leave-preview-note" class="mt-2 whitespace-pre-wrap leading-relaxed"></p>
            </div>
            <div id="leave-preview-proof-section" class="hidden">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Bukti</p>
                <div id="leave-preview-proof" class="mt-2"></div>
            </div>
        </div>
        <div id="leave-preview-actions"
            class="flex flex-col-reverse gap-2 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6 dark:border-gray-700">
            <button type="button" id="leave-preview-approve-btn"
                class="hidden inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:w-auto dark:focus:ring-offset-gray-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Setujui
            </button>
            <button type="button" id="leave-preview-reject-btn"
                class="hidden inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-xl bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 sm:w-auto dark:focus:ring-offset-gray-800">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Tolak
            </button>
            <button type="button" onclick="closeLeavePreview()"
                class="min-h-11 w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                Tutup
            </button>
        </div>
    </div>
</div>
