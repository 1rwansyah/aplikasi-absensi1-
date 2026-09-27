<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="face-model-path" content="{{ asset('models') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/logo/logo_SADAR.png') }}">

        {{-- <link rel="preconnect" href="https://fonts.bunny.net"> --}}
        {{-- <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" /> --}}

        @stack('head-scripts')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        <style>[x-cloak]{display:none!important}</style>
        {{-- Sidebar docked at lg (1024px)+, controlled via data attributes (no !important / no conflicting media query) --}}

        <script>
            if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        </script>
    </head>
    <body class="min-h-screen overflow-x-hidden font-sans antialiased bg-gray-100 transition-colors duration-300 ease-in-out dark:bg-gray-900">

        {{-- Mobile backdrop overlay (never shown on lg+) --}}
        <div id="sidebar-backdrop"
            class="fixed inset-0 z-20 bg-gray-900/50 hidden lg:hidden"
            aria-hidden="true">
        </div>

        {{-- Sidebar: fixed overlay on mobile, docked on lg+ when data-open=true --}}
        <aside
            id="sidebar-shell"
            data-open="false"
            class="fixed inset-y-0 left-0 z-30 flex h-full w-64 -translate-x-full flex-col overflow-hidden transition-transform duration-150 ease-in-out data-[open=true]:translate-x-0"
        >
            @include('layouts.sidebar')
        </aside>

        {{-- Main wrapper: offset by sidebar width only when docked open on lg+ --}}
        <div
            id="main-wrapper"
            data-sidebar-open="false"
            class="flex min-h-screen min-w-0 flex-col transition-[margin] duration-150 ease-in-out lg:data-[sidebar-open=true]:ml-64"
        >
            @include('layouts.navigation')

            @isset($header)
                <header class="flex-shrink-0 border-b border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex min-h-0 min-w-0 flex-1 flex-col overflow-x-hidden">
                {{ $slot }}
            </main>
        </div>

        {{-- Avoid FOUC: sync data attrs before full JS module boots --}}
        <script>
            (function () {
                var desktop = window.matchMedia('(min-width: 1024px)').matches;
                var open = desktop && localStorage.getItem('sidebarOpen') !== '0';
                var shell = document.getElementById('sidebar-shell');
                var main = document.getElementById('main-wrapper');
                if (shell) shell.dataset.open = open ? 'true' : 'false';
                if (main) main.dataset.sidebarOpen = open ? 'true' : 'false';
            })();
        </script>

        <x-ui.toast-stack />

        @stack('modals')
        @stack('scripts')

        <script>
            document.addEventListener('submit', function (event) {
                const form = event.target;

                if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') {
                    return;
                }

                const tokenInput = form.querySelector('input[name="_token"]');
                const metaToken = document.querySelector('meta[name="csrf-token"]');

                if (tokenInput && metaToken) {
                    tokenInput.value = metaToken.content;
                }
            });
        </script>
    </body>
</html>
