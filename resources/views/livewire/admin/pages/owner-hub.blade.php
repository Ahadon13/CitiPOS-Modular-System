<div class="min-h-screen
            bg-linear-to-br from-blue-50/40 via-white to-blue-50/20
            dark:from-slate-900 dark:via-slate-950 dark:to-blue-950/30
            flex flex-col pt-16 pb-12 px-4 sm:px-6">
    <div class="max-w-4xl w-full mx-auto">

        {{-- ========================================== --}}
        {{-- HEADER SECTION                             --}}
        {{-- ========================================== --}}
        <div class="flex justify-between items-center mb-8">
            <div class="flex gap-4 items-center">
                {{-- Logo Placeholder --}}
                <div class="size-14 bg-white dark:bg-white/10 dark:border-blue-100/10 rounded-md shadow-sm border border-blue-100 flex items-center justify-center shrink-0">
                    <x-ui.brand href="/" logo="{{ asset('favicon.png') }}" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-white tracking-tight">
                        Welcome back, {{ auth()->user()->name ?? 'User' }}!
                    </h1>
                </div>
            </div>

            <x-ui.button variant="ghost" wire:click="logout" icon="arrow-right-start-on-rectangle" class="hover:text-blue-600 hover:bg-blue-50">
                Sign Out
            </x-ui.button>
        </div>

        {{-- ========================================== --}}
        {{-- ADMIN PANEL CARD                           --}}
        {{-- ========================================== --}}
        <a href="{{ route('admin.dashboard') }}" class="block mb-10 group">
            <x-ui.card size="full" hoverless class="bg-white/60 hover:bg-white/90 transition-all duration-200 border-blue-100 shadow-[0_4px_20px_-4px_rgba(225,29,72,0.05)] backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-blue-50 dark:bg-blue-50/10 text-blue-500 rounded-xl flex items-center justify-center group-hover:scale-105 transition-transform">
                            <x-ui.icon name="squares-2x2" class="size-6" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Admin Panel</h2>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">Manage branches, users, settings & unified branch reports</p>
                        </div>
                    </div>
                    <x-ui.icon name="chevron-right" class="size-5 text-neutral-300 group-hover:text-blue-500 transition-colors" />
                </div>
            </x-ui.card>
        </a>

        {{-- ========================================== --}}
        {{-- BRANCHES SECTION                           --}}
        {{-- ========================================== --}}
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-neutral-900 dark:text-white tracking-tight">Your Branches</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ count($this->branches) }} branch{{ count($this->branches) !== 1 ? 'es' : '' }}</p>
            </div>

            <x-ui.button size="sm" icon="plus" x-on:click="$dispatch('open-modal', { id: 'manage-branch' })">
                Add
            </x-ui.button>
        </div>

        {{-- Branch Cards List --}}
        <div class="space-y-3">
            @forelse($this->branches as $branch)
            <x-ui.card size="full" hoverless class="bg-white/60 hover:bg-white/90 transition-all duration-200 border-blue-100 shadow-[0_4px_20px_-4px_rgba(225,29,72,0.05)] backdrop-blur-sm !p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">

                    {{-- Left: Branch Identity --}}
                    <div class="flex items-center gap-4">
                        <div class="size-12 bg-amber-50 dark:bg-amber-50/10 text-amber-500 rounded-xl flex items-center justify-center shrink-0 border border-amber-100/50 dark:border-amber-100/20">
                            <x-ui.icon name="star" class="size-6" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-neutral-900 dark:text-white text-lg">{{ $branch->name }}</h3>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-neutral-500 mt-0.5">
                                <span>{{ $branch->address ?? 'Tagum City' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Right: Stats & Action --}}
                    <div class="flex items-center justify-between sm:justify-end gap-6 sm:gap-8 border-t sm:border-t-0 border-blue-100/50 pt-3 sm:pt-0">
                        <div class="text-center">
                            <p class="font-bold text-neutral-900 dark:text-white leading-none">₱{{ number_format($branch->today_sales ?? 0) }}</p>
                            <p class="text-[10px] font-bold text-neutral-400 dark:text-neutral-400 uppercase tracking-widest mt-1.5">today</p>
                        </div>
                        <div class="text-center">
                            <p class="font-bold text-neutral-900 dark:text-white leading-none">{{ number_format($branch->today_orders ?? 0) }}</p>
                            <p class="text-[10px] font-bold text-neutral-400 dark:text-neutral-400 uppercase tracking-widest mt-1.5">orders</p>
                        </div>

                        <x-ui.button href="{{ route('admin.branches.view', $branch->id) }}" iconAfter="chevron-right" wire:loading.attr="disabled" class="bg-blue-500 hover:bg-blue-600 text-white border-0 shadow-md shadow-blue-500/20 px-5">
                            Enter
                        </x-ui.button>
                    </div>

                </div>
            </x-ui.card>
            @empty
            {{-- Sheaf Empty State Component --}}
            <x-ui.empty>
                <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-50">
                    <x-ui.icon name="building-storefront" class="size-6 text-blue-500" />
                </x-ui.empty.media>
                <x-ui.empty.contents>
                    <x-ui.heading>No branches found</x-ui.heading>
                    <x-ui.text class="opacity-70">
                        Add your first branch to get started.
                    </x-ui.text>
                    <x-ui.button variant="outline" icon="plus" size="sm" class="mt-3">
                        Add Branch
                    </x-ui.button>
                </x-ui.empty.contents>
            </x-ui.empty>
            @endforelse
        </div>

        {{-- ========================================== --}}
        {{-- FOOTER                                     --}}
        {{-- ========================================== --}}
        <div class="mt-16 text-center text-xs text-neutral-400 font-medium">
            CitiPOS &middot; Point of Sale System and Inventory Management
        </div>

    </div>

    {{-- Manage Branch Modal --}}
    <livewire:admin.common.manage-branch-modal />

</div>
