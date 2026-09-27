<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>SADAR-PRI - Login</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="{{ asset('images/logo/logo_SADAR.png') }}">

        <!-- Fonts -->
        {{-- <link rel="preconnect" href="https://fonts.bunny.net"> --}}
        {{-- <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" /> --}}

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Authentication pages always use light mode. -->
        <script>
            document.documentElement.classList.remove('dark');
        </script>
    </head>
    <body class="bg-white font-sans text-gray-900 antialiased transition-colors duration-300 dark:bg-gray-900">
        <main class="relative min-h-screen overflow-hidden lg:grid lg:grid-cols-[1.05fr_0.95fr]">
            {{-- Brand panel --}}
            <section class="relative hidden overflow-hidden bg-gradient-to-br from-red-600 via-red-700 to-red-950 p-12 text-white lg:flex lg:flex-col lg:justify-between xl:p-16">
                <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.1]" aria-hidden="true">
                    <defs>
                        <pattern id="login-batik-pattern" width="84" height="84" patternUnits="userSpaceOnUse">
                            <g fill="none" stroke="currentColor" stroke-width="1.25">
                                <circle cx="42" cy="42" r="18" />
                                <path d="M42 15c5 10 11 17 27 27-16 10-22 17-27 27-5-10-11-17-27-27 16-10 22-17 27-27Z" />
                                <circle cx="42" cy="42" r="5" />
                                <path d="M0 0l17 17M84 0 67 17M0 84l17-17M84 84 67 67" />
                            </g>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#login-batik-pattern)" />
                </svg>
                <div class="pointer-events-none absolute -right-32 -top-32 h-96 w-96 rounded-full border-[52px] border-white/5"></div>
                <div class="pointer-events-none absolute -bottom-28 -left-28 h-80 w-80 rounded-full bg-black/10 blur-3xl"></div>

                <a href="/" class="relative inline-flex w-fit items-center gap-4">
                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-white/20 bg-white/10 p-1.5 shadow-xl backdrop-blur-sm">
                        <img src="{{ asset('images/logo/logo_SADAR.png') }}" alt="Logo SADAR-PRI" class="h-full w-full object-contain">
                    </span>
                    <span>
                        <span class="block text-xl font-extrabold uppercase tracking-[0.18em]">SADAR-PRI</span>
                        <span class="mt-1 block text-xs font-medium uppercase tracking-[0.16em] text-red-200">Employee System</span>
                    </span>
                </a>

                <div class="relative max-w-xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-red-50 backdrop-blur-sm">
                        <span class="h-2 w-2 rounded-full bg-amber-300"></span>
                        Sistem Absensi Digital
                    </span>
                    <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                        Kelola kehadiran dengan mudah dan terpercaya.
                    </h1>
                    <p class="mt-5 max-w-lg text-base leading-relaxed text-red-100/90">
                        Akses absensi, pengajuan izin, riwayat kehadiran, dan slip gaji Anda dalam satu portal.
                    </p>

                    <div class="mt-8 grid max-w-lg grid-cols-3 gap-3">
                        @foreach (['Aman', 'Cepat', 'Terintegrasi'] as $feature)
                            <div class="rounded-2xl border border-white/10 bg-black/10 px-4 py-3 backdrop-blur-sm">
                                <svg class="h-4 w-4 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                <p class="mt-2 text-sm font-semibold">{{ $feature }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="relative text-xs text-red-200/80">
                    &copy; {{ date('Y') }} SADAR-PRI. Seluruh hak cipta dilindungi.
                </p>
            </section>

            {{-- Authentication panel --}}
            <section class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-gray-50 via-white to-red-50/70 px-5 py-8 dark:from-gray-900 dark:via-gray-900 dark:to-red-950/20 sm:px-8 lg:px-12">
                {{-- Pola dekoratif ringan tanpa file gambar tambahan. --}}
                <svg class="pointer-events-none absolute inset-0 h-full w-full text-red-200 opacity-30 dark:text-red-900 dark:opacity-20" aria-hidden="true">
                    <defs>
                        <pattern id="login-panel-pattern" width="52" height="52" patternUnits="userSpaceOnUse">
                            <circle cx="4" cy="4" r="1.25" fill="currentColor" />
                            <path d="M38 18l6 6-6 6-6-6 6-6Z" fill="none" stroke="currentColor" stroke-width="0.8" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#login-panel-pattern)" />
                </svg>
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-red-200/50 blur-3xl dark:bg-red-900/10"></div>
                <div class="pointer-events-none absolute -bottom-32 left-10 h-72 w-72 rounded-full bg-amber-100/50 blur-3xl dark:bg-amber-900/5"></div>
                <div class="pointer-events-none absolute right-[8%] top-[12%] h-16 w-16 rotate-12 rounded-2xl border border-red-200/60 bg-white/30 backdrop-blur-sm dark:border-red-900/20 dark:bg-white/[0.02]"></div>
                <div class="pointer-events-none absolute bottom-[12%] left-[8%] h-10 w-10 -rotate-12 rounded-xl border border-red-200/50 dark:border-red-900/20"></div>

                <div class="relative w-full max-w-md">
                    {{-- Mobile branding --}}
                    <a href="/" class="mx-auto mb-7 flex w-fit items-center justify-center gap-3 rounded-2xl border border-white/80 bg-white/70 px-4 py-2.5 shadow-sm backdrop-blur-sm lg:hidden dark:border-gray-700 dark:bg-gray-800/70">
                        <img src="{{ asset('images/logo/logo_SADAR.png') }}" alt="Logo SADAR-PRI" class="h-14 w-14 object-contain">
                        <span>
                            <span class="block text-lg font-extrabold uppercase tracking-[0.14em] text-red-700 dark:text-red-400">SADAR-PRI</span>
                            <span class="block text-[10px] font-medium uppercase tracking-[0.13em] text-gray-400">Employee System</span>
                        </span>
                    </a>

                    <div class="relative overflow-hidden rounded-3xl border border-white bg-white/95 shadow-2xl shadow-red-950/10 backdrop-blur-sm dark:border-gray-700 dark:bg-gray-800/95 dark:shadow-black/20">
                        <div class="h-1.5 w-full bg-gradient-to-r from-red-500 via-red-600 to-red-800"></div>
                        <div class="pointer-events-none absolute -right-10 top-5 h-24 w-24 rounded-full border-[16px] border-red-50 dark:border-red-900/10"></div>
                        <div class="px-6 py-7 sm:px-8 sm:py-8">
                            {{ $slot }}
                        </div>
                    </div>

                    <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500 lg:hidden">
                        &copy; {{ date('Y') }} SADAR-PRI. Seluruh hak cipta dilindungi.
                    </p>
                </div>
            </section>
        </main>
    </body>
</html>
