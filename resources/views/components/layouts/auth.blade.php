@props(['title' => ''])
<x-layouts.base :title="$title">
    <div class="relative flex min-h-screen w-full flex-col overflow-hidden bg-neutral-50 dark:bg-[#060A23] z-0">

        <div class="absolute -top-40 -left-40 size-125 bg-primary-400 dark:bg-green-500/20! rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-blob pointer-events-none">
        </div>
        <div class="absolute -bottom-40 -right-40 size-125 bg-primary-400 dark:bg-green-500/20! rounded-full mix-blend-multiply filter blur-[100px] opacity-30 animate-blob pointer-events-none animation-delay-2000">
        </div>

        <div class="absolute top-6 left-6 md:top-10 md:left-10 z-20">
            <a href="/" class="flex items-center gap-2 text-sm font-medium text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white transition-colors">
                <x-ui.icon name="arrow-left" class="size-4" />
                Back to Home
            </a>
        </div>

        <div class="mx-auto flex w-full max-w-7xl flex-1 flex-col items-center justify-center px-6 pt-16 lg:flex-row lg:gap-24 z-10">

            <div class="w-full max-w-lg mb-12 lg:mb-0 space-y-8">
                <div class="flex items-center gap-3">
                    <x-app.logo />
                </div>

                <div class="space-y-4">
                    <h1 class="text-4xl md:text-5xl font-extrabold text-[#0a1331] dark:text-white tracking-tight">
                        Welcome back
                    </h1>
                    <p class="text-lg text-neutral-600 dark:text-neutral-400 leading-relaxed">
                        Continue with your account and manage sales, inventory, and reports in one dashboard.
                    </p>
                </div>

                <div class="space-y-4 pt-4 hidden sm:block">
                    <div class="rounded-2xl border border-neutral-300/60 dark:border-white/10 bg-white/50 dark:bg-[#0a1331]/50 px-6 py-4">
                        <p class="text-neutral-700 dark:text-neutral-300">Real-time sales monitoring.</p>
                    </div>
                    <div class="rounded-2xl border border-neutral-300/60 dark:border-white/10 bg-white/50 dark:bg-[#0a1331]/50 px-6 py-4">
                        <p class="text-neutral-700 dark:text-neutral-300">Built for Filipino stores with local payment workflows.</p>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-md lg:ml-auto">
                {{ $slot }}
            </div>

        </div>

        <div class="w-full pb-8 text-center z-10">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                By signing in, you agree to our
                <a href="#" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline">Terms of Service</a> and
                <a href="#" class="text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline">Privacy Policy</a>.
            </p>
        </div>
    </div>
</x-layouts.base>
