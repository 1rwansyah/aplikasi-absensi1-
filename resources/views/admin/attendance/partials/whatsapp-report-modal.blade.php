{{-- WhatsApp Report Confirmation Modal --}}
<div id="whatsapp-report-modal"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4" style="display:none"
    role="dialog" aria-modal="true" aria-labelledby="whatsapp-report-modal-title" data-modal
    data-report-date="{{ $date->toDateString() }}"
    data-send-url="{{ route('attendances.send-whatsapp-report') }}">

    {{-- Backdrop --}}
    <div id="whatsapp-report-modal-backdrop"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity duration-300 opacity-0"
        data-backdrop onclick="closeWhatsappReportModal()"></div>

    {{-- Panel --}}
    <div class="relative mx-auto w-full max-w-md transform rounded-2xl bg-white shadow-xl transition-all duration-300 ease-out scale-95 opacity-0 dark:bg-gray-800"
        data-modal-content>

        <button type="button" onclick="closeWhatsappReportModal()"
            class="absolute right-4 top-4 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
            aria-label="Tutup">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="flex flex-col items-center px-6 pb-4 pt-8 text-center">
            <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-50 dark:bg-green-900/30">
                <svg class="h-7 w-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
            </div>

            <h3 id="whatsapp-report-modal-title" class="text-lg font-bold text-gray-900 dark:text-gray-100">Kirim Rekap
                Absensi?</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Pilih jenis rekap untuk tanggal
                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $date->translatedFormat('d F Y') }}</span>
                yang akan dikirim ke grup WhatsApp.
            </p>
        </div>

        <div class="space-y-4 px-6 pb-4">
            <fieldset>
                <legend class="mb-2 block text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Jenis Laporan
                </legend>
                <div class="grid grid-cols-2 gap-3">
                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-green-300 hover:bg-green-50/50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50 has-[:checked]:text-green-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-green-700 dark:hover:bg-green-900/20 dark:has-[:checked]:border-green-500 dark:has-[:checked]:bg-green-900/20 dark:has-[:checked]:text-green-300">
                        <input type="radio" name="whatsapp-report-type" value="masuk" class="text-green-600 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-500" checked>
                        Absensi Masuk
                    </label>
                    <label
                        class="flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-green-300 hover:bg-green-50/50 has-[:checked]:border-green-500 has-[:checked]:bg-green-50 has-[:checked]:text-green-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-green-700 dark:hover:bg-green-900/20 dark:has-[:checked]:border-green-500 dark:has-[:checked]:bg-green-900/20 dark:has-[:checked]:text-green-300">
                        <input type="radio" name="whatsapp-report-type" value="pulang" class="text-green-600 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-500">
                        Absensi Pulang
                    </label>
                </div>
            </fieldset>

            <div id="whatsapp-report-feedback" class="hidden rounded-xl px-4 py-3 text-sm" role="alert"></div>
        </div>

        <div class="border-t border-gray-100 dark:border-gray-700"></div>

        <div class="flex items-center justify-end gap-3 px-6 py-4">
            <x-ui.button type="button" id="whatsapp-report-submit-btn" variant="success" size="md"
                onclick="submitWhatsappReport()">Ya, Kirim
            </x-ui.button>
            <x-ui.button type="button" variant="secondary" size="md" onclick="closeWhatsappReportModal()">Batal
            </x-ui.button>
        </div>
    </div>
</div>
