@props(['title' => '', 'inventory' => false])
<x-layouts.base>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <x-ui.layout>
        @if ($inventory)
            <livewire:inventory.components.sidebar />
        @endif

        <x-ui.layout.main>
            <x-ui.layout.header>
                @if ($inventory)
                <x-ui.navbar>
                    <x-ui.navbar.item class="bg-[--alpha(var(--color-primary)_/5%)]" label="POS Terminal" icon="shopping-cart" href="{{ route('pos.dashboard') }}" />
                </x-ui.navbar>
                @endif

                <!-- User menu, search, etc. -->
                <div class="ml-auto flex items-center gap-3 mr-2">
                    <x-ui.theme-switcher.variants.inline />
                    <x-ui.separator vertical />
                    {{-- User Information --}}
                    <div class="flex flex-col items-start text-left">
                        <span class="text-sm font-bold leading-none text-gray-800 dark:text-white">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-[10px] font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ auth()->user()->role }}
                        </span>
                    </div>
                </div>
            </x-ui.layout.header>

            <!-- Your page content -->
            <div class="m-6">
                {{ $slot }}
            </div>
        </x-ui.layout.main>
    </x-ui.layout>
</x-layouts.base>
