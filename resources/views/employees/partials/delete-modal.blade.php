<div id="delete-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none" role="dialog" aria-modal="true" aria-labelledby="modal-title" data-modal>

    <div id="delete-modal-backdrop" class="absolute inset-0 bg-gray-900/40 transition-opacity duration-300 opacity-0" data-backdrop data-action="close-modal" data-modal-id="delete-modal"></div>

    <div class="relative mx-auto w-full max-w-md overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-2xl shadow-gray-900/10 transition-all duration-300 ease-out scale-95 opacity-0 dark:border-gray-700 dark:bg-gray-800" data-modal-content>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

        <div class="flex flex-col items-center px-6 pb-6 pt-8 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-50 ring-1 ring-inset ring-rose-100 dark:bg-rose-950/40 dark:ring-rose-900/50">
                <svg class="h-7 w-7 text-rose-600 dark:text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>

            <h3 id="modal-title" class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">Hapus Karyawan?</h3>
            <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                Anda akan menghapus karyawan <span id="modal-employee-name" class="font-semibold text-gray-800 dark:text-gray-200"></span>.
                Tindakan ini tidak dapat dibatalkan.
            </p>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700"></div>

        <div class="flex flex-col-reverse gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
            <button type="button" data-action="close-modal" data-modal-id="delete-modal"
                class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700">
                Batal
            </button>
            <button type="button" id="modal-confirm-btn" data-action="submit-delete"
                class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 sm:w-auto">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>
