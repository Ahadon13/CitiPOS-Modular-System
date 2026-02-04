<!DOCTYPE html>
<html class="h-full"
      lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport"
                content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token"
                content="{{ csrf_token() }}">
        <title> POSAI {{ isset($title) ? '| ' . $title : '' }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}">

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
        <x-ui.toast :maxToasts="1" />
    </body>

</html>
