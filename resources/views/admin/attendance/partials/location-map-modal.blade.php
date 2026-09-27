{{-- rules: Tailwind only — modal peta lokasi absensi (Leaflet) --}}
@if (isset($geofence))
    <script type="application/json" id="admin-attendance-geofence-config">@json($geofence)</script>
@endif

<div id="admin-attendance-location-map-modal"
    class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="admin-attendance-location-map-title">

    <div class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <div class="min-w-0 pr-4">
                <h3 id="admin-attendance-location-map-title" class="truncate text-lg font-bold text-gray-900 dark:text-gray-100">
                    Lokasi Absensi
                </h3>
                <p id="admin-attendance-location-map-subtitle" class="mt-0.5 truncate text-sm text-gray-500 dark:text-gray-400"></p>
            </div>
            <button type="button" data-admin-location-map-close
                class="shrink-0 rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                <span class="sr-only">Tutup peta</span>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="border-b border-gray-100 px-5 py-3 dark:border-gray-700">
            <div id="admin-attendance-location-map-legend" class="flex flex-wrap gap-2 text-xs"></div>
        </div>

        <div class="relative min-h-[280px] flex-1 bg-gray-100 dark:bg-gray-900 sm:min-h-[360px]">
            <div id="admin-attendance-location-map-canvas" class="h-[280px] w-full sm:h-[360px]"></div>
            <div id="admin-attendance-location-map-loading"
                class="absolute inset-0 flex items-center justify-center bg-white/80 text-sm font-medium text-gray-600 dark:bg-gray-800/80 dark:text-gray-300">
                Memuat peta...
            </div>
        </div>

        <div id="admin-attendance-location-map-footer"
            class="max-h-32 overflow-y-auto border-t border-gray-100 px-5 py-3 text-xs text-gray-600 dark:border-gray-700 dark:text-gray-400">
        </div>
    </div>
</div>
