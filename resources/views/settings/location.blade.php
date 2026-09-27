<x-app-layout>
    @php
        $latitude = old('office_latitude', $settings->office_latitude ?? -6.200000);
        $longitude = old('office_longitude', $settings->office_longitude ?? 106.816666);
        $radius = (int) old('attendance_radius_meters', $settings->attendance_radius_meters ?? 1000);
        $presets = [100, 250, 500, 1000, 2000, 3000];
    @endphp

    <div class="py-6">
        <div
            class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8"
            x-data="{
                lat: '{{ $latitude }}',
                lng: '{{ $longitude }}',
                radius: {{ $radius }},
                radiusLabel() {
                    const value = Number(this.radius) || 0;
                    if (value >= 1000 && value % 1000 === 0) {
                        return (value / 1000) + ' km';
                    }
                    return new Intl.NumberFormat('id-ID').format(value) + ' m';
                },
                syncRadiusFromInput() {
                    const input = document.getElementById('attendance_radius_meters');
                    this.radius = parseInt(input?.value || '0', 10) || 0;
                },
                applyPreset(value) {
                    const input = document.getElementById('attendance_radius_meters');
                    if (!input) return;
                    input.value = value;
                    this.radius = value;
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }"
            x-init="
                const latInput = document.getElementById('office_latitude');
                const lngInput = document.getElementById('office_longitude');
                const radiusInput = document.getElementById('attendance_radius_meters');
                latInput?.addEventListener('input', (e) => lat = e.target.value);
                lngInput?.addEventListener('input', (e) => lng = e.target.value);
                radiusInput?.addEventListener('input', () => syncRadiusFromInput());
                radiusInput?.addEventListener('change', () => syncRadiusFromInput());
            ">

            {{-- Header --}}
            <section class="relative overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>
                <div class="grid gap-5 px-5 py-5 sm:px-6 sm:py-6 lg:grid-cols-[minmax(0,1.2fr)_auto] lg:items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-700 ring-1 ring-inset ring-red-100 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900/50">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Geofence Absensi
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Lokasi GPS Kantor</h1>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Tentukan titik pusat kantor dan radius absensi. Geser pin di peta atau isi koordinat secara manual.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-3 py-3 dark:border-gray-700 dark:bg-gray-900/50">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Latitude</p>
                            <p class="mt-1 truncate text-sm font-bold tabular-nums text-gray-900 dark:text-white" x-text="Number(lat).toFixed(6)"></p>
                        </div>
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-3 py-3 dark:border-gray-700 dark:bg-gray-900/50">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Longitude</p>
                            <p class="mt-1 truncate text-sm font-bold tabular-nums text-gray-900 dark:text-white" x-text="Number(lng).toFixed(6)"></p>
                        </div>
                        <div class="rounded-2xl border border-red-100 bg-red-50 px-3 py-3 dark:border-red-900/40 dark:bg-red-950/30">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-500 dark:text-red-300">Radius</p>
                            <p class="mt-1 truncate text-sm font-bold tabular-nums text-red-700 dark:text-red-200" x-text="radiusLabel()"></p>
                        </div>
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('settings.location.update') }}" class="space-y-5">
                @csrf
                @method('PATCH')

                @if (session('success'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200">
                        <p class="font-semibold">Periksa kembali isian berikut:</p>
                        <ul class="mt-2 list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="grid gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
                    {{-- Controls --}}
                    <aside class="space-y-4">
                        <section class="overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                                <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Pengaturan Titik</h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Koordinat pusat dan jarak absensi.</p>
                            </div>

                            <div class="space-y-4 p-5">
                                <label for="office_latitude" class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Latitude <span class="text-red-500">*</span>
                                    </span>
                                    <input id="office_latitude" type="number" step="any" name="office_latitude" required
                                        value="{{ $latitude }}"
                                        class="block min-h-11 w-full rounded-2xl border-gray-200 bg-gray-50 text-sm font-semibold tabular-nums text-gray-900 shadow-sm transition focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    @error('office_latitude')
                                        <span class="mt-1.5 block text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label for="office_longitude" class="block">
                                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Longitude <span class="text-red-500">*</span>
                                    </span>
                                    <input id="office_longitude" type="number" step="any" name="office_longitude" required
                                        value="{{ $longitude }}"
                                        class="block min-h-11 w-full rounded-2xl border-gray-200 bg-gray-50 text-sm font-semibold tabular-nums text-gray-900 shadow-sm transition focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    @error('office_longitude')
                                        <span class="mt-1.5 block text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                                    @enderror
                                </label>

                                <div>
                                    <label for="attendance_radius_meters" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        Radius Absensi (meter) <span class="text-red-500">*</span>
                                    </label>
                                    <input id="attendance_radius_meters" type="number" min="1" max="50000" step="1" name="attendance_radius_meters" required
                                        value="{{ $radius }}"
                                        class="block min-h-11 w-full rounded-2xl border-gray-200 bg-gray-50 text-sm font-semibold tabular-nums text-gray-900 shadow-sm transition focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Lingkaran di peta mengikuti nilai ini.</p>
                                    @error('attendance_radius_meters')
                                        <p class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Preset cepat</p>
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach ($presets as $preset)
                                            <button
                                                type="button"
                                                data-radius-preset="{{ $preset }}"
                                                @click="applyPreset({{ $preset }})"
                                                :class="radius === {{ $preset }}
                                                    ? 'border-red-500 bg-red-600 text-white shadow-sm shadow-red-900/10'
                                                    : 'border-gray-200 bg-white text-gray-700 hover:border-red-300 hover:bg-red-50 hover:text-red-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:border-red-700 dark:hover:bg-red-950/40 dark:hover:text-red-300'"
                                                class="rounded-xl border px-2.5 py-2 text-xs font-bold transition">
                                                {{ $preset >= 1000 && $preset % 1000 === 0 ? ($preset / 1000).' KM' : $preset.' m' }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[1.75rem] border border-gray-200/80 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Panduan singkat</h3>
                            <ul class="mt-3 space-y-2.5 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                <li class="flex gap-2">
                                    <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-red-50 text-[10px] font-bold text-red-600 dark:bg-red-950/40 dark:text-red-300">1</span>
                                    <span>Geser pin di peta untuk memindahkan titik pusat kantor.</span>
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-red-50 text-[10px] font-bold text-red-600 dark:bg-red-950/40 dark:text-red-300">2</span>
                                    <span>Pilih preset radius atau isi manual sesuai area absensi.</span>
                                </li>
                                <li class="flex gap-2">
                                    <span class="mt-0.5 inline-flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-red-50 text-[10px] font-bold text-red-600 dark:bg-red-950/40 dark:text-red-300">3</span>
                                    <span>Tekan Simpan Lokasi agar aturan baru aktif untuk karyawan.</span>
                                </li>
                            </ul>
                        </section>
                    </aside>

                    {{-- Map --}}
                    <section class="overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Peta Kantor</h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                    Marker = pusat kantor · Lingkaran = radius absensi
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-600 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                    Titik pusat
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-50 px-2.5 py-1 text-[11px] font-semibold text-sky-700 ring-1 ring-inset ring-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:ring-sky-900/50">
                                    <span class="h-1.5 w-1.5 rounded-full bg-sky-500"></span>
                                    <span x-text="'Radius ' + radiusLabel()"></span>
                                </span>
                            </div>
                        </div>

                        <div class="p-3 sm:p-4">
                            <div class="overflow-hidden rounded-2xl ring-1 ring-gray-100 dark:ring-gray-700">
                                <x-attendance.geofence-scripts />
                                <x-attendance.geofence-map
                                    map-id="settings-office-map"
                                    :office-lat="$latitude"
                                    :office-lng="$longitude"
                                    :radius-meters="$radius"
                                    :editable="true"
                                    height-class="h-[28rem] sm:h-[36rem] xl:h-[42rem]"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <div class="sticky bottom-4 z-30">
                    <div class="flex flex-col-reverse gap-3 rounded-2xl border border-gray-200/80 bg-white/95 px-4 py-3 shadow-lg shadow-gray-900/5 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-900/95 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                            Perubahan baru aktif setelah lokasi disimpan.
                        </p>
                        <button type="submit"
                            class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-red-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-900/10 transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Simpan Lokasi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
