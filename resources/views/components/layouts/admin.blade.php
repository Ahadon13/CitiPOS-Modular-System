@props(['title' => '', 'inventory' => false])
<x-layouts.base>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    {{-- Tell it to use the new layout! --}}
    <div class="flex-col flex min-h-screen" x-data="{ mobileMenuOpen: false }">

        <x-ui.layout.header class="my-0!" :sticky="false">

            {{-- Hamburger Button (Mobile Only) --}}
            <div class="flex items-center lg:hidden mr-3">
                <x-ui.button variant="ghost" icon="bars-3" x-on:click="mobileMenuOpen = true" />
            </div>

            <x-app.logo class="ml-1.5" />

            <x-ui.navbar class="flex-1 justify-center hidden lg:flex">
                @can('admin-dashboard')
                <x-ui.navbar.item size="sm" icon="rectangle-group" label="Dashboard" href="{{ route('admin.dashboard') }}" active="admin.dashboard.*" />
                @endcan

                @can('manage-branches')
                <x-ui.navbar.item size="sm" icon="building-office-2" label="Branches" href="{{ route('admin.branches') }}" active="admin.branches.*" />
                @endcan

                @can('manage-users')
                <x-ui.navbar.item size="sm" icon="users" label="Users" href="{{ route('admin.users') }}" active="admin.users.*" />
                @endcan

                @can('admin-reports')
                <x-ui.navbar.item size="sm" icon="chart-pie" label="Reports" href="{{ route('admin.reports') }}" active="admin.reports.*" />
                @endcan

                {{-- Fallback for Settings since it lacks a specific permission --}}
                @hasanyrole(['super-admin', 'admin'])
                <x-ui.navbar.item size="sm" icon="cog-6-tooth" label="Settings" href="{{ route('admin.settings') }}" active="admin.settings.*" />
                @endhasanyrole
            </x-ui.navbar>

            <div class="ml-auto flex items-center mr-1.5">
                <x-ui.theme-switcher.variants.inline />
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <x-ui.button variant="soft" @click.prevent="$root.submit()" icon="arrow-right-start-on-rectangle" />
                </form>
            </div>
        </x-ui.layout.header>

        {{-- ========================================== --}}
        {{-- MOBILE SIDEBAR / DRAWER                    --}}
        {{-- ========================================== --}}
        <div x-show="mobileMenuOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true" x-on:click.away="mobileMenuOpen = false">

            {{-- Backdrop --}}
            <div x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-neutral-900/80 backdrop-blur-sm" x-on:click="mobileMenuOpen = false"></div>

            {{-- Sidebar Panel --}}
            <div class="fixed inset-0 flex">
                <div x-show="mobileMenuOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex w-full max-w-xs flex-1 flex-col bg-white dark:bg-[#0a1331] pt-5 pb-4 shadow-xl border-r border-black/10 dark:border-white/10">

                    {{-- Header & Close Button --}}
                    <div class="flex items-center justify-between px-2 mb-6">
                        <x-app.logo />
                        <x-ui.button variant="ghost" icon="x-mark" x-on:click="mobileMenuOpen = false" />
                    </div>

                    {{-- Navigation Links --}}
                    <div class="flex flex-col flex-1 overflow-y-auto custom-scrollbar">
                        <x-ui.navlist>
                            @can('admin-dashboard')
                            <x-ui.navlist.item size="sm" icon="rectangle-group" label="Dashboard" href="{{ route('admin.dashboard') }}" active="admin.dashboard.*" />
                            @endcan

                            @can('manage-branches')
                            <x-ui.navlist.item size="sm" icon="building-office-2" label="Branches" href="{{ route('admin.branches') }}" active="admin.branches.*" />
                            @endcan

                            @can('manage-users')
                            <x-ui.navlist.item size="sm" icon="users" label="Users" href="{{ route('admin.users') }}" active="admin.users.*" />
                            @endcan

                            @can('admin-reports')
                            <x-ui.navlist.item size="sm" icon="chart-pie" label="Reports" href="{{ route('admin.reports') }}" active="admin.reports.*" />
                            @endcan
                        </x-ui.navlist>

                        <x-ui.navlist class="mt-auto">
                            <x-ui.separator />
                            {{-- Fallback for Settings since it lacks a specific permission --}}
                            @hasanyrole(['super-admin', 'admin'])
                            <x-ui.navlist.item size="sm" icon="cog-6-tooth" label="Settings" href="{{ route('admin.settings') }}" active="admin.settings.*" />
                            @endhasanyrole
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf
                                <x-ui.navlist.item size="sm" label="Logout" icon="arrow-right-start-on-rectangle" href="#" @click.prevent="$root.submit()" />
                            </form>
                        </x-ui.navlist>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full h-[calc(100vh-57px)]">
            {{ $slot }}
        </div>
    </div>

</x-layouts.base>

