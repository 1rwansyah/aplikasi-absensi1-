@props([

    'type',

    'settings',

    'hasFaceRegistered' => false,

    'needsFaceDescriptorSync' => false,

    'profilePhotoUrl' => null,

    'faceMatchThreshold' => 0.5,

    'faceMinMatchPercent' => 74,

    'title' => null,

    'subtitle' => null,

    'reportLabel' => null,

    'reportName' => null,

    'submitLabel' => null,

    'actionUrl' => null,

    'clockOutWindow' => null,

    'dualAction' => false,

    'showClockOutWaiting' => false,

    'clockOutOpensAt' => null,

    'hasClockIn' => false,

    'hasClockOut' => false,

    'reportMode' => null,

    'disableClockIn' => false,

    'pendingClockOutDate' => null,

])



@php

    $mapId = $type . '-map';

    $statusId = $type . '-geofence-status';

    $videoId = $type . '-face-video';

    $canvasId = $type . '-face-canvas';

    $faceStatusId = $type . '-face-status';

    $formId = $type . '-attendance-form';

    $requiresGeofence = $settings->hasGeofence();

    $faceReady = $profilePhotoUrl && ($hasFaceRegistered || $needsFaceDescriptorSync);


    $resolvedReportMode =

        $reportMode ??

        match ($type) {

            'clock-out' => 'hasil-pekerjaan',

            default => 'rencana-kerja',

        };



    $reportCopy = match ($resolvedReportMode) {

        'hasil-pekerjaan' => [

            'label' => 'Laporan - Apa yang Sudah Dikerjakan Hari Ini',

            'placeholder' => 'Tuliskan pekerjaan yang telah diselesaikan hari ini',

        ],

        default => [

            'label' => 'Laporan - Rencana Kerja',

            'placeholder' => 'Tuliskan rencana kerja yang akan dikerjakan hari ini',

        ],

    };



    $defaultFormType = $resolvedReportMode === 'hasil-pekerjaan' ? 'clock-out' : 'clock-in';

@endphp



<div

    {{ $attributes->merge(['class' => 'flex w-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800']) }}>

    <x-attendance.face-api-scripts />

    <x-attendance.geofence-scripts />



    @if ($title)

        <div class="relative overflow-hidden border-b border-red-100 bg-gradient-to-r from-red-50 via-white to-white px-5 py-5 dark:border-gray-700 dark:from-red-950/20 dark:via-gray-800 dark:to-gray-800 sm:px-8 sm:py-6">
            <div class="pointer-events-none absolute -right-10 -top-14 h-32 w-32 rounded-full border-[20px] border-red-100/50 dark:border-red-900/10"></div>

            <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-red-700 text-white shadow-lg shadow-red-600/20">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5a3 3 0 016 0m-6 7 2 2 4-4" />
                        </svg>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold tracking-tight text-gray-900 sm:text-xl dark:text-white">{{ $title }}</h2>
                        @if ($subtitle)
                            <p class="mt-1 text-sm leading-relaxed text-gray-600 dark:text-gray-400">{{ $subtitle }}</p>
                        @endif
                    </div>
                </div>

                @if ($dualAction)
                    @if ($faceReady)
                        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-50"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Verifikasi siap
                        </span>
                    @else
                        <span class="inline-flex w-fit items-center gap-2 rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                            Perlu data wajah
                        </span>
                    @endif
                @endif
            </div>

            @if ($pendingClockOutDate)

                <p class="relative mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">

                    Lanjutkan absen pulang untuk tugas security tanggal

                    {{ $pendingClockOutDate->translatedFormat('d F Y') }}.

                </p>

            @endif

            @if ($clockOutWindow)

                <p class="relative mt-3 text-xs font-medium text-gray-500 dark:text-gray-400">Rentang absen pulang:

                    {{ $clockOutWindow }}</p>

            @endif

        </div>

    @endif



    <div class="flex flex-1 flex-col p-6 sm:p-8">



        <form id="{{ $formId }}" class="flex flex-1 flex-col space-y-5" data-attendance-face-form

            data-action="{{ $actionUrl }}" data-type="{{ $dualAction ? $defaultFormType : $type }}"

            @if ($dualAction) data-dual-action="1"

                data-action-clock-in="{{ route('attendance.clock-in') }}"

                data-action-clock-out="{{ route('attendance.clock-out') }}" @endif

            data-video-id="face-video" data-canvas-id="face-canvas" data-face-status-id="face-status"

            data-face-badge-id="face-status-badge" data-geofence-map-id="{{ $requiresGeofence ? $mapId : '' }}"

            data-requires-geofence="{{ $requiresGeofence ? '1' : '0' }}"

            data-face-threshold="{{ $faceMatchThreshold }}" data-min-match-percent="{{ $faceMinMatchPercent }}"

            data-verify-url="{{ route('attendance.face.verify') }}"

            data-sync-url="{{ route('attendance.face.sync-descriptor') }}"

            data-profile-photo-url="{{ $profilePhotoUrl ?? '' }}"

            data-needs-sync="{{ $needsFaceDescriptorSync ? '1' : '0' }}"

            data-has-clock-in="{{ $hasClockIn ? '1' : '0' }}" data-has-clock-out="{{ $hasClockOut ? '1' : '0' }}">

            @csrf



            {{-- Report editor (CKEditor) --}}

            <div class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-gray-50/70 p-3.5 dark:border-gray-700 dark:bg-gray-900/30">
                @if ($profilePhotoUrl)
                    <img src="{{ $profilePhotoUrl }}" alt="Foto profil {{ auth()->user()->name }}"
                        class="h-10 w-10 shrink-0 rounded-xl object-cover shadow-sm ring-2 ring-white dark:ring-gray-700">
                @else
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-gray-400 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-500 dark:ring-gray-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0Zm4 10a7 7 0 00-14 0" />
                        </svg>
                    </span>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">{{ auth()->user()->name }}</p>
                    <p class="mt-0.5 text-xs capitalize text-gray-500 dark:text-gray-400">
                        {{ \App\Support\AppTime::now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>
                <span class="hidden rounded-lg bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-500 shadow-sm ring-1 ring-gray-200 sm:inline-flex dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">
                    Hari ini
                </span>
            </div>

            <label for="{{ $dualAction ? 'attendance-report' : $reportName }}"

                class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">

                {{ $reportCopy['label'] }}

            </label>

            <x-attendance.report-editor

                :name="$dualAction ? null : $reportName"

                :id="$dualAction ? 'attendance-report' : $reportName"

                :report-field="$dualAction"

                :required="true"

                :placeholder="$reportCopy['placeholder']"

                :value="old($dualAction ? 'attendance_report' : $reportName)"

            />

            @unless ($dualAction)

                @error($reportName)

                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>

                @enderror

            @endunless



            <div id="{{ $formId }}-alert" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>



            @if ($dualAction)

                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Pilih aksi absensi</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500">Verifikasi terbuka setelah dipilih</p>
                    </div>

                <div class="grid grid-cols-2 gap-3">

                    <button type="button" data-attendance-action="clock-in" @disabled(!$faceReady || $disableClockIn)

                        @if ($disableClockIn) title="Selesaikan absen pulang tugas security terlebih dahulu" @endif

                        class="group flex min-h-14 items-center justify-center gap-2.5 rounded-2xl border border-red-200 bg-red-50/50 px-4 py-3.5 text-sm font-semibold text-red-700 shadow-sm transition hover:border-red-300 hover:bg-red-50 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-900 dark:bg-red-950/20 dark:text-red-300 dark:hover:bg-red-950/30 sm:text-base">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white text-red-600 shadow-sm ring-1 ring-red-100 transition group-hover:scale-105 dark:bg-gray-800 dark:text-red-400 dark:ring-red-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5 4 12m0 0 7 7m-7-7h16" />
                            </svg>
                        </span>
                        Masuk

                    </button>



                    <button type="button" data-attendance-action="clock-out" @disabled(!$faceReady || $showClockOutWaiting)

                        @if ($showClockOutWaiting && $clockOutOpensAt) title="Absen pulang dibuka mulai {{ $clockOutOpensAt }}" @endif

                        class="group flex min-h-14 items-center justify-center gap-2.5 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-4 py-3.5 text-sm font-semibold text-white shadow-md shadow-red-600/20 transition hover:from-red-700 hover:to-red-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 sm:text-base">
                        Pulang
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-white/15 text-white transition group-hover:scale-105">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m13 5 7 7m0 0-7 7m7-7H4" />
                            </svg>
                        </span>

                    </button>

                </div>
                </div>

                @if ($showClockOutWaiting && $clockOutOpensAt)

                    <p class="text-center text-xs text-amber-700 dark:text-amber-300">

                        Belum waktunya absen pulang. Dibuka mulai <strong>{{ $clockOutOpensAt }}</strong>

                        @if ($clockOutWindow)

                            · rentang {{ $clockOutWindow }}

                        @endif

                    </p>

                @endif

            @else

                {{-- Primary submit button --}}

                <button type="submit" id="{{ $formId }}-submit"

                    @if (!$profilePhotoUrl || (!$hasFaceRegistered && !$needsFaceDescriptorSync)) disabled @endif

                    class="flex w-full items-center justify-center gap-2.5 rounded-xl bg-red-600 px-6 py-3.5 text-base font-semibold text-white shadow-sm hover:bg-red-700 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 transition-all">

                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0" />

                    </svg>

                    {{ $submitLabel }}

                </button>

            @endif



            {{-- Secondary: face badge + GPS (below button) --}}

            <div class="space-y-2 pt-1">

                @if (!$profilePhotoUrl)

                    <div

                        class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-300">

                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />

                        </svg>

                        Foto profil belum tersedia. Hubungi HR untuk menambahkan foto profil. Absensi belum tercatat.

                    </div>

                @elseif (!$hasFaceRegistered && !$needsFaceDescriptorSync)

                    <div

                        class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-800 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-300">

                        <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />

                        </svg>

                        Data wajah belum siap. Hubungi HR untuk memperbarui foto profil. Absensi belum tercatat.

                    </div>

                @else

                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-3 py-2.5 text-xs leading-relaxed text-sky-800 dark:border-sky-900/50 dark:bg-sky-950/30 dark:text-sky-200">
                        Begitu halaman ini terbuka, browser meminta akses <strong>lokasi GPS</strong> dan <strong>kamera</strong>. Tekan <strong>Izinkan</strong> atau Allow. Izin cukup sekali; di verifikasi wajah Anda tinggal menghadapkan wajah dan mengambil foto.
                    </div>

                @endif



                @if ($requiresGeofence && $dualAction)

                    <div class="flex items-center gap-2 px-1 text-xs text-gray-500 dark:text-gray-400">

                        <svg class="h-3 w-3 shrink-0 text-green-500" fill="none" stroke="currentColor"

                            viewBox="0 0 24 24">

                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"

                                d="M5 13l4 4L19 7" />

                        </svg>

                        Verifikasi lokasi GPS

                    </div>

                @endif



                @if ($requiresGeofence)

                    <div x-data="{ open: false }" class="rounded-xl border border-gray-200 dark:border-gray-600">

                        <button type="button"

                            @click="

        open = !open;



        if (open) {

            setTimeout(() => {

                const instance = window.GeofenceMap?.getInstance('{{ $mapId }}');



                if (instance) {

                    instance.map.invalidateSize();

                }

            }, 300);

        }

    "

                            class="flex w-full items-center justify-between px-4 py-2.5 text-left">

                            <div class="flex items-center gap-2">

                                <svg class="h-3.5 w-3.5 text-blue-500" fill="none" stroke="currentColor"

                                    viewBox="0 0 24 24">

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />

                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0" />

                                </svg>

                                <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Verifikasi Lokasi

                                    GPS</span>

                                <span

                                    class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">

                                    {{ $settings->formattedAttendanceRadius() }}

                                </span>

                            </div>

                            <svg class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200"

                                :class="open && 'rotate-180'" fill="none" stroke="currentColor"

                                viewBox="0 0 24 24">

                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"

                                    d="M19 9l-7 7-7-7" />

                            </svg>

                        </button>

                        <div id="{{ $statusId }}"

                            class="border-t border-gray-100 px-4 py-2 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">

                            Mengambil lokasi GPS...

                        </div>

                        <div x-show="open" x-transition class="border-t border-gray-100 p-3 dark:border-gray-700">

                            <x-attendance.geofence-map :map-id="$mapId" :office-lat="$settings->office_latitude" :office-lng="$settings->office_longitude"

                                :radius-meters="$settings->attendance_radius_meters" :track-user="true" :status-target="$statusId" />

                        </div>

                    </div>

                @endif

            </div>

        </form>



    </div>

</div>



@once

    @push('scripts')

        <script type="application/json" id="attendance-face-config-json">

            {!! json_encode([

                'hasFaceRegistered' => (bool) $hasFaceRegistered,

                'needsFaceDescriptorSync' => (bool) $needsFaceDescriptorSync,

                'profilePhotoUrl' => $profilePhotoUrl,

                'threshold' => (float) $faceMatchThreshold,

                'minMatchPercent' => (int) $faceMinMatchPercent,

            ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}

        </script>

        <script>

            window.attendanceFaceConfig = JSON.parse(

                document.getElementById('attendance-face-config-json').textContent,

            );



            async function attendanceCsrfFetch(url, options = {}) {

                const fetchFn = window.csrfFetch ?? fetch;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

                const headers = {

                    Accept: 'application/json',

                    ...(options.headers ?? {}),

                };



                if (csrfToken && !window.csrfFetch) {

                    headers['X-CSRF-TOKEN'] = csrfToken;

                }



                const response = await fetchFn(url, {

                    ...options,

                    headers,

                });



                if (!window.csrfFetch && response.status === 419) {

                    alert('Sesi halaman sudah kedaluwarsa. Halaman akan dimuat ulang.');

                    window.location.reload();

                    return null;

                }



                return response;

            }



            // Legacy stub kept for geofence compatibility

            window.FaceAttendance = {

                modelsLoaded: false,

                stream: null,

                detectorOptions: null,



                modelPath() {

                    return document.querySelector('meta[name="face-model-path"]')?.content || '/models';

                },



                async loadModels() {

                    if (this.modelsLoaded) {

                        return true;

                    }



                    if (typeof faceapi === 'undefined') {

                        return false;

                    }



                    try {

                        const modelPath = this.modelPath();



                        await Promise.all([

                            faceapi.nets.tinyFaceDetector.loadFromUri(modelPath),

                            faceapi.nets.faceLandmark68Net.loadFromUri(modelPath),

                            faceapi.nets.faceRecognitionNet.loadFromUri(modelPath),

                        ]);



                        this.detectorOptions = new faceapi.TinyFaceDetectorOptions({

                            inputSize: 160,

                            scoreThreshold: 0.5,

                        });



                        this.modelsLoaded = true;

                        return true;

                    } catch (error) {

                        console.error('Gagal memuat model face-api:', error);

                        return false;

                    }

                },



                async startCamera(videoEl, statusEl, badgeEl) {

                    if (!navigator.mediaDevices?.getUserMedia) {

                        this.setStatus(statusEl, badgeEl, 'Kamera tidak didukung di peramban ini.', 'error');

                        return false;

                    }



                    try {

                        this.stream = await navigator.mediaDevices.getUserMedia({

                            video: {

                                facingMode: 'user',

                                width: {

                                    ideal: 640

                                },

                                height: {

                                    ideal: 480

                                }

                            },

                            audio: false,

                        });

                        videoEl.srcObject = this.stream;

                        await videoEl.play();

                        this.setStatus(statusEl, badgeEl, 'Kamera aktif — hadapkan wajah ke layar.', 'ready');

                        return true;

                    } catch {

                        this.setStatus(statusEl, badgeEl, 'Gagal membuka kamera. Izinkan akses kamera.', 'error');

                        return false;

                    }

                },



                setStatus(statusEl, badgeEl, message, type) {

                    if (statusEl) statusEl.textContent = message;

                    if (!badgeEl) return;



                    const classes = {

                        loading: 'inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300',

                        ready: 'inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/50 dark:text-green-200',

                        error: 'inline-flex rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-800 dark:bg-red-900/50 dark:text-red-200',

                        scanning: 'inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-200',

                    };



                    badgeEl.className = classes[type] || classes.loading;

                    badgeEl.textContent = type === 'ready' ? 'Siap Scan' : type === 'scanning' ? 'Memindai...' : type ===

                        'error' ? 'Error' : 'Memuat...';

                },



                async detectFace(videoEl) {

                    const detection = await faceapi

                        .detectAllFaces(videoEl, this.detectorOptions)

                        .withFaceLandmarks()

                        .withFaceDescriptors();



                    return detection;

                },



                captureFrame(videoEl, canvasEl) {

                    canvasEl.width = videoEl.videoWidth;

                    canvasEl.height = videoEl.videoHeight;

                    const ctx = canvasEl.getContext('2d');

                    ctx.drawImage(videoEl, 0, 0);

                    return canvasEl.toDataURL('image/jpeg', 0.85);

                },



                async getGeolocation(mapId) {

                    if (!mapId) {

                        return {

                            latitude: null,

                            longitude: null,

                            withinRadius: true

                        };

                    }



                    const instance = window.GeofenceMap?.getInstance(mapId);

                    if (!instance) {

                        throw new Error('Peta geofence belum siap.');

                    }



                    const result = await instance.trackUserLocation();

                    return {

                        latitude: result.latitude,

                        longitude: result.longitude,

                        withinRadius: result.withinRadius,

                    };

                },



                showFormAlert(form, message, type) {

                    const alert = form.querySelector('[id$="-alert"]');

                    if (!alert) return;



                    alert.classList.remove('hidden');

                    alert.textContent = message;

                    alert.className = type === 'success' ?

                        'rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700 dark:border-green-800 dark:bg-green-950/40 dark:text-green-200' :

                        'rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200';

                },



                async handleSubmit(form) {

                    const videoId = form.dataset.videoId;

                    const canvasId = form.dataset.canvasId;

                    const videoEl = document.getElementById(videoId);

                    const canvasEl = document.getElementById(canvasId);

                    const statusEl = document.getElementById(form.dataset.faceStatusId);

                    const badgeEl = document.getElementById(form.dataset.faceBadgeId);

                    const submitBtn = form.querySelector('[type="submit"]');

                    const reportName = form.dataset.type === 'clock-in' ? 'clock_in_report' : 'clock_out_report';

                    const report = typeof window.getAttendanceReportHtml === 'function' ?

                        window.getAttendanceReportHtml(form) :

                        form.querySelector(`[name="${reportName}"]`)?.value?.trim();

                    const reportPlainLength = typeof window.getAttendanceReportPlainLength === 'function' ?

                        window.getAttendanceReportPlainLength(form) :

                        (report || '')

                        .replace(/<[^>]+>/g, '').trim().length;



                    if (!report || reportPlainLength < 15) {

                        this.showFormAlert(form, 'Laporan minimal 15 karakter.', 'error');

                        return;

                    }



                    submitBtn.disabled = true;

                    this.setStatus(statusEl, badgeEl, 'Memindai wajah...', 'scanning');



                    try {

                        if (!this.modelsLoaded) {

                            await this.loadModels();

                        }



                        const detections = await this.detectFace(videoEl);

                        const facesDetected = detections.length;



                        if (facesDetected !== 1) {

                            const msg = facesDetected < 1 ?

                                'Wajah tidak terdeteksi. Pastikan pencahayaan cukup.' :

                                'Terdeteksi lebih dari satu wajah.';

                            this.setStatus(statusEl, badgeEl, msg, 'error');

                            this.showFormAlert(form, msg, 'error');

                            return;

                        }



                        const descriptor = Array.from(detections[0].descriptor);

                        const photo = this.captureFrame(videoEl, canvasEl);



                        const geo = await this.getGeolocation(form.dataset.geofenceMapId || '');

                        if (form.dataset.requiresGeofence === '1' && !geo.withinRadius) {

                            this.showFormAlert(form, 'Anda berada di luar radius absensi kantor.', 'error');

                            return;

                        }



                        const payload = {

                            [reportName]: report,

                            face_descriptor: descriptor,

                            faces_detected: facesDetected,

                            verification_photo: photo,

                            device_type: /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop',

                            client_time: Math.floor(Date.now() / 1000),

                        };



                        if (geo.latitude !== null) {

                            payload.latitude = geo.latitude;

                            payload.longitude = geo.longitude;

                            payload.accuracy = geo.accuracy;

                        }



                        const response = await attendanceCsrfFetch(form.dataset.action, {

                            method: 'POST',

                            headers: {

                                'Content-Type': 'application/json',

                            },

                            body: JSON.stringify(payload),

                        });



                        if (!response) {

                            return;

                        }



                        const data = await response.json();



                        if (!response.ok || !data.success) {

                            this.setStatus(statusEl, badgeEl, data.message || 'Absensi gagal.', 'error');

                            this.showFormAlert(form, data.message || 'Absensi gagal.', 'error');

                            return;

                        }



                        this.setStatus(statusEl, badgeEl, 'Verifikasi berhasil!', 'ready');

                        window.location.reload();

                    } catch (error) {

                        this.setStatus(statusEl, badgeEl, 'Terjadi kesalahan. Coba lagi.', 'error');

                        this.showFormAlert(form, error.message || 'Terjadi kesalahan.', 'error');

                    } finally {

                        submitBtn.disabled = false;

                    }

                },



                loadProfileImage(url) {

                    return new Promise((resolve, reject) => {

                        const image = new Image();

                        image.onload = () => resolve(image);

                        image.onerror = () => reject(new Error(

                            'Foto profil tidak dapat dimuat. Hubungi HR.'));

                        image.src = url;

                    });

                },



                async syncProfileDescriptor(form, statusEl, badgeEl) {

                    if (form.dataset.needsSync !== '1' || !form.dataset.profilePhotoUrl) {

                        return true;

                    }



                    this.setStatus(statusEl, badgeEl, 'Menyiapkan data wajah dari foto profil...', 'loading');



                    const img = await this.loadProfileImage(form.dataset.profilePhotoUrl);

                    const detections = await this.detectFace(img);



                    if (detections.length !== 1) {

                        const msg = detections.length < 1 ?

                            'Wajah tidak terdeteksi pada foto profil. Hubungi HR.' :

                            'Foto profil harus hanya menampilkan satu wajah.';

                        this.setStatus(statusEl, badgeEl, msg, 'error');

                        return false;

                    }



                    const response = await attendanceCsrfFetch(form.dataset.syncUrl, {

                        method: 'POST',

                        headers: {

                            'Content-Type': 'application/json',

                        },

                        body: JSON.stringify({

                            face_descriptor: Array.from(detections[0].descriptor),

                            faces_detected: 1,

                        }),

                    });



                    if (!response) {

                        return false;

                    }



                    const data = await response.json();



                    if (!response.ok || !data.success) {

                        this.setStatus(statusEl, badgeEl, data.message || 'Gagal menyiapkan data wajah.', 'error');

                        return false;

                    }



                    form.dataset.needsSync = '0';

                    return true;

                },



                async initForm(form) {

                    const videoEl = document.getElementById(form.dataset.videoId);

                    const statusEl = document.getElementById(form.dataset.faceStatusId);

                    const badgeEl = document.getElementById(form.dataset.faceBadgeId);

                    const submitBtn = form.querySelector('[type="submit"]');



                    try {

                        this.setStatus(statusEl, badgeEl, 'Memuat model AI...', 'loading');



                        const modelsOk = await this.loadModels();

                        if (!modelsOk) {

                            this.setStatus(statusEl, badgeEl,

                                'Gagal memuat model AI. Periksa koneksi internet lalu muat ulang halaman.', 'error');

                            return;

                        }



                        const synced = await this.syncProfileDescriptor(form, statusEl, badgeEl);

                        if (!synced) {

                            return;

                        }



                        this.setStatus(statusEl, badgeEl, 'Membuka kamera...', 'loading');



                        const cameraOk = await this.startCamera(videoEl, statusEl, badgeEl);

                        if (cameraOk) {

                            submitBtn.disabled = false;

                        }

                    } catch (error) {

                        console.error('Inisialisasi verifikasi wajah gagal:', error);

                        this.setStatus(statusEl, badgeEl, error.message || 'Gagal memuat verifikasi wajah.', 'error');

                    }



                    form.addEventListener('submit', (e) => {

                        e.preventDefault();

                        this.handleSubmit(form);

                    });

                },



                boot() {

                    document.querySelectorAll('[data-attendance-face-form]').forEach((form) => {

                        this.initForm(form);

                    });

                },

            };



            function bootGeofenceOnly() {

                if (window.GeofenceMap && !window.GeofenceMap.booted) {

                    window.GeofenceMap.initAll();

                    window.GeofenceMap.booted = true;

                }

            }



            if (document.readyState === 'loading') {

                document.addEventListener('DOMContentLoaded', bootGeofenceOnly);

            } else {

                bootGeofenceOnly();

            }

        </script>

    @endpush

@endonce

