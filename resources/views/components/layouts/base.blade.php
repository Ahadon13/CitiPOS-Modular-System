<!DOCTYPE html>
<html class="h-full"
      lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport"
                content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token"
                content="{{ csrf_token() }}">
        <title> CitiPOS {{ isset($title) ? '| ' . $title : '' }}</title>
        <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
        <meta name="apple-mobile-web-app-title" content="CitiPOS" />
        <link rel="manifest" href="{{ asset('site.webmanifest') }}" />

        <tallstackui:script />
        @livewireStyles
        <style>
            /* gives the progress bar primary color */
            :root {
                --livewire-progress-bar-color: var(--color-primary);
            }

        </style>
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            // Load dark mode before page renders to prevent flicker
            const loadDarkMode = () => {
                const theme = localStorage.getItem('theme') ?? 'dark'

                if (
                    theme === 'dark' ||
                    (theme === 'system' &&
                        window.matchMedia('(prefers-color-scheme: dark)')
                            .matches)
                ) {
                    document.documentElement.classList.add('dark')
                }
            }

            // Initialize on page load
            loadDarkMode();

            // Reinitialize after Livewire navigation (for spa mode)
            document.addEventListener('livewire:navigated', function () {
                loadDarkMode();
            });

        </script>
    </head>
    <body class="bg-neutral-100 dark:bg-deep-space text-neutral-900 dark:text-neutral-50 font-inter antialiased min-h-screen">

        {{ $slot }}

        @livewireScriptConfig
        <script>
            loadDarkMode()
        </script>
        <x-ui.toast :maxToasts="10" position="top-right" />
        <x-ui.confirm-modal />

        {{-- OFFLINE OVERLAY --}}
        <div x-data="{ offline: !navigator.onLine }" @offline.window="offline = true" @online.window="offline = false" x-show="offline" x-transition.opacity.duration.300ms style="display: none;" class="fixed inset-0 z-9999 bg-neutral-50 dark:bg-[#0a1331] flex items-center justify-center p-6 backdrop-blur-md">
            <div class="max-w-md w-full bg-white dark:bg-[#060A23] rounded-3xl shadow-2xl border border-black/10 dark:border-white/10 p-8 text-center transform transition-all">

                {{-- Animated / Colored Icon --}}
                <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-rose-100 dark:bg-rose-500/20 mb-6 relative">
                    {{-- Ping animation ring --}}
                    <div class="absolute inset-0 rounded-full border-4 border-rose-200 dark:border-rose-500/30 animate-ping"></div>

                    {{-- Wifi Slash Icon --}}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-10 text-rose-600 dark:text-rose-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M22.853 7.71a16.691 16.691 0 0 0-2.012-1.745M19.1 4.542a17.067 17.067 0 0 0-14.2 0M11.963 15.025l3.204-3.204m-3.204 3.204-3.204-3.204m3.204 3.204v5.39m0-12.78v5.39m-8.31-2.186a11.956 11.956 0 0 1-1.07-1.127M3.011 8.825a12.018 12.018 0 0 1 1.62-1.428" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                    </svg>
                </div>

                {{-- Text Content --}}
                <h2 class="text-2xl font-black text-neutral-900 dark:text-white mb-2">No Internet Connection</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-8">
                    It looks like you've lost your connection to the network. Please check your Wi-Fi or mobile data to continue working.
                </p>

                {{-- Action Button (Forces a page reload to test connection) --}}
                <button type="button" onclick="window.location.reload()" class="w-full inline-flex justify-center items-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="size-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    Try Again
                </button>

                {{-- Auto-reconnect message --}}
                <p class="mt-4 text-[10px] uppercase tracking-widest font-bold text-neutral-400 dark:text-neutral-500 animate-pulse">
                    Waiting for connection...
                </p>
            </div>
        </div>
    </body>

</html>
