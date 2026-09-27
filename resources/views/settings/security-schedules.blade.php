<x-app-layout>
    @php
        $weekLabel = $days->first()->translatedFormat('d M Y').' – '.$days->last()->translatedFormat('d M Y');
        $employeeCount = $employees->count();
        $prevWeek = $weekStart->copy()->subWeek()->toDateString();
        $nextWeek = $weekStart->copy()->addWeek()->toDateString();
        $todayKey = now()->toDateString();

        $selectionFor = static function ($assignment): string {
            return match (true) {
                $assignment?->workSchedule?->code === 'off' => 'off',
                $assignment?->post_location === 'gate' => 'gate',
                $assignment?->post_location === 'lobby' => 'lobby',
                $assignment?->workSchedule?->code === 'security' => 'lobby',
                default => 'off',
            };
        };

        $counts = ['lobby' => 0, 'gate' => 0, 'off' => 0];
        foreach ($employees as $employee) {
            foreach ($days as $day) {
                $key = $employee->id.'_'.$day->format('Y-m-d');
                $counts[$selectionFor($assignments->get($key))]++;
            }
        }
    @endphp

    <div class="py-6">
        <div class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8"
            x-data="{
                tone(value) {
                    if (value === 'lobby') return 'bg-sky-50 text-sky-800 ring-sky-200 dark:bg-sky-950/40 dark:text-sky-200 dark:ring-sky-800';
                    if (value === 'gate') return 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-200 dark:ring-amber-800';
                    return 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700';
                }
            }">

            {{-- Header --}}
            <section
                class="relative overflow-hidden rounded-3xl border border-red-900/10 bg-gradient-to-br from-red-700 via-red-600 to-rose-600 px-5 py-6 text-white shadow-lg shadow-red-900/10 sm:px-7 sm:py-7">
                <div class="pointer-events-none absolute -right-16 -top-20 h-52 w-52 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-white/10 blur-3xl"></div>

                <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-50">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                            Pengaturan Sistem
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">Jadwal Security</h1>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-red-50/90">
                            Atur penugasan mingguan petugas security. Gunakan L (Lobby), PG (Pos Gate), atau OFF.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:min-w-[28rem]">
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-3.5 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Petugas</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums">{{ $employeeCount }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-3.5 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Lobby</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums">{{ $counts['lobby'] }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/15 bg-white/10 px-3.5 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">Pos Gate</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums">{{ $counts['gate'] }}</p>
                        </div>
                        <div class="col-span-2 rounded-2xl border border-white/15 bg-white/10 px-3.5 py-3 backdrop-blur-sm sm:col-span-1">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100">OFF</p>
                            <p class="mt-0.5 text-xl font-bold tabular-nums">{{ $counts['off'] }}</p>
                        </div>
                    </div>
                </div>
            </section>

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-950/40 dark:text-red-200">
                    <p class="font-semibold">Terjadi kesalahan:</p>
                    <ul class="mt-2 list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Week navigator + import --}}
            <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="grid gap-0 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)]">
                    <div class="border-b border-gray-100 p-5 dark:border-gray-700 lg:border-b-0 lg:border-r sm:p-6">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Minggu aktif</p>
                                <h2 class="mt-1 text-lg font-bold tracking-tight text-gray-900 dark:text-white">{{ $weekLabel }}</h2>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('settings.security-schedules.index', ['week_start' => $prevWeek]) }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-600 dark:bg-gray-900 dark:hover:border-red-800 dark:hover:bg-red-950/30 dark:hover:text-red-300"
                                    aria-label="Minggu sebelumnya">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </a>
                                <a href="{{ route('settings.security-schedules.index', ['week_start' => $nextWeek]) }}"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-600 dark:border-gray-600 dark:bg-gray-900 dark:hover:border-red-800 dark:hover:bg-red-950/30 dark:hover:text-red-300"
                                    aria-label="Minggu berikutnya">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('settings.security-schedules.index') }}" class="mt-4">
                            <label for="week_start" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                Lompat ke tanggal
                            </label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <input id="week_start" type="date" name="week_start" value="{{ $weekStart->toDateString() }}"
                                    onchange="this.form.submit()"
                                    class="block min-h-11 w-full rounded-2xl border-gray-200 bg-gray-50 pl-11 pr-3 text-sm text-gray-800 shadow-sm transition focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-white">
                            </div>
                        </form>

                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="rounded-xl bg-sky-50 px-3 py-2 text-center ring-1 ring-sky-100 dark:bg-sky-950/30 dark:ring-sky-900/50">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-sky-700 dark:text-sky-300">L</p>
                                <p class="text-[11px] font-medium text-sky-800/80 dark:text-sky-200/80">Lobby</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 px-3 py-2 text-center ring-1 ring-amber-100 dark:bg-amber-950/30 dark:ring-amber-900/50">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-amber-700 dark:text-amber-300">PG</p>
                                <p class="text-[11px] font-medium text-amber-800/80 dark:text-amber-200/80">Pos Gate</p>
                            </div>
                            <div class="rounded-xl bg-slate-100 px-3 py-2 text-center ring-1 ring-slate-200 dark:bg-slate-800 dark:ring-slate-700">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:text-slate-300">OFF</p>
                                <p class="text-[11px] font-medium text-slate-600/80 dark:text-slate-400">Libur</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-gray-900 text-white dark:bg-white dark:text-gray-900">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Import Excel</h2>
                                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">Unggah file untuk mengisi jadwal minggu ini sekaligus.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('settings.security-schedules.import') }}"
                            enctype="multipart/form-data"
                            class="mt-4 rounded-2xl border border-dashed border-gray-200 bg-gray-50/80 p-4 dark:border-gray-600 dark:bg-gray-900/40">
                            @csrf
                            <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">

                            <label for="schedule-file" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                File Excel
                            </label>
                            <input id="schedule-file" type="file" name="file" accept=".xlsx,.xls,.csv" required
                                class="block w-full rounded-2xl border border-gray-200 bg-white text-sm text-gray-700 shadow-sm file:mr-4 file:rounded-xl file:border-0 file:bg-red-600 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-white hover:file:bg-red-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:file:bg-red-700">
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-xs text-gray-500 dark:text-gray-400">Format: .xlsx, .xls, atau .csv</p>
                                <button type="submit"
                                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100">
                                    Import Excel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Assignment table --}}
            <form method="POST" action="{{ route('settings.security-schedules.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">

                <div class="overflow-hidden rounded-3xl border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex flex-col gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div>
                            <h2 class="text-base font-bold tracking-tight text-gray-900 dark:text-white">Penugasan Mingguan</h2>
                            <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                                Ubah jadwal per hari lalu simpan. Nama petugas tetap terlihat saat digeser.
                            </p>
                        </div>
                        <span class="inline-flex w-fit items-center rounded-full bg-gray-100 px-3 py-1 text-[11px] font-semibold text-gray-600 ring-1 ring-inset ring-gray-200 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700">
                            {{ $employeeCount }} petugas · 7 hari
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-900/60 dark:to-gray-800">
                                <tr>
                                    <th scope="col"
                                        class="sticky left-0 z-20 min-w-[220px] border-r border-gray-200 bg-gray-50 px-4 py-3.5 text-left text-[11px] font-bold uppercase tracking-[0.12em] text-gray-500 dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-400">
                                        Nama Petugas
                                    </th>

                                    @foreach ($days as $day)
                                        @php $isToday = $day->toDateString() === $todayKey; @endphp
                                        <th scope="col" class="min-w-[108px] px-2 py-3.5 text-center">
                                            <div @class([
                                                'mx-auto inline-flex min-w-[4.75rem] flex-col items-center rounded-xl px-2.5 py-1.5 shadow-sm ring-1',
                                                'bg-red-600 text-white ring-red-500' => $isToday,
                                                'bg-white text-gray-700 ring-gray-100 dark:bg-gray-900 dark:text-gray-200 dark:ring-gray-700' => ! $isToday,
                                            ])>
                                                <span @class([
                                                    'text-[10px] font-bold uppercase tracking-wide',
                                                    'text-red-100' => $isToday,
                                                    'text-red-600 dark:text-red-300' => ! $isToday,
                                                ])>
                                                    {{ $day->translatedFormat('D') }}
                                                </span>
                                                <span class="mt-0.5 text-xs font-semibold tabular-nums">
                                                    {{ $day->format('d/m') }}
                                                </span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-800">
                                @forelse ($employees as $employee)
                                    @php $photoUrl = $employee->profilePhotoUrl(); @endphp
                                    <tr class="transition hover:bg-red-50/30 dark:hover:bg-red-950/10">
                                        <td class="sticky left-0 z-10 border-r border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-center gap-3">
                                                @if ($photoUrl)
                                                    <img src="{{ $photoUrl }}" alt="{{ $employee->name }}"
                                                        class="h-9 w-9 flex-shrink-0 rounded-xl object-cover ring-1 ring-gray-200 dark:ring-gray-600">
                                                @else
                                                    <span class="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-rose-600 text-xs font-bold text-white shadow-sm">
                                                        {{ strtoupper(substr($employee->name, 0, 1)) }}
                                                    </span>
                                                @endif
                                                <span class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $employee->name }}</span>
                                            </div>
                                        </td>

                                        @foreach ($days as $day)
                                            @php
                                                $selected = $selectionFor(
                                                    $assignments->get($employee->id.'_'.$day->format('Y-m-d'))
                                                );
                                            @endphp
                                            <td class="px-2 py-2.5 text-center">
                                                <select
                                                    name="schedules[{{ $employee->id }}][{{ $day->format('Y-m-d') }}]"
                                                    x-data="{ value: '{{ $selected }}' }"
                                                    x-model="value"
                                                    :class="tone(value)"
                                                    class="w-full cursor-pointer appearance-none rounded-xl px-2 py-2 text-center text-xs font-bold tracking-wide ring-1 ring-inset transition focus:outline-none focus:ring-2 focus:ring-red-500">
                                                    <option value="lobby">L</option>
                                                    <option value="gate">PG</option>
                                                    <option value="off">OFF</option>
                                                </select>
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $days->count() + 1 }}" class="px-6 py-14 text-center">
                                            <div class="mx-auto flex max-w-sm flex-col items-center">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                </div>
                                                <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-200">Belum ada karyawan Security</p>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tambahkan karyawan dengan staff Security terlebih dahulu.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($employeeCount > 0)
                    <div class="sticky bottom-4 z-30">
                        <div class="flex flex-col-reverse gap-3 rounded-2xl border border-gray-200/80 bg-white/95 px-4 py-3 shadow-lg shadow-gray-900/5 backdrop-blur-xl dark:border-gray-700 dark:bg-gray-900/95 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Perubahan baru tersimpan setelah tombol <span class="font-semibold text-gray-700 dark:text-gray-300">Simpan Jadwal</span> ditekan.
                            </p>
                            <button type="submit"
                                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-red-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-900/10 transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Simpan Jadwal
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>
