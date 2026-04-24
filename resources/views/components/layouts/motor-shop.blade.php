@props(['title' => '', 'inventory' => false])
<x-layouts.base>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    <x-ui.layout>
        @if ($inventory)
            <livewire:inventory.components.sidebar module="motor-shop" />
        @endif

        <x-ui.layout.main>
            @if ($inventory)
            <x-ui.layout.header>
                <!-- Realtime Date and Time -->
                <div class="flex flex-col items-start ml-3 sm:ml-5" x-data="{ time: new Date().toLocaleTimeString() }" x-init="setInterval(() => time = new Date().toLocaleTimeString(), 1000)">
                    <span class="font-semibold" x-text="time"></span>
                    <span class="text-xs font-medium text-gray-500">
                        {{ now()->format('F j, Y') }}
                    </span>
                </div>
                <!-- User menu, search, etc. -->
                <div class="ml-auto flex items-center gap-3 mr-3 sm:mr-5">
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
            @endif
            <!-- Your page content -->
            <div class="m-3 sm:m-5 min-w-0 max-w-full">
                {{ $slot }}
            </div>
        </x-ui.layout.main>
    </x-ui.layout>
</x-layouts.base>
