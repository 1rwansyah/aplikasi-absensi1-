@props(['employee'])

<div x-data="{
        open: false,
        top: 0,
        left: 0,
        openUpward: false,
        toggle(event) {
            const button = event.currentTarget;
            const rect = button.getBoundingClientRect();
            const menuHeight = 148;
            const gap = 4;
            const spaceBelow = window.innerHeight - rect.bottom;
            this.openUpward = spaceBelow < menuHeight + gap;
            this.top = this.openUpward
                ? Math.max(8, rect.top - menuHeight - gap)
                : rect.bottom + gap;
            this.left = Math.min(
                Math.max(8, rect.right - 192),
                window.innerWidth - 200
            );
            this.open = !this.open;
            if (this.open) {
                window.dispatchEvent(new CustomEvent('employee-action-open', {
                    detail: { id: {{ $employee->id }} },
                }));
            }
        },
        close() { this.open = false; },
    }"
    @employee-action-open.window="if ($event.detail.id !== {{ $employee->id }}) close()"
    @keydown.escape.window="close()"
    @scroll.window="close()"
    @resize.window="close()"
    class="relative inline-flex">
    <button
        type="button"
        x-ref="trigger"
        @click.stop="toggle($event)"
        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-transparent text-gray-500 transition hover:border-gray-200 hover:bg-gray-50 focus:outline-none md:h-9 md:w-9 dark:text-gray-400 dark:hover:border-gray-600 dark:hover:bg-gray-700">
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <circle cx="10" cy="4" r="1.5" />
            <circle cx="10" cy="10" r="1.5" />
            <circle cx="10" cy="16" r="1.5" />
        </svg>
    </button>

    <template x-teleport="body">
        <div x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.outside="if (!$refs.trigger || !$refs.trigger.contains($event.target)) close()"
            :style="`top:${top}px;left:${left}px`"
            class="fixed z-[400] w-48 overflow-hidden rounded-2xl border border-gray-200/80 bg-white shadow-xl shadow-gray-900/10 dark:border-gray-700 dark:bg-gray-800"
            style="display: none;">

            <button
                type="button"
                data-action="show-employee"
                data-employee-id="{{ $employee->id }}"
                @click="close()"
                class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/60">
                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>Lihat Detail</span>
            </button>

            <button
                type="button"
                data-action="edit-employee"
                data-employee-id="{{ $employee->id }}"
                @click="close()"
                class="flex w-full items-center gap-2.5 border-t border-gray-100 px-4 py-2.5 text-left text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/60">
                <svg class="h-4 w-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                <span>Edit</span>
            </button>

            <button
                type="button"
                data-action="delete-employee"
                data-employee-id="{{ $employee->id }}"
                data-employee-name="{{ $employee->name }}"
                @click="close()"
                class="flex w-full items-center gap-2.5 border-t border-gray-100 px-4 py-2.5 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50 dark:border-gray-700 dark:text-rose-400 dark:hover:bg-rose-950/30">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Hapus</span>
            </button>
        </div>
    </template>
</div>
