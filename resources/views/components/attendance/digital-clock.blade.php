@props(['settings'])

@php
    $hour = (int) \App\Support\AppTime::now()->format('H');
    $greeting = match (true) {
        $hour < 11 => 'Selamat Pagi',
        $hour < 15 => 'Selamat Siang',
        $hour < 18 => 'Selamat Sore',
        default => 'Selamat Malam',
    };
@endphp

<div data-server-time-url="{{ route('attendance.server-time') }}"
    {{ $attributes->merge(['class' => 'relative w-full overflow-hidden rounded-3xl border border-red-500/20 bg-gradient-to-br from-red-600 via-red-700 to-red-900 shadow-lg shadow-red-900/10 dark:border-gray-700 dark:from-gray-800 dark:via-gray-800 dark:to-gray-900']) }}>
    {{-- Motif geometris terinspirasi batik, dibuat ringan agar teks tetap jelas. --}}
    <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-[0.12]" aria-hidden="true">
        <defs>
            <pattern id="attendance-batik-pattern" width="72" height="72" patternUnits="userSpaceOnUse">
                <g fill="none" stroke="currentColor" stroke-width="1.25">
                    <circle cx="36" cy="36" r="16" />
                    <path d="M36 13c4 8 9 14 18 23-9 9-14 15-18 23-4-8-9-14-18-23 9-9 14-15 18-23Z" />
                    <circle cx="36" cy="36" r="4" />
                    <path d="M0 0l14 14M72 0 58 14M0 72l14-14M72 72 58 58" />
                </g>
            </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#attendance-batik-pattern)" />
    </svg>

    <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full border-[32px] border-white/5"></div>
    <div class="pointer-events-none absolute -bottom-20 left-1/3 h-40 w-40 rounded-full bg-black/5 blur-2xl"></div>

    <div class="relative grid gap-6 px-5 py-6 text-white sm:px-8 sm:py-8 md:grid-cols-[1fr_auto] md:items-center lg:px-10">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-red-50 backdrop-blur-sm dark:text-gray-200">
                <span class="h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_0_4px_rgba(253,224,71,0.12)]"></span>
                Portal Kehadiran Karyawan
            </div>
            <p class="mt-5 text-sm font-medium text-red-100 dark:text-gray-300">{{ $greeting }},</p>
            <p class="mt-1 truncate text-2xl font-bold tracking-tight sm:text-3xl">{{ auth()->user()->name }}</p>
            <p class="mt-2 max-w-lg text-sm leading-relaxed text-red-100/90 dark:text-gray-400">
                Semoga aktivitas Anda hari ini berjalan lancar dan produktif.
            </p>
        </div>

        <div class="rounded-2xl border border-white/15 bg-black/10 p-4 backdrop-blur-sm sm:p-5 md:min-w-72 md:text-right">
            <p id="digital-date" class="text-xs font-semibold capitalize tracking-wide text-red-100 sm:text-sm dark:text-gray-300">
                {{ \App\Support\AppTime::now()->translatedFormat('l, d F Y') }}
            </p>
            <p id="digital-clock" class="mt-2 whitespace-nowrap font-mono text-4xl font-bold tracking-tight tabular-nums sm:text-5xl dark:text-white">
                {{ \App\Support\AppTime::now()->format('H:i:s') }}
            </p>
            <p class="mt-2 text-[11px] font-medium uppercase tracking-[0.16em] text-red-200 dark:text-gray-400">
                Waktu Indonesia Barat
            </p>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (function() {
                let serverTimeOffset = 0;
                let clockElement = document.getElementById('digital-clock');
                let dateElement = document.getElementById('digital-date');
                let clockCard = document.querySelector('[data-server-time-url]');

                async function syncWithServer() {
                    try {
                        const response = await fetch(clockCard.dataset.serverTimeUrl);
                        const data = await response.json();
                        const serverTimestamp = data.timestamp * 1000;
                        const localTimestamp = Date.now();
                        serverTimeOffset = serverTimestamp - localTimestamp;
                        updateClock();
                    } catch (error) {
                        console.error('Failed to sync with server time:', error);
                    }
                }

                function updateClock() {
                    const now = new Date(Date.now() + serverTimeOffset);

                    // Gunakan Intl.DateTimeFormat dengan timezone Asia/Jakarta untuk jam
                    const timeFormatter = new Intl.DateTimeFormat('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                        timeZone: 'Asia/Jakarta'
                    });
                    const timeString = timeFormatter.format(now);

                    if (clockElement) {
                        clockElement.textContent = timeString;
                    }

                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Jakarta' };
                    const dateString = now.toLocaleDateString('id-ID', options);
                    if (dateElement) {
                        dateElement.textContent = dateString;
                    }
                }

                syncWithServer();
                setInterval(updateClock, 1000);
            })();
        </script>
    @endpush
@endonce
