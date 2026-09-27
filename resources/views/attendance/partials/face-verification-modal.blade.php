{{--
    Face Verification Modal
    Shared by clock-in and clock-out forms.
    Opened via JS: FaceVerificationModal.open(formEl)
--}}
<div id="face-verification-modal"
    class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/60 p-3 backdrop-blur-sm sm:p-4">

    <div
        class="flex max-h-[min(92vh,56rem)] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-xl sm:max-w-xl md:max-w-2xl dark:bg-gray-800">

        {{-- Header --}}
        <div class="flex flex-shrink-0 items-center justify-between border-b border-gray-100 px-4 py-3 sm:px-6 sm:py-4 dark:border-gray-700">
            <div class="min-w-0 pr-3">
                <h3 id="face-modal-title" class="text-base font-bold text-gray-900 dark:text-white">Verifikasi Wajah</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Izin lokasi dan kamera sudah diminta saat halaman dibuka. Hadapkan wajah, lalu ambil foto.</p>
            </div>
            <button id="face-cancel-button" type="button"
                class="flex-shrink-0 rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 disabled:cursor-not-allowed disabled:opacity-40 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                aria-label="Tutup verifikasi">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4 sm:p-6">

            {{-- Reference photo + status badge --}}
            <div class="flex items-center gap-3">
                <img id="face-modal-profile-photo"
                    class="h-12 w-12 rounded-xl border border-gray-200 object-cover dark:border-gray-600"
                    src="" alt="Foto Profil">
                <div class="min-w-0 flex-1">
                    <p class="text-xs text-gray-500 dark:text-gray-400">Referensi verifikasi</p>
                    <span id="face-status-badge"
                        class="mt-1 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        Memuat...
                    </span>
                </div>
            </div>

            {{-- Video preview: square on phone, capped height on short tablets (Nest Hub 600) --}}
            <div id="face-camera-panel" tabindex="-1"
                class="relative mx-auto w-full max-w-xl scroll-m-4 overflow-hidden rounded-2xl border border-gray-200 bg-gray-900 outline-none dark:border-gray-600">
                <video id="face-video" autoplay muted playsinline
                    class="aspect-square max-h-[min(42vh,24rem)] w-full scale-x-[-1] object-cover sm:max-h-[min(52vh,28rem)] md:max-h-[min(56vh,32rem)]"></video>
                <img id="face-preview-image" class="hidden aspect-square max-h-[min(42vh,24rem)] w-full object-cover sm:max-h-[min(52vh,28rem)] md:max-h-[min(56vh,32rem)]">
                <canvas id="face-canvas" class="hidden"></canvas>
                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent px-4 py-3">
                    <p id="face-status" class="text-sm font-medium text-white">Menyiapkan kamera...</p>
                </div>
                <div class="absolute inset-x-0 top-0 flex items-center justify-between px-3 py-2">
                    <span id="face-preview-label"
                        class="inline-flex rounded-full bg-black/55 px-2.5 py-1 text-[11px] font-semibold text-white">
                        Menyiapkan preview...
                    </span>
                    <span id="face-live-indicator"
                        class="hidden animate-pulse rounded-full bg-emerald-500/90 px-2.5 py-1 text-[11px] font-semibold text-white">
                        Realtime
                    </span>
                </div>
            </div>

            <div id="face-ready-panel"
                tabindex="-1"
                class="scroll-m-4 rounded-xl border border-blue-100 bg-blue-50/90 px-4 py-3 outline-none dark:border-blue-900/50 dark:bg-blue-950/30">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-700 dark:text-blue-300">Status verifikasi</p>
                    <span id="face-panel-live-badge"
                        class="hidden rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                        Aktif
                    </span>
                </div>
                <p id="face-ready-title" class="mt-1 text-base font-bold text-blue-800 dark:text-blue-200 sm:text-lg">
                    Menyiapkan verifikasi...
                </p>
                <p id="face-ready-hint" class="mt-1 text-xs text-blue-700/90 dark:text-blue-300/90">
                    Izin lokasi dan kamera seharusnya sudah diizinkan saat halaman absensi dibuka. Di sini tinggal menghadapkan wajah dan mengambil foto.
                </p>
                <div id="face-loading-guidance"
                    class="mt-3 border-t border-blue-200/70 pt-3 dark:border-blue-800/60">
                    <p class="text-xs font-semibold text-blue-800 dark:text-blue-200">Yang perlu Anda lakukan:</p>
                    <ol class="mt-2 space-y-2 text-xs leading-relaxed text-blue-800 dark:text-blue-200">
                        <li class="flex gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">1</span>
                            <span>Pastikan Anda sudah menekan <strong>Izinkan</strong> untuk lokasi dan kamera saat halaman absensi terbuka. Jika belum, tutup modal, muat ulang halaman, lalu tekan Izinkan.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">2</span>
                            <span>Hadapkan satu wajah ke kamera dengan pencahayaan cukup. Izin tidak diminta ulang di langkah ini.</span>
                        </li>
                        <li class="flex gap-2">
                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600 text-[10px] font-bold text-white">3</span>
                            <span>Tunggu sampai status berubah menjadi <strong>Siap</strong>, lalu tekan <strong>Ambil Foto</strong>.</span>
                        </li>
                    </ol>
                    <p class="mt-3 rounded-lg bg-amber-50 px-3 py-2 text-xs font-medium leading-relaxed text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                        Jika izin ditolak atau diblokir, muat ulang halaman atau ubah izin di pengaturan situs browser, lalu tekan Coba Lagi. Absensi belum tercatat sebelum muncul pemberitahuan berhasil.
                    </p>
                </div>
                <p id="face-ready-steps-heading" class="hidden">
                    Langkah perbaikan yang perlu dilakukan:
                </p>
                <ol id="face-ready-steps"
                    class="hidden">
                </ol>
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex flex-shrink-0 flex-wrap items-center justify-end gap-2 border-t border-gray-100 px-4 py-3 sm:gap-3 sm:px-6 sm:py-4 dark:border-gray-700">
            <button id="face-retry-button" type="button"
                class="hidden rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                <svg class="mr-1 inline-block h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span data-button-label>Coba Lagi</span>
            </button>
            <button id="face-capture-button" type="button" disabled
                class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:opacity-50">
                Ambil Foto
            </button>
            <button id="face-retake-button" type="button"
                class="hidden rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                Ambil Foto Ulang
            </button>
            <button id="face-use-photo-button" type="button"
                class="hidden rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:cursor-not-allowed disabled:opacity-50">
                Gunakan Foto
            </button>
        </div>

    </div>
</div>
