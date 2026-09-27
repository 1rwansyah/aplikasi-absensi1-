<x-guest-layout>
    <div class="mb-7">
        <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-red-600 dark:bg-red-900/20 dark:text-red-400">
            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
            Selamat datang
        </span>
        <h2 class="mt-4 text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100">Masuk ke akun Anda</h2>
        <p class="mt-2 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
            Gunakan email dan kata sandi yang telah terdaftar.
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if ($errors->has('email'))
        <div role="alert" aria-live="polite"
            class="mb-5 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50/80 p-4 shadow-sm dark:border-red-900/60 dark:bg-red-950/25">
            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white text-red-600 shadow-sm ring-1 ring-red-100 dark:bg-red-950/40 dark:text-red-400 dark:ring-red-900/60">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v3.5m0 3.5h.01M10.3 4.4 2.6 18a2 2 0 001.75 3h15.3a2 2 0 001.75-3L13.7 4.4a2 2 0 00-3.4 0Z" />
                </svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-red-800 dark:text-red-200">Tidak dapat masuk</p>
                <p class="mt-1 text-xs leading-relaxed text-red-700/90 dark:text-red-300/90">
                    {{ $errors->first('email') }}
                </p>
                <p class="mt-1.5 text-[11px] text-red-600/70 dark:text-red-400/70">
                    Periksa kembali alamat email dan kata sandi Anda.
                </p>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email" value="Alamat Email" class="font-semibold" />
            <div class="relative mt-2">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2Z" />
                    </svg>
                </span>
                <x-text-input id="email"
                    class="block min-h-12 w-full rounded-xl border-gray-200 bg-gray-50/60 pl-11 text-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:focus:border-red-500 dark:focus:ring-red-500"
                    type="email" name="email" :value="old('email')" required autofocus autocomplete="username"
                    placeholder="nama@email.com" />
            </div>
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Kata Sandi" class="font-semibold" />

            <x-password-input id="password"
                            class="mt-2 block min-h-12 w-full rounded-xl border-gray-200 bg-gray-50/60 text-sm focus:border-red-500 focus:bg-white focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:focus:border-red-500 dark:focus:ring-red-500"
                            name="password"
                            required autocomplete="current-password"
                            placeholder="Masukkan kata sandi" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex cursor-pointer items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500 dark:border-gray-600 dark:bg-gray-900 dark:focus:ring-red-500 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="rounded-md text-sm font-semibold text-red-600 transition-colors hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:text-red-400 dark:hover:text-red-300 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            @endif
        </div>

        <button type="submit" class="group relative inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-md shadow-red-600/20 transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 active:scale-[0.99] dark:bg-red-700 dark:hover:bg-red-600 dark:focus:ring-offset-gray-800">
            Masuk
            <span class="absolute right-3 inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/10">
                <svg class="h-4 w-4 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                </svg>
            </span>
        </button>

        <div class="flex items-center justify-center gap-2 pt-1 text-xs text-gray-400 dark:text-gray-500">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 11V7a3 3 0 016 0v4m-8 0h10a2 2 0 012 2v6H5v-6a2 2 0 012-2Z" />
            </svg>
            Koneksi Anda dilindungi dan data tetap aman
        </div>
    </form>
</x-guest-layout>
