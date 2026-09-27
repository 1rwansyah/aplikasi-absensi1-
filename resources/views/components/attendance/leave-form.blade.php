<div x-data="leaveForm({{ $errors->has('type') || $errors->has('leave_note') || $errors->has('doctor_note') || old('leave_note') ? 'true' : 'false' }}, '{{ old('type', 'permission') }}')"
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>

    {{-- Accordion header --}}
    <button type="button" @click="open = !open"
        class="group relative flex w-full items-center justify-between overflow-hidden px-5 py-5 text-left transition sm:px-7 sm:py-6"
        :class="open ? 'bg-gradient-to-r from-red-50 via-white to-white dark:from-red-950/20 dark:via-gray-800 dark:to-gray-800' : 'bg-white hover:bg-gray-50/80 dark:bg-gray-800 dark:hover:bg-gray-700/50'">
        <div class="pointer-events-none absolute -right-8 -top-12 h-28 w-28 rounded-full border-[18px] border-red-100/40 dark:border-red-900/10"></div>
        <div class="relative flex min-w-0 items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-red-700 text-white shadow-lg shadow-red-600/20">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-base font-bold tracking-tight text-gray-900 sm:text-lg dark:text-gray-100">Pengajuan Izin / Sakit</span>
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-700 ring-1 ring-inset ring-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-800">
                        Jika berhalangan hadir
                    </span>
                </div>
                <p class="mt-1 text-xs leading-relaxed text-gray-500 sm:text-sm dark:text-gray-400">
                    Ajukan izin atau sakit dengan bukti pendukung yang sesuai.
                </p>
            </div>
        </div>
        <span class="relative ml-3 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 shadow-sm transition group-hover:text-red-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">
            <svg class="h-4 w-4 transition-transform duration-200" :class="open && 'rotate-180'"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </span>
    </button>

    {{-- Accordion body --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1">
        <div class="border-t border-gray-100 bg-gray-50/60 px-5 py-6 sm:px-7 sm:py-7 dark:border-gray-700 dark:bg-gray-900/20">
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-100 dark:bg-gray-800 dark:text-blue-400 dark:ring-blue-900">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9h.01M11 12h1v4h1m8-4a9 9 0 11-18 0 9 9 0 0118 0Z" />
                    </svg>
                </span>
                <div>
                    <p class="text-sm font-semibold text-blue-900 dark:text-blue-200">Siapkan bukti pendukung</p>
                    <p class="mt-1 text-xs leading-relaxed text-blue-700/80 dark:text-blue-300/80"
                        x-text="type === 'sick' ? 'Pengajuan sakit wajib dilengkapi surat dokter dalam format PDF, JPG, atau PNG.' : 'Pengajuan izin wajib dilengkapi bukti izin dalam format PDF, JPG, atau PNG.'">
                    </p>
                </div>
            </div>

            <form method="POST" action="{{ route('attendance.leave') }}" enctype="multipart/form-data" class="space-y-5"
                @submit="window.syncAttendanceEditors?.($el)">
                @csrf
                <input type="hidden" name="latitude" :value="location ? location.latitude : ''">
                <input type="hidden" name="longitude" :value="location ? location.longitude : ''">
                <input type="hidden" name="accuracy" :value="location ? location.accuracy : ''">
                <div>
                    <div class="mb-2.5 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-red-100 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-300">1</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Pilih jenis pengajuan</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border p-3.5 transition"
                            :class="type === 'sick' ? 'border-red-300 bg-red-50 ring-1 ring-red-200 dark:border-red-800 dark:bg-red-950/20 dark:ring-red-900' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-600 dark:bg-gray-800'">
                            <input type="radio" name="type" value="sick" x-model="type" class="text-red-600 focus:ring-red-500">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m7-3a7 7 0 11-14 0 7 7 0 0114 0Z" />
                                </svg>
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200">Sakit</span>
                                <span class="hidden text-[11px] text-gray-500 sm:block dark:text-gray-400">Dengan surat dokter</span>
                            </span>
                        </label>
                        <label class="flex cursor-pointer items-center gap-3 rounded-2xl border p-3.5 transition"
                            :class="type === 'permission' ? 'border-blue-300 bg-blue-50 ring-1 ring-blue-200 dark:border-blue-800 dark:bg-blue-950/20 dark:ring-blue-900' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-600 dark:bg-gray-800'">
                            <input type="radio" name="type" value="permission" x-model="type" class="text-red-600 focus:ring-red-500">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3h7l3 3v15H7V3Zm3 7h4m-4 4h4" />
                                </svg>
                            </span>
                            <span>
                                <span class="block text-sm font-semibold text-gray-800 dark:text-gray-200">Izin</span>
                                <span class="hidden text-[11px] text-gray-500 sm:block dark:text-gray-400">Dengan bukti izin</span>
                            </span>
                        </label>
                    </div>
                    @error('type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="leave_note" class="mb-2.5 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-red-100 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-300">2</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Jelaskan keterangan</span>
                    </label>
                    <x-attendance.report-editor
                        name="leave_note"
                        id="leave_note"
                        :required="true"
                        rows="3"
                        placeholder="Jelaskan alasan izin atau sakit..."
                        :value="old('leave_note')"
                        textareaClass="mt-1.5 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white"
                        class="mt-0"
                    />
                    @error('leave_note')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- File upload + preview --}}
                <div>
                    <label for="doctor_note" class="mb-2.5 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-lg bg-red-100 text-xs font-bold text-red-700 dark:bg-red-900/30 dark:text-red-300">3</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200"
                            x-text="type === 'sick' ? 'Unggah surat dokter' : 'Unggah bukti izin'">Unggah bukti pendukung</span>
                        <span class="text-red-500">*</span>
                    </label>

                    {{-- File input (hidden when file already selected) --}}
                    <div x-show="!filePreview.url && !filePreview.name"
                        class="rounded-2xl border border-dashed border-gray-300 bg-white p-4 transition hover:border-red-300 hover:bg-red-50/30 dark:border-gray-600 dark:bg-gray-800 dark:hover:border-red-800 dark:hover:bg-red-950/10">
                        <div class="mb-3 flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 16V4m0 0L8 8m4-4 4 4M5 20h14" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-gray-700 dark:text-gray-200">Pilih dokumen pendukung</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">PDF, JPG, atau PNG · maksimal 10 MB</p>
                            </div>
                        </div>
                        <input id="doctor_note" type="file" name="doctor_note" accept=".pdf,.jpg,.jpeg,.png" required
                            class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-xl file:border-0 file:bg-red-600 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-red-700 dark:text-gray-400 dark:file:bg-red-700 dark:hover:file:bg-red-600"
                            @change="handleFileChange($event)">
                    </div>

                    {{-- Preview: image thumbnail --}}
                    <div x-show="filePreview.url && filePreview.isImage" class="mt-2 flex items-center gap-3">
                        <button type="button"
                            class="group relative h-16 w-16 shrink-0 overflow-hidden rounded-xl border border-gray-200 shadow-sm transition hover:shadow-md dark:border-gray-600"
                            @click="showImageModal = true"
                            title="Klik untuk lihat gambar penuh">
                            <img :src="filePreview.url" :alt="filePreview.name"
                                class="h-full w-full object-cover">
                            <div class="absolute inset-0 flex items-center justify-center bg-black/0 transition group-hover:bg-black/25">
                                <svg class="h-5 w-5 text-white opacity-0 drop-shadow transition group-hover:opacity-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                </svg>
                            </div>
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium text-gray-700 dark:text-gray-200" x-text="filePreview.name"></p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500" x-text="filePreview.sizeLabel"></p>
                            <button type="button"
                                class="mt-1 text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                @click="clearFile()">
                                Ganti file
                            </button>
                        </div>
                    </div>

                    {{-- Preview: PDF chip --}}
                    <div x-show="filePreview.name && !filePreview.isImage" class="mt-2 flex items-center gap-3">
                        <button type="button"
                            class="flex h-16 w-16 shrink-0 flex-col items-center justify-center gap-1 rounded-xl border border-red-200 bg-red-50 transition hover:bg-red-100 dark:border-red-900/50 dark:bg-red-950/40 dark:hover:bg-red-950/60"
                            @click="openPdf()"
                            title="Buka PDF di tab baru">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-red-600 dark:text-red-400">PDF</span>
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-medium text-gray-700 dark:text-gray-200" x-text="filePreview.name"></p>
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500" x-text="filePreview.sizeLabel"></p>
                            <button type="button"
                                class="mt-1 text-xs font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                @click="clearFile()">
                                Ganti file
                            </button>
                        </div>
                    </div>

                    {{-- Size error --}}
                    <p x-show="filePreview.sizeError" x-text="filePreview.sizeError"
                        class="mt-1 text-xs text-red-600 dark:text-red-400"></p>

                    @error('doctor_note')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit"
                    class="group inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-red-600/20 transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:scale-[0.99] dark:bg-red-700 dark:hover:bg-red-600 dark:focus:ring-offset-gray-800 sm:w-auto">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12 20 4l-5 16-3-7-8-1Zm8 1 3-3" />
                    </svg>
                    Kirim Pengajuan
                </button>

                <div x-show="gettingLocation" class="flex items-center gap-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2.5 text-xs font-medium text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300">
                    <svg class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Mengambil lokasi...
                </div>

                <div x-show="location && !gettingLocation" class="flex items-center gap-2 rounded-xl border border-emerald-100 bg-emerald-50 px-3 py-2.5 text-xs font-medium text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-300">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Lokasi terkirim
                </div>
            </form>
        </div>
    </div>

    {{-- Image lightbox modal --}}
    <div
        x-show="showImageModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="showImageModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
        style="display: none;"
        @click.self="showImageModal = false">

        {{-- Modal panel --}}
        <div class="relative max-h-[90vh] max-w-[90vw] overflow-hidden rounded-2xl shadow-2xl"
            @click.stop>
            {{-- Close button --}}
            <button type="button"
                class="absolute right-3 top-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-black/50 text-white transition hover:bg-black/70"
                @click="showImageModal = false">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <img :src="filePreview.url" :alt="filePreview.name"
                class="block max-h-[85vh] max-w-[85vw] object-contain">

            {{-- Caption --}}
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/60 to-transparent px-4 pb-3 pt-6">
                <p class="truncate text-xs font-medium text-white/90" x-text="filePreview.name"></p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('leaveForm', (initialOpen, initialType) => ({
            open: initialOpen === 'true',
            type: initialType,
            location: null,
            gettingLocation: false,

            // File preview state
            filePreview: {
                url: null,
                name: null,
                isImage: false,
                sizeLabel: '',
                sizeError: '',
                _objectUrl: null,   // track for revoke
                _pdfFile: null,     // track PDF file for blob open
            },
            showImageModal: false,

            init() {
                if (this.open) {
                    this.getLocation();
                    this.$nextTick(() => {
                        window.initAttendanceEditors?.(this.$el);
                    });
                }

                this.$watch('open', (value) => {
                    if (value) {
                        this.getLocation();
                        this.$nextTick(() => {
                            window.initAttendanceEditors?.(this.$el);
                        });
                    }
                });
            },

            destroy() {
                this._revokeObjectUrl();
            },

            _revokeObjectUrl() {
                if (this.filePreview._objectUrl) {
                    URL.revokeObjectURL(this.filePreview._objectUrl);
                    this.filePreview._objectUrl = null;
                }
            },

            _formatSize(bytes) {
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            },

            handleFileChange(event) {
                const file = event.target.files?.[0];
                if (!file) return;

                // Reset previous
                this._revokeObjectUrl();
                this.filePreview = {
                    url: null,
                    name: file.name,
                    isImage: false,
                    sizeLabel: this._formatSize(file.size),
                    sizeError: '',
                    _objectUrl: null,
                    _pdfFile: null,
                };

                // Soft size check (10 MB)
                const maxBytes = 10 * 1024 * 1024;
                if (file.size > maxBytes) {
                    this.filePreview.sizeError = 'Ukuran file melebihi 10 MB. Silakan pilih file yang lebih kecil.';
                    return;
                }

                const isImage = /^image\/(jpeg|png|gif|webp)$/i.test(file.type)
                    || /\.(jpe?g|png)$/i.test(file.name);
                const isPdf = file.type === 'application/pdf'
                    || /\.pdf$/i.test(file.name);

                if (isImage) {
                    const objectUrl = URL.createObjectURL(file);
                    this.filePreview.url = objectUrl;
                    this.filePreview.isImage = true;
                    this.filePreview._objectUrl = objectUrl;
                } else if (isPdf) {
                    this.filePreview.isImage = false;
                    this.filePreview._pdfFile = file;
                }
            },

            clearFile() {
                this._revokeObjectUrl();
                this.showImageModal = false;
                this.filePreview = {
                    url: null,
                    name: null,
                    isImage: false,
                    sizeLabel: '',
                    sizeError: '',
                    _objectUrl: null,
                    _pdfFile: null,
                };

                // Reset the actual file input
                const input = this.$el.querySelector('#doctor_note');
                if (input) {
                    input.value = '';
                }
            },

            openPdf() {
                const file = this.filePreview._pdfFile;
                if (!file) return;
                const url = URL.createObjectURL(file);
                window.open(url, '_blank', 'noopener,noreferrer');
                // Revoke after a short delay (tab has loaded)
                setTimeout(() => URL.revokeObjectURL(url), 5000);
            },

            async getLocation() {
                if (!navigator.geolocation) {
                    return;
                }

                this.gettingLocation = true;

                try {
                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(
                            resolve,
                            reject,
                            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                        );
                    });

                    this.location = {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                    };
                } catch (error) {
                    console.error('Gagal mengambil lokasi:', error);
                } finally {
                    this.gettingLocation = false;
                }
            }
        }));
    });
</script>
