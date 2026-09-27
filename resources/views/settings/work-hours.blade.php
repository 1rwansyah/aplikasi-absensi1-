<x-app-layout>
    @php
        $securitySchedule = \App\Models\WorkSchedule::query()->where('code', 'security')->first();
        $obSchedule = \App\Models\WorkSchedule::query()->where('code', 'ob')->first();
        $engineeringSchedule = \App\Models\WorkSchedule::query()->where('code', 'engineering')->first();

        $timeValue = static function (?string $value, string $fallback = ''): string {
            if (! filled($value)) {
                return $fallback;
            }

            return substr($value, 0, 5);
        };

        $sections = [
            [
                'key' => 'regular',
                'title' => 'Jam Kerja Reguler',
                'subtitle' => 'Jadwal default untuk karyawan reguler (bukan Security/OB/Engineering).',
                'accent' => 'blue',
                'badge' => 'Default',
                'fields' => [
                    [
                        'id' => 'office_start',
                        'name' => 'office_start',
                        'label' => 'Jam Masuk',
                        'required' => true,
                        'value' => old('office_start', $settings->formattedOfficeStart()),
                        'hint' => null,
                    ],
                    [
                        'id' => 'late_limit',
                        'name' => 'late_limit',
                        'label' => 'Batas Telat',
                        'required' => true,
                        'value' => old('late_limit', $settings->formattedLateLimit()),
                        'hint' => 'Setelah jam ini status Telat.',
                    ],
                    [
                        'id' => 'clock_out_start',
                        'name' => 'clock_out_start',
                        'label' => 'Jam Pulang',
                        'required' => true,
                        'value' => old('clock_out_start', $settings->formattedClockOutStart()),
                        'hint' => null,
                    ],
                    [
                        'id' => 'clock_out_limit',
                        'name' => 'clock_out_limit',
                        'label' => 'Batas Pulang Lewat',
                        'required' => true,
                        'value' => old('clock_out_limit', $settings->formattedClockOutLimit()),
                        'hint' => 'Setelah jam ini status Pulang Lewat.',
                    ],
                ],
            ],
            [
                'key' => 'security',
                'title' => 'Jam Kerja Security',
                'subtitle' => 'Jadwal khusus untuk petugas Security.',
                'accent' => 'rose',
                'badge' => 'Security',
                'fields' => [
                    [
                        'id' => 'security_clock_in_start',
                        'name' => 'security_clock_in_start',
                        'label' => 'Jam Masuk',
                        'required' => true,
                        'value' => old('security_clock_in_start', $timeValue($securitySchedule?->clock_in_start, '07:00')),
                        'hint' => null,
                    ],
                    [
                        'id' => 'security_late_limit',
                        'name' => 'security_late_limit',
                        'label' => 'Batas Telat',
                        'required' => true,
                        'value' => old('security_late_limit', $timeValue($securitySchedule?->late_limit, '07:15')),
                        'hint' => 'Setelah jam ini status Telat.',
                    ],
                    [
                        'id' => 'security_clock_out_start',
                        'name' => 'security_clock_out_start',
                        'label' => 'Jam Pulang',
                        'required' => true,
                        'value' => old('security_clock_out_start', $timeValue($securitySchedule?->clock_out_start, '19:00')),
                        'hint' => null,
                    ],
                    [
                        'id' => 'security_clock_out_limit',
                        'name' => 'security_clock_out_limit',
                        'label' => 'Batas Pulang Lewat',
                        'required' => false,
                        'value' => old('security_clock_out_limit', $timeValue($securitySchedule?->clock_out_limit)),
                        'hint' => 'Kosongkan jika tidak ada batas.',
                    ],
                ],
            ],
            [
                'key' => 'ob',
                'title' => 'Jam Kerja OB',
                'subtitle' => 'Jadwal khusus untuk petugas OB (Office Boy).',
                'accent' => 'amber',
                'badge' => 'OB',
                'fields' => [
                    [
                        'id' => 'ob_clock_in_start',
                        'name' => 'ob_clock_in_start',
                        'label' => 'Jam Masuk',
                        'required' => true,
                        'value' => old('ob_clock_in_start', $timeValue($obSchedule?->clock_in_start, '07:30')),
                        'hint' => null,
                    ],
                    [
                        'id' => 'ob_late_limit',
                        'name' => 'ob_late_limit',
                        'label' => 'Batas Telat',
                        'required' => true,
                        'value' => old('ob_late_limit', $timeValue($obSchedule?->late_limit, '07:30')),
                        'hint' => 'Setelah jam ini status Telat.',
                    ],
                    [
                        'id' => 'ob_clock_out_start',
                        'name' => 'ob_clock_out_start',
                        'label' => 'Jam Pulang',
                        'required' => true,
                        'value' => old('ob_clock_out_start', $timeValue($obSchedule?->clock_out_start, '18:00')),
                        'hint' => null,
                    ],
                    [
                        'id' => 'ob_clock_out_limit',
                        'name' => 'ob_clock_out_limit',
                        'label' => 'Batas Pulang Lewat',
                        'required' => false,
                        'value' => old('ob_clock_out_limit', $timeValue($obSchedule?->clock_out_limit)),
                        'hint' => 'Kosongkan jika tidak ada batas.',
                    ],
                ],
            ],
            [
                'key' => 'engineering',
                'title' => 'Jam Kerja Staff Engineering',
                'subtitle' => 'Shift malam dini hari. Lembur dihitung otomatis 1 jam setelah jam pulang. Minggu libur otomatis.',
                'accent' => 'slate',
                'badge' => 'Engineering',
                'fields' => [
                    [
                        'id' => 'engineering_clock_in_start',
                        'name' => 'engineering_clock_in_start',
                        'label' => 'Jam Masuk',
                        'required' => true,
                        'value' => old('engineering_clock_in_start', $timeValue($engineeringSchedule?->clock_in_start, '00:00')),
                        'hint' => null,
                    ],
                    [
                        'id' => 'engineering_late_limit',
                        'name' => 'engineering_late_limit',
                        'label' => 'Batas Telat',
                        'required' => true,
                        'value' => old('engineering_late_limit', $timeValue($engineeringSchedule?->late_limit, '00:15')),
                        'hint' => 'Setelah jam ini status Telat.',
                    ],
                    [
                        'id' => 'engineering_clock_out_start',
                        'name' => 'engineering_clock_out_start',
                        'label' => 'Jam Pulang',
                        'required' => true,
                        'value' => old('engineering_clock_out_start', $timeValue($engineeringSchedule?->clock_out_start, '05:00')),
                        'hint' => null,
                    ],
                    [
                        'id' => 'engineering_clock_out_limit',
                        'name' => 'engineering_clock_out_limit',
                        'label' => 'Batas Pulang Lewat',
                        'required' => false,
                        'value' => old(
                            'engineering_clock_out_limit',
                            $timeValue($engineeringSchedule?->clock_out_limit, '05:15')
                        ),
                        'hint' => 'Lembur mulai 1 jam setelah jam pulang.',
                    ],
                ],
            ],
        ];

        $tones = [
            'blue' => [
                'shell' => 'border-blue-100 dark:border-blue-900/40',
                'header' => 'from-blue-50/90 to-white dark:from-blue-950/30 dark:to-gray-800',
                'icon' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300',
                'badge' => 'bg-blue-50 text-blue-700 ring-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-900/50',
                'focus' => 'focus:border-blue-500 focus:ring-blue-500',
            ],
            'rose' => [
                'shell' => 'border-rose-100 dark:border-rose-900/40',
                'header' => 'from-rose-50/90 to-white dark:from-rose-950/30 dark:to-gray-800',
                'icon' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300',
                'badge' => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-900/50',
                'focus' => 'focus:border-rose-500 focus:ring-rose-500',
            ],
            'amber' => [
                'shell' => 'border-amber-100 dark:border-amber-900/40',
                'header' => 'from-amber-50/90 to-white dark:from-amber-950/30 dark:to-gray-800',
                'icon' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300',
                'badge' => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-900/50',
                'focus' => 'focus:border-amber-500 focus:ring-amber-500',
            ],
            'slate' => [
                'shell' => 'border-slate-200 dark:border-slate-700',
                'header' => 'from-slate-50/90 to-white dark:from-slate-900/40 dark:to-gray-800',
                'icon' => 'bg-slate-100 text-slate-700 dark:bg-slate-700/60 dark:text-slate-200',
                'badge' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
                'focus' => 'focus:border-slate-500 focus:ring-slate-500',
            ],
        ];
    @endphp

    <div class="py-6">
        <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Header --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-red-900/10 bg-gradient-to-br from-red-700 via-red-600 to-rose-600 px-5 py-6 text-white shadow-lg shadow-red-900/10 sm:px-7 sm:py-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-50">
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-300"></span>
                            Pengaturan Sistem
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Pengaturan Jam Kerja</h1>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-red-50/90">
                            Atur jam masuk, batas telat, jam pulang, dan batas pulang lewat untuk setiap jenis jadwal karyawan.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        @foreach ([
                            ['label' => 'Reguler', 'value' => $settings->formattedOfficeStart().'–'.$settings->formattedClockOutStart()],
                            ['label' => 'Security', 'value' => $timeValue($securitySchedule?->clock_in_start, '07:00').'–'.$timeValue($securitySchedule?->clock_out_start, '19:00')],
                            ['label' => 'OB', 'value' => $timeValue($obSchedule?->clock_in_start, '07:30').'–'.$timeValue($obSchedule?->clock_out_start, '18:00')],
                            ['label' => 'Engineering', 'value' => $timeValue($engineeringSchedule?->clock_in_start, '00:00').'–'.$timeValue($engineeringSchedule?->clock_out_start, '05:00')],
                        ] as $chip)
                            <div class="rounded-2xl border border-white/15 bg-white/10 px-3.5 py-3 backdrop-blur-sm">
                                <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">{{ $chip['label'] }}</p>
                                <p class="mt-0.5 text-sm font-bold tabular-nums">{{ $chip['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('settings.work-hours.update') }}" class="space-y-5">
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

                @foreach ($sections as $section)
                    @php $tone = $tones[$section['accent']]; @endphp
                    <section class="overflow-hidden rounded-3xl border bg-white shadow-sm dark:bg-gray-800 {{ $tone['shell'] }}">
                        <div class="flex flex-col gap-3 border-b border-gray-100 bg-gradient-to-r px-5 py-4 dark:border-gray-700/80 sm:flex-row sm:items-center sm:justify-between sm:px-6 {{ $tone['header'] }}">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl {{ $tone['icon'] }}">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">{{ $section['title'] }}</h2>
                                    <p class="mt-0.5 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ $section['subtitle'] }}</p>
                                </div>
                            </div>
                            <span class="inline-flex w-fit items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 ring-inset {{ $tone['badge'] }}">
                                {{ $section['badge'] }}
                            </span>
                        </div>

                        <div class="grid gap-4 p-5 sm:grid-cols-2 sm:gap-5 sm:p-6">
                            @foreach ($section['fields'] as $field)
                                <label for="{{ $field['id'] }}" class="block rounded-2xl border border-gray-100 bg-gray-50/70 p-4 transition hover:border-gray-200 hover:bg-white dark:border-gray-700/70 dark:bg-gray-900/40 dark:hover:border-gray-600 dark:hover:bg-gray-900/70">
                                    <span class="mb-2 flex items-center justify-between gap-2">
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            {{ $field['label'] }}
                                            @if ($field['required'])
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </span>
                                        @unless ($field['required'])
                                            <span class="rounded-full bg-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400 ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-500 dark:ring-gray-700">Opsional</span>
                                        @endunless
                                    </span>
                                    <input
                                        id="{{ $field['id'] }}"
                                        type="time"
                                        name="{{ $field['name'] }}"
                                        @if ($field['required']) required @endif
                                        value="{{ $field['value'] }}"
                                        class="block min-h-11 w-full rounded-xl border-gray-200 bg-white text-sm font-medium tabular-nums text-gray-900 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white {{ $tone['focus'] }}">
                                    @if ($field['hint'])
                                        <span class="mt-2 block text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                    </section>
                @endforeach

                <div class="flex flex-col-reverse gap-3 rounded-3xl border border-gray-200/80 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                        Perubahan berlaku untuk perhitungan absensi setelah tombol simpan ditekan.
                    </p>
                    <button type="submit"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-red-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-900/10 transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
