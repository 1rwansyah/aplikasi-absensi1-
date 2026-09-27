<x-app-layout>
    <div class="py-6">
        <div class="mx-auto space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Page Header --}}
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Log Aktivitas</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Catatan aktivitas pengguna dalam sistem.</p>
            </div>

            {{-- Table Card --}}
            <div
                class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                {{-- Toolbar --}}
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-700">
                    <form method="GET" action="{{ route('activity-log.index') }}"
                        class="flex flex-wrap items-center gap-2 w-full">
                        <div class="relative flex-1 min-w-[200px] max-w-md">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}"
                                placeholder="Cari log..."
                                class="w-full rounded-xl border-gray-300 pl-9 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400">
                        </div>
                        <select name="role"
                            class="rounded-xl border-gray-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                            <option value="">Semua Role</option>
                            <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="hr" {{ request('role') === 'hr' ? 'selected' : '' }}>HR</option>
                            <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                        </select>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-red-700 dark:bg-gray-800 dark:text-gray-100 dark:border dark:border-gray-700 dark:hover:bg-gray-700">
                            Filter
                        </button>
                        @if (request()->hasAny(['search', 'role']))
                            <a href="{{ route('activity-log.index') }}"
                                class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500 transition hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                                ✕ Reset
                            </a>
                        @endif
                    </form>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead class="border-b border-gray-100 bg-gray-50/80 dark:border-gray-700 dark:bg-gray-700/50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Waktu</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    User</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Role</th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Aktivitas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($logs as $log)
                                <x-activity-log-row :log="$log" />
                            @empty
                                <tr>
                                    <td colspan="4"
                                        class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada data log aktivitas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-gray-100 px-6 py-4 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Menampilkan {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }}
                        dari {{ $logs->total() }} data
                    </p>
                    {{ $logs->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
