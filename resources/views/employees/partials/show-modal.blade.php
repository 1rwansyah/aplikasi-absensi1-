<div id="show-modal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/55 p-4 backdrop-blur-sm" style="display:none" data-modal>

    <div class="absolute inset-0 bg-gray-900/40 transition-opacity duration-300 opacity-0" data-backdrop data-action="close-modal" data-modal-id="show-modal"></div>

    <div class="relative mx-auto w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-[1.75rem] border border-gray-200/80 bg-white shadow-2xl shadow-gray-900/10 dark:border-gray-700 dark:bg-gray-800 transition-all duration-300 ease-out transform scale-95 opacity-0" data-modal-content>
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

        <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6 dark:border-gray-700">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Manajemen SDM</p>
                <h2 class="mt-1 text-lg font-bold tracking-tight text-gray-900 dark:text-white">Profil Karyawan</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Informasi lengkap data karyawan</p>
            </div>
            <button type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 text-gray-400 transition hover:border-gray-300 hover:bg-gray-50 hover:text-gray-600 dark:border-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                data-action="close-modal" data-modal-id="show-modal" aria-label="Tutup">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div id="show-loading" class="px-6 py-14 text-center">
            <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Memuat data...
            </div>
        </div>

        <div id="show-content" class="hidden">

            <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 via-white to-rose-50/40 px-5 py-6 sm:px-6 dark:border-gray-700 dark:from-gray-900/50 dark:via-gray-800 dark:to-gray-800">
                <div class="flex items-start gap-4">
                    <div class="relative flex-shrink-0">
                        <img id="show_photo" src="" class="hidden h-24 w-24 rounded-2xl border-2 border-white object-cover shadow-md dark:border-gray-700" alt="">
                        <div id="show_photo_placeholder" class="flex h-24 w-24 items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white dark:border-gray-600 dark:bg-gray-700">
                            <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 id="show_name" class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">-</h3>
                            <span id="show_employment_status_badge" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">-</span>
                            <span id="show_hr_badge" class="hidden inline-flex items-center gap-1 rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/50">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                Admin Panel
                            </span>
                        </div>
                        <p class="mt-1.5 flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3m3-3a3 3 0 013-3m-3 3h.01" />
                            </svg>
                            <span id="show_employee_code">-</span>
                        </p>
                        <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                            <span id="show_position" class="font-semibold text-gray-700 dark:text-gray-300">-</span>
                            <span class="text-gray-300 dark:text-gray-600">|</span>
                            <span id="show_staff" class="text-gray-500 dark:text-gray-400">-</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">

                <div class="space-y-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Informasi Pribadi</h4>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="space-y-3.5">
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Email</span>
                                <span id="show_email" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Pendidikan</span>
                                <span id="show_education" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">NIK</span>
                                <span id="show_nik" class="text-sm font-medium tabular-nums text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Tempat/Tgl Lahir</span>
                                <span id="show_birth_info" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Pengalaman Kerja</span>
                                <span id="show_work_experience" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Alamat</span>
                                <span id="show_address" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Informasi Kepegawaian</h4>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="space-y-3.5">
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Tanggal Masuk</span>
                                <span id="show_join_date" class="text-sm font-medium tabular-nums text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Status</span>
                                <span id="show_employment_status_text" class="inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-semibold">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Jabatan</span>
                                <span id="show_position_text" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Staff</span>
                                <span id="show_staff_text" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-3 md:col-span-2">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Informasi Penggajian</h4>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="grid gap-5 md:grid-cols-3">
                            <div class="rounded-2xl border border-white bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Gaji Pokok</p>
                                <p id="show_basic_salary" class="mt-1.5 text-lg font-bold tabular-nums text-gray-900 dark:text-white">-</p>
                            </div>

                            <div class="md:col-span-2">
                                <p class="mb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-400">Komponen Gaji</p>
                                <div id="show_salary_components" class="space-y-2">
                                    <p class="text-sm italic text-gray-400">Tidak ada komponen gaji</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 flex items-center justify-between rounded-2xl bg-gradient-to-r from-red-600 to-rose-600 px-4 py-4 text-white shadow-sm shadow-red-900/10">
                            <div>
                                <p class="text-sm font-semibold">Gaji Gross</p>
                                <p class="text-xs text-red-100/90">Gaji Pokok + Tunjangan</p>
                            </div>
                            <p id="show_gross_salary" class="text-xl font-bold tabular-nums">-</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Informasi Bank</h4>
                    <div class="rounded-2xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                        <div class="space-y-3.5">
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Nama Bank</span>
                                <span id="show_bank_name" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">No. Rekening</span>
                                <span id="show_bank_account_number" class="text-sm font-medium tabular-nums text-gray-900 dark:text-gray-100">-</span>
                            </div>
                            <div class="grid grid-cols-[110px_1fr] items-baseline gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Atas Nama</span>
                                <span id="show_bank_account_name" class="text-sm font-medium text-gray-900 dark:text-gray-100">-</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex justify-end border-t border-gray-100 px-5 py-4 sm:px-6 dark:border-gray-700">
                <button type="button" data-action="close-modal" data-modal-id="show-modal"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>
