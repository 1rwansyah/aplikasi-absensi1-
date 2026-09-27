{{-- Work Calendar Edit Modal --}}
<div id="calendar-edit-modal"
    class="fixed inset-0 z-[100] items-center justify-center bg-black/55 p-4 backdrop-blur-sm"
    style="display:none">

    <div class="relative mx-auto w-full max-w-md overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-2xl shadow-gray-900/10 dark:border-gray-700 dark:bg-gray-800">
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

        <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:px-6">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Edit hari kerja</p>
                <h3 class="mt-1 text-base font-bold tracking-tight text-gray-900 dark:text-white">Edit Hari Kerja</h3>
                <p id="calendar-modal-date" class="mt-0.5 text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
            <button type="button" data-calendar-close
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-600 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                aria-label="Tutup">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="calendar-edit-form" method="POST" class="space-y-4 p-5 sm:p-6">
            @csrf
            @method('PATCH')

            <div>
                <label for="calendar-type" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tipe Hari</label>
                <select id="calendar-type" name="type"
                    class="block min-h-11 w-full rounded-2xl border-gray-200 bg-gray-50 text-sm font-semibold text-gray-800 shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                    <option value="full_day">Full Day</option>
                    <option value="half_day">Half Day</option>
                    <option value="holiday">Libur</option>
                </select>
            </div>

            <div>
                <label for="calendar-name" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Nama / Keterangan Singkat</label>
                <input id="calendar-name" type="text" name="name" placeholder="Mis. Hari Raya Idul Fitri"
                    class="block min-h-11 w-full rounded-2xl border-gray-200 bg-gray-50 text-sm shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label for="calendar-note" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Catatan</label>
                <textarea id="calendar-note" name="note" rows="3" placeholder="Opsional..."
                    class="block w-full rounded-2xl border-gray-200 bg-gray-50 text-sm shadow-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"></textarea>
            </div>

            <div class="flex flex-col-reverse gap-2 pt-1 sm:flex-row sm:justify-end">
                <button type="button" data-calendar-close
                    class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700">
                    Batal
                </button>
                <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm shadow-red-900/10 transition hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>
