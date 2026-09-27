<div id="leave-verify-confirm-modal"
    class="fixed inset-0 z-[110] flex items-center justify-center bg-black/50 p-4"
    style="display:none" role="dialog" aria-modal="true" aria-labelledby="leave-verify-modal-title" data-modal>

    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 opacity-0"
        data-backdrop data-action="close-modal" data-modal-id="leave-verify-confirm-modal"></div>

    <div class="relative mx-auto w-full max-w-md transform rounded-2xl bg-white shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 dark:bg-gray-800"
        data-modal-content>

        <div class="flex flex-col items-center px-6 pb-4 pt-8 text-center">
            <div id="leave-verify-modal-icon-wrap"
                class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/30">
                <svg id="leave-verify-modal-icon" class="h-7 w-7 text-green-600 dark:text-green-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <h3 id="leave-verify-modal-title" class="text-lg font-bold text-gray-900 dark:text-gray-100">
                Konfirmasi
            </h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                <span id="leave-verify-modal-message"></span>
            </p>
            <p class="mt-3 text-sm text-gray-700 dark:text-gray-300">
                <span id="leave-verify-modal-employee" class="font-semibold text-gray-900 dark:text-gray-100"></span>
                <span class="text-gray-400 dark:text-gray-500"> · </span>
                <span id="leave-verify-modal-date"></span>
            </p>
        </div>

        <div id="leave-verify-reject-fields" class="hidden border-t border-gray-100 px-6 py-4 text-left dark:border-gray-700">
            <label for="leave-verify-rejection-reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Alasan penolakan <span class="text-red-500">*</span>
            </label>
            <textarea id="leave-verify-rejection-reason" rows="4" maxlength="2000"
                placeholder="Tuliskan alasan penolakan (minimal 10 karakter)..."
                class="mt-2 w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:placeholder-gray-500"></textarea>
            <p id="leave-verify-rejection-error" class="mt-1.5 hidden text-xs font-medium text-red-600 dark:text-red-400"></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Alasan ini akan dikirim ke email karyawan.</p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700"></div>

        <div class="flex flex-col-reverse gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
            <button type="button" id="leave-verify-confirm-btn"
                onclick="window.LeaveVerificationModule?.submit()"
                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-green-700 sm:w-auto">
                Ya
            </button>
            <x-ui.button type="button" variant="secondary" size="md" class="w-full min-h-11 sm:w-auto" data-action="close-modal"
                data-modal-id="leave-verify-confirm-modal">
                Batal
            </x-ui.button>
        </div>
    </div>
</div>
