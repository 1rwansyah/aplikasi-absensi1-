<x-app-layout>
    <div id="employees-page" class="py-6">
        <div class="mx-auto space-y-5 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Header --}}
            <section class="relative overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-red-600 via-rose-500 to-amber-400"></div>

                <div class="grid gap-5 px-5 py-5 sm:px-6 sm:py-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-red-700 ring-1 ring-inset ring-red-100 dark:bg-red-950/40 dark:text-red-300 dark:ring-red-900/50">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Manajemen SDM
                        </div>
                        <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-3xl">Karyawan</h1>
                        <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                            Kelola data karyawan, status kepegawaian, dan komponen gaji dalam satu tempat.
                        </p>
                    </div>

                    <button type="button" data-action="create-employee"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-2xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-red-900/10 transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Tambah Karyawan
                    </button>
                </div>
            </section>

            {{-- Summary --}}
            <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
                <div class="relative overflow-hidden rounded-[1.5rem] border border-gray-200/80 bg-white px-4 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-slate-400 to-slate-600"></div>
                    <div class="flex items-start justify-between gap-2 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Total</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums tracking-tight text-gray-900 dark:text-white">{{ $summary['total'] }}</p>
                        </div>
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 dark:bg-slate-900 dark:text-slate-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-[1.5rem] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-white px-4 py-4 shadow-sm dark:border-emerald-900/40 dark:from-emerald-950/40 dark:via-gray-800 dark:to-gray-800">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-emerald-400 to-emerald-600"></div>
                    <div class="flex items-start justify-between gap-2 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-300">Aktif</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums tracking-tight text-emerald-800 dark:text-emerald-200">{{ $summary['active'] }}</p>
                        </div>
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-[1.5rem] border border-gray-200/80 bg-white px-4 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-gray-300 to-gray-500"></div>
                    <div class="flex items-start justify-between gap-2 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">Tidak aktif</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums tracking-tight text-gray-700 dark:text-gray-200">{{ $summary['inactive'] }}</p>
                        </div>
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gray-100 text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative overflow-hidden rounded-[1.5rem] border border-rose-100 bg-gradient-to-br from-rose-50 via-white to-white px-4 py-4 shadow-sm dark:border-rose-900/40 dark:from-rose-950/40 dark:via-gray-800 dark:to-gray-800">
                    <div class="absolute inset-y-0 left-0 w-1 bg-gradient-to-b from-rose-500 to-red-600"></div>
                    <div class="flex items-start justify-between gap-2 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-300">Resign</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums tracking-tight text-rose-800 dark:text-rose-200">{{ $summary['resigned'] }}</p>
                        </div>
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950/60 dark:text-rose-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                        </div>
                    </div>
                </div>

                <div class="relative col-span-2 overflow-hidden rounded-[1.5rem] border border-red-100 bg-gradient-to-br from-red-600 to-rose-600 px-4 py-4 shadow-sm md:col-span-1 dark:border-red-900/40">
                    <div class="absolute inset-y-0 left-0 w-1 bg-white/40"></div>
                    <div class="flex items-start justify-between gap-2 pl-2">
                        <div class="min-w-0">
                            <p class="text-[10px] font-semibold uppercase tracking-wider text-red-100/90">Total gaji gross</p>
                            <p class="mt-2 break-words text-lg font-bold tabular-nums tracking-tight text-white">
                                Rp {{ number_format($summary['total_gross_salary'], 0, ',', '.') }}
                            </p>
                        </div>
                        <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-white/15 text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Table Card --}}
            <section class="overflow-hidden rounded-[1.75rem] border border-gray-200/80 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Filter Toolbar --}}
                <div class="border-b border-gray-100 px-4 py-4 sm:px-5 dark:border-gray-700">
                    <form method="GET" action="{{ route('employees.index') }}"
                        class="flex w-full flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center" id="filter-form">

                        <x-ui.search-input name="search" value="{{ $search }}"
                            placeholder="Cari nama / kode / jabatan..." />

                        <x-ui.select name="status">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $s)
                                <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                            @endforeach
                        </x-ui.select>

                        <x-ui.select name="staff">
                            <option value="">Semua Staf</option>
                            @foreach ($staffs as $stf)
                                <option value="{{ $stf }}" @selected($staff === $stf)>{{ $stf }}</option>
                            @endforeach
                        </x-ui.select>

                        <button type="submit"
                            class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-2xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 sm:w-auto">
                            Filter
                        </button>

                        @if ($search || $status || $staff)
                            <a href="{{ route('employees.index') }}"
                                class="inline-flex min-h-11 w-full items-center justify-center gap-1.5 rounded-2xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-600 shadow-sm transition hover:bg-gray-50 sm:w-auto dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                Reset
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Mobile cards --}}
                <div class="space-y-3 p-4 md:hidden">
                    @forelse ($employees as $employee)
                        <article class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/40">
                            <div class="flex items-start gap-3">
                                <div class="h-12 w-12 flex-shrink-0">
                                    @if ($employee->profilePhotoUrl())
                                        <img src="{{ $employee->profilePhotoUrl() }}"
                                            alt="{{ $employee->name }}"
                                            class="h-12 w-12 rounded-2xl border border-white object-cover shadow-sm dark:border-gray-600">
                                    @else
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-red-500 to-rose-600 text-sm font-semibold text-white shadow-sm">
                                            {{ substr($employee->name, 0, 1) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-gray-900 dark:text-gray-100">{{ $employee->name }}</p>
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                {{ $employee->employee_code }} · {{ $employee->position ?? '-' }}
                                            </p>
                                        </div>
                                        <x-employees.action-dropdown :employee="$employee" />
                                    </div>
                                    <div class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <x-ui.badge :type="$employee->employment_status">{{ ucfirst($employee->employment_status) }}</x-ui.badge>
                                        <span class="text-sm font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($employee->gross_salary, 0, ',', '.') }}
                                        </span>
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        Masuk {{ $employee->join_date?->format('d M Y') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-200 px-4 py-12 text-center dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data karyawan.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Desktop table --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full border-collapse">
                        <thead class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-900/40">
                            <tr>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Foto</th>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Kode</th>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Karyawan</th>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Jabatan</th>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Tanggal masuk</th>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Status</th>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Gaji gross</th>
                                <th class="px-5 py-3.5 text-left text-[10px] font-bold uppercase tracking-wider text-gray-400">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/80">
                            @forelse ($employees as $employee)
                                <tr class="bg-white transition hover:bg-gray-50/80 dark:bg-gray-800 dark:hover:bg-gray-900/30">
                                    <td class="px-5 py-4">
                                        <div class="h-10 w-10 flex-shrink-0">
                                            @if ($employee->profilePhotoUrl())
                                                <img src="{{ $employee->profilePhotoUrl() }}"
                                                    alt="{{ $employee->name }}"
                                                    class="h-10 w-10 rounded-xl border border-gray-100 object-cover shadow-sm dark:border-gray-600">
                                            @else
                                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-red-500 to-rose-600 text-sm font-semibold text-white">
                                                    {{ substr($employee->name, 0, 1) }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="rounded-lg bg-gray-100 px-2 py-1 text-xs font-semibold tabular-nums text-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                            {{ $employee->employee_code }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $employee->name }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $employee->email ?? '-' }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $employee->position ?? '-' }}
                                    </td>
                                    <td class="px-5 py-4 text-sm tabular-nums text-gray-700 dark:text-gray-300">
                                        {{ $employee->join_date?->format('d M Y') ?? '-' }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <x-ui.badge :type="$employee->employment_status">{{ ucfirst($employee->employment_status) }}</x-ui.badge>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">
                                            Rp {{ number_format($employee->gross_salary, 0, ',', '.') }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <form id="delete-form-{{ $employee->id }}" method="POST"
                                            action="{{ route('employees.destroy', $employee) }}">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <x-employees.action-dropdown :employee="$employee" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-16 text-center">
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-3 text-sm font-medium text-gray-500 dark:text-gray-400">Belum ada data karyawan.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $employees->firstItem() ?? 0 }}–{{ $employees->lastItem() ?? 0 }}</span>
                        dari
                        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $employees->total() }}</span>
                        karyawan
                    </p>
                    <div>{{ $employees->links() }}</div>
                </div>
            </section>
        </div>
    </div>

    @push('modals')
        @include('employees.partials.create-modal')
        @include('employees.partials.show-modal')
        @include('employees.partials.edit-modal')
        @include('employees.partials.delete-modal')
    @endpush

    @push('scripts')
        <script src="{{ asset('face-api/face-api.min.js') }}"></script>
    @endpush
</x-app-layout>
