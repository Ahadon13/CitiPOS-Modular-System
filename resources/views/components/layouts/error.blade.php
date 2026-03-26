<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name') }}</title>

    {{-- Assuming you are using Vite for your Tailwind CSS --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased bg-neutral-50 dark:bg-[#060A23] text-neutral-900 dark:text-white flex items-center justify-center min-h-screen selection:bg-electric-blue selection:text-white">

    <div class="max-w-xl w-full px-6 py-12 text-center relative">
        {{-- Background Glow Effect --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-500/20 dark:bg-blue-500/10 blur-3xl rounded-full pointer-events-none"></div>

        <div class="relative z-10">
            @yield('content')
        </div>
    </div>

</body>
</html>
