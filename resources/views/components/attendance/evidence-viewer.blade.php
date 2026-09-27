@props([
    'showOpenInNewTab' => true,
])

<div x-cloak x-show="isOpen"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="closeEvidence()"
    @click.self="closeEvidence()"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-950/85 p-3 backdrop-blur-sm sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="evidence-modal-title">
    <div class="flex h-[92dvh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-white/10 bg-gray-900 shadow-2xl"
        @click.stop>
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/10 px-4 py-3 sm:px-5">
            <div class="min-w-0">
                <h2 id="evidence-modal-title" class="truncate text-sm font-semibold text-white sm:text-base"
                    x-text="title"></h2>
                <p class="mt-0.5 text-xs text-gray-400"
                    x-text="isImage ? 'Scroll untuk zoom · Geser gambar untuk melihat area lain' : 'Preview dokumen PDF'"></p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                @if ($showOpenInNewTab)
                    <a :href="url" target="_blank" rel="noopener noreferrer"
                        class="hidden min-h-9 items-center rounded-lg border border-white/15 px-3 text-xs font-medium text-gray-200 transition hover:bg-white/10 sm:inline-flex">
                        Buka di tab baru
                    </a>
                @endif
                <button type="button" @click="closeEvidence()"
                    class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-300 transition hover:bg-white/10 hover:text-white"
                    aria-label="Tutup preview">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="relative min-h-0 flex-1 overflow-hidden bg-black/40">
            <div x-show="isImage"
                x-ref="imageViewport"
                @wheel.prevent="handleWheel($event)"
                @pointerdown="startPan($event)"
                @pointermove="movePan($event)"
                @pointerup="endPan($event)"
                @pointercancel="endPan($event)"
                class="flex h-full w-full select-none items-center justify-center overflow-hidden touch-none"
                :class="scale > 1 ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'">
                <img x-ref="evidenceImage" :src="url" :alt="title"
                    draggable="false"
                    @load="resetZoom()"
                    @dblclick.prevent="toggleZoomAt($event.clientX, $event.clientY)"
                    class="max-h-full max-w-full select-none object-contain will-change-transform"
                    :style="imageTransform">
            </div>

            <div x-show="!isImage" class="h-full w-full bg-white">
                <iframe :src="url" :title="title" class="h-full w-full border-0"></iframe>
            </div>

            <div x-show="isImage"
                class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-1 rounded-xl border border-white/10 bg-gray-950/80 p-1.5 text-white shadow-xl backdrop-blur">
                <button type="button" @click="zoomFromCenter(-zoomStep)"
                    class="flex h-9 w-9 items-center justify-center rounded-lg transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="scale <= minScale" aria-label="Perkecil gambar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                </button>
                <button type="button" @click="resetZoom()"
                    class="min-w-14 rounded-lg px-2 py-2 text-xs font-semibold tabular-nums transition hover:bg-white/10"
                    x-text="Math.round(scale * 100) + '%'"></button>
                <button type="button" @click="zoomFromCenter(zoomStep)"
                    class="flex h-9 w-9 items-center justify-center rounded-lg transition hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="scale >= maxScale" aria-label="Perbesar gambar">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex shrink-0 items-center justify-between gap-3 border-t border-white/10 px-4 py-3 sm:hidden">
            <p class="text-[11px] text-gray-400"
                x-text="isImage ? 'Ketuk 2× untuk memperbesar/perkecil' : 'Dokumen ditampilkan langsung di dalam preview.'"></p>
            @if ($showOpenInNewTab)
                <a :href="url" target="_blank" rel="noopener noreferrer"
                    class="shrink-0 rounded-lg border border-white/15 px-3 py-2 text-xs font-medium text-gray-200">
                    Buka di tab baru
                </a>
            @endif
        </div>
    </div>
</div>
