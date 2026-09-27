<x-app-layout>
    <x-attendance.employee-page page-id="employee-profile-page">
        <div class="space-y-6">
            @php
                $statusClass = match ($employee?->employment_status) {
                    'active' => 'bg-white/15 text-white ring-white/20',
                    'inactive' => 'bg-gray-900/20 text-white ring-white/20',
                    'resigned' => 'bg-red-950/30 text-red-50 ring-white/20',
                    default => 'bg-white/15 text-white ring-white/20',
                };
                $statusLabel = match ($employee?->employment_status) {
                    'active' => 'Aktif',
                    'inactive' => 'Nonaktif',
                    'resigned' => 'Resign',
                    default => $employee?->employment_status ?? 'Akun Aktif',
                };
            @endphp

            {{-- Page Header --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-400 dark:text-gray-500">Akun Karyawan</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Profil Saya</h1>
                    <p class="mt-1 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        Lihat informasi pribadi, data kepegawaian, dan keamanan akun Anda.
                    </p>
                </div>
                <a href="{{ $user->homeUrl() }}"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-gray-300 hover:bg-gray-50 sm:w-auto dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0 7-7m-7 7h18" />
                    </svg>
                    Kembali ke Beranda
                </a>
            </div>

            {{-- ── Employee Detail Card ──────────────────────────────── --}}
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Profile Header --}}
                <div
                    class="relative overflow-hidden border-b border-red-700 bg-gradient-to-br from-red-600 via-red-700 to-red-900 px-5 py-7 text-white sm:px-7 sm:py-8 dark:border-gray-700 dark:from-gray-800 dark:via-gray-800 dark:to-gray-900">
                    <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[34px] border-white/[0.06]"></div>
                    <div class="pointer-events-none absolute -bottom-20 right-1/3 h-40 w-40 rounded-full bg-white/[0.04]"></div>

                    <div class="relative flex flex-col items-start gap-5 sm:flex-row sm:items-center sm:gap-6">
                        {{-- Photo --}}
                        <div class="flex-shrink-0">
                            @if ($employee && $employee->hasProfilePhoto())
                                <img src="{{ $employee->profilePhotoUrl() }}" alt="{{ $employee->name }}"
                                    class="h-24 w-24 rounded-3xl object-cover shadow-xl ring-4 ring-white/20 sm:h-28 sm:w-28">
                            @else
                                <div
                                    class="flex h-24 w-24 items-center justify-center rounded-3xl border border-white/20 bg-white/10 shadow-xl backdrop-blur-sm sm:h-28 sm:w-28">
                                    <svg class="h-11 w-11 text-white/70" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Name & badges --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="truncate text-2xl font-bold tracking-tight text-white sm:text-3xl">
                                    {{ $employee->name ?? $user->name }}
                                </h3>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $statusClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                                        {{ $statusLabel }}
                                </span>
                                @if ($user->hasAnyRole(['hr', 'admin']))
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold text-white ring-1 ring-inset ring-white/20">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                        Admin Panel
                                    </span>
                                @endif
                            </div>
                            @if ($employee)
                                <p class="mt-3 flex items-center gap-1.5 text-sm font-medium text-red-100 dark:text-gray-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3 3 0 00-3 3m3-3a3 3 0 013-3m-3 3h.01" />
                                    </svg>
                                    {{ $employee->employee_code }}
                                </p>
                                <p class="mt-1.5 text-sm text-red-100/90 dark:text-gray-400">
                                    {{ $employee->position ?: 'Jabatan belum tersedia' }}
                                    @if ($employee->staff)
                                        <span class="mx-1 text-red-200/60">·</span>{{ $employee->staff }}
                                    @endif
                                </p>
                            @else
                                <p class="mt-2 text-sm text-red-100 dark:text-gray-400">{{ $user->email }}</p>
                            @endif
                        </div>

                        <div class="grid w-full grid-cols-1 gap-2 sm:w-auto sm:min-w-64">
                            <div class="rounded-xl border border-white/10 bg-black/10 px-4 py-3 backdrop-blur-sm">
                                <p class="text-[11px] font-medium uppercase tracking-wider text-red-200 dark:text-gray-400">Email</p>
                                <p class="mt-1 truncate text-sm font-semibold text-white">{{ $user->email }}</p>
                            </div>
                            @if ($employee?->join_date)
                                <div class="rounded-xl border border-white/10 bg-black/10 px-4 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-medium uppercase tracking-wider text-red-200 dark:text-gray-400">Bergabung sejak</p>
                                    <p class="mt-1 text-sm font-semibold text-white">{{ $employee->join_date->translatedFormat('d F Y') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($employee)
                    <div class="grid gap-4 bg-gray-50/70 p-4 sm:p-6 md:grid-cols-2 dark:bg-gray-900/20">

                        {{-- Informasi Pribadi --}}
                        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h4
                                class="flex items-center gap-2.5 text-sm font-bold text-gray-800 dark:text-gray-100">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                                Informasi Pribadi
                            </h4>
                            <div
                                class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ([
        'Email' => $employee->email,
        'Pendidikan' => $employee->education,
        'Pengalaman Kerja' => $employee->work_experience,
        'NIK' => $employee->nik,
        'Tempat Lahir' => $employee->birth_place,
        'Tanggal Lahir' => $employee->birth_date?->translatedFormat('d F Y'),
        'Alamat' => $employee->address,
    ] as $label => $value)
                                    @if ($value)
                                        <div class="grid grid-cols-[6rem_1fr] gap-3 py-2.5 text-sm first:pt-0 last:pb-0 sm:grid-cols-[120px_1fr]">
                                            <span class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ $label }}</span>
                                            <span
                                                class="font-medium text-gray-900 dark:text-gray-100 break-words">{{ $value }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>

                        {{-- Informasi Kepegawaian --}}
                        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <h4
                                class="flex items-center gap-2.5 text-sm font-bold text-gray-800 dark:text-gray-100">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600 dark:bg-violet-900/20 dark:text-violet-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </span>
                                Informasi Kepegawaian
                            </h4>
                            <div
                                class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach ([
        'Kode Karyawan' => $employee->employee_code,
        'Jabatan' => $employee->position,
        'Staff' => $employee->staff,
        'Tanggal Masuk' => $employee->join_date?->translatedFormat('d F Y'),
        'Status' => $statusLabel ?? '-',
    ] as $label => $value)
                                    @if ($value)
                                        <div class="grid grid-cols-[6rem_1fr] gap-3 py-2.5 text-sm first:pt-0 last:pb-0 sm:grid-cols-[120px_1fr]">
                                            <span class="text-xs font-medium text-gray-400 dark:text-gray-500">{{ $label }}</span>
                                            <span
                                                class="font-medium text-gray-900 dark:text-gray-100">{{ $value }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </section>

                        {{-- Informasi Bank --}}
                        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm md:col-span-2 dark:border-gray-700 dark:bg-gray-800">
                            <h4
                                class="flex items-center gap-2.5 text-sm font-bold text-gray-800 dark:text-gray-100">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </span>
                                Informasi Bank
                            </h4>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                    @foreach ([
        'Nama Bank' => $employee->bank_name,
        'No. Rekening' => $employee->bank_account_number,
        'Atas Nama' => $employee->bank_account_name,
    ] as $label => $value)
                                        <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/30">
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                            <p class="mt-1 break-words text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $value ?: '-' }}</p>
                                        </div>
                                    @endforeach
                            </div>
                        </section>

                    </div>
                @else
                    {{-- Non-employee user (admin only) --}}
                    <div class="p-6">
                        <div
                            class="rounded-xl border border-gray-100 bg-gray-50/60 p-4 dark:border-gray-700 dark:bg-gray-700/30 space-y-3">
                            @foreach (['Nama' => $user->name, 'Email' => $user->email] as $label => $value)
                                <div class="grid grid-cols-[4.5rem_1fr] gap-2 text-sm sm:grid-cols-[80px_1fr]">
                                    <span class="text-gray-500 dark:text-gray-400">{{ $label }}</span>
                                    <span
                                        class="font-medium text-gray-900 dark:text-gray-100">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- ── Ubah Password Card ────────────────────────────────── --}}
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/60 px-5 py-4 sm:px-6 dark:border-gray-700 dark:bg-gray-900/20">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 11V7a4 4 0 118 0v4m-2 0H6a2 2 0 00-2 2v6a2 2 0 002 2h12a2 2 0 002-2v-6a2 2 0 00-2-2Z" />
                        </svg>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">Keamanan Akun</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Perbarui password akun Anda secara berkala.</p>
                    </div>
                </div>
                <div class="p-5 sm:p-6">
                    <form method="POST" action="{{ route('password.update') }}" class="max-w-xl space-y-5">
                        @csrf
                        @method('put')

                        <div>
                            <label for="current_password"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Password Saat
                                Ini</label>
                            <x-password-input id="current_password" name="current_password"
                                autocomplete="current-password"
                                class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                            @if ($errors->updatePassword->get('current_password'))
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                    {{ $errors->updatePassword->first('current_password') }}</p>
                            @endif
                        </div>

                        <div>
                            <label for="password"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Password
                                Baru</label>
                            <x-password-input id="password" name="password" autocomplete="new-password"
                                class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                            @if ($errors->updatePassword->get('password'))
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                    {{ $errors->updatePassword->first('password') }}</p>
                            @endif
                        </div>

                        <div>
                            <label for="password_confirmation"
                                class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Konfirmasi
                                Password Baru</label>
                            <x-password-input id="password_confirmation" name="password_confirmation"
                                autocomplete="new-password"
                                class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                            @if ($errors->updatePassword->get('password_confirmation'))
                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                    {{ $errors->updatePassword->first('password_confirmation') }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-4">
                            <button type="submit"
                                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:bg-red-700 dark:hover:bg-red-600">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                Simpan Password
                            </button>
                            @if (session('status') === 'password-updated')
                                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                                    class="text-sm text-green-600 dark:text-green-400">
                                    Password berhasil diperbarui.
                                </p>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </x-attendance.employee-page>
</x-app-layout>
