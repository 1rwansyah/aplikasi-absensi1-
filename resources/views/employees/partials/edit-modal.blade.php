<div id="edit-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none" data-modal>

    <div class="absolute inset-0 bg-gray-900/40 transition-opacity duration-300 opacity-0" data-backdrop data-action="close-modal" data-modal-id="edit-modal"></div>

    <div class="relative mx-auto w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-[1.75rem] border border-gray-200/80 bg-white shadow-2xl shadow-gray-900/10 dark:border-gray-700 dark:bg-gray-800 transition-all duration-300 ease-out transform scale-95 opacity-0" data-modal-content>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

        <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6 dark:border-gray-700">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Manajemen SDM</p>
                <h3 class="mt-1 text-lg font-bold tracking-tight text-gray-900 dark:text-white">Edit Karyawan</h3>
                <p id="edit-modal-subtitle" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
            <button type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-600 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                data-action="close-modal" data-modal-id="edit-modal" aria-label="Tutup">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="edit-loading" class="px-6 py-14 text-center">
            <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Memuat data...
            </div>
        </div>

        <form id="edit-form" method="POST" class="hidden" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            <div class="px-5 py-5 sm:px-6">
                <div id="edit-errors" class="mb-4 hidden rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-300">
                    <ul id="edit-error-list" class="list-disc pl-4"></ul>
                </div>

                @include('employees.partials.employee-form', ['mode' => 'edit'])
            </div>

            <div class="sticky bottom-0 flex flex-col-reverse gap-2 border-t border-gray-100 bg-white/95 px-5 py-4 backdrop-blur sm:flex-row sm:items-center sm:justify-end sm:px-6 dark:border-gray-700 dark:bg-gray-800/95">
                <button type="button" data-action="close-modal" data-modal-id="edit-modal"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700">
                    Batal
                </button>
                <button type="submit" id="edit-submit-btn"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-red-900/10 transition hover:bg-red-700 sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
