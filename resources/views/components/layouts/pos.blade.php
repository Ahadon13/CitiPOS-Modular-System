@props(['title' => '', 'inventory' => false])
<x-layouts.base>
    <x-slot:title>
        {{ $title }}
    </x-slot:title>

    {{-- Tell it to use the new layout! --}}
    <div class="flex-col flex min-h-screen" x-data>
        <x-ui.layout.header class="my-0!" :sticky="false">
            <x-ui.button href="{{ route('inventory.pharmacy.dashboard') }}" variant="outline" size="sm" icon="cube" class="ml-1.5">
                Inventory
            </x-ui.button>

            <div class="flex flex-col items-start ml-3 sm:ml-5" x-data="{ time: new Date().toLocaleTimeString() }" x-init="setInterval(() => time = new Date().toLocaleTimeString(), 1000)">
                <span class="font-semibold" x-text="time"></span>
                <span class="text-xs font-medium text-gray-500">
                    {{ now()->format('F j, Y') }}
                </span>
            </div>

            <livewire:inventory.components.expiry-banner module="pharmacy" />

            <div class="ml-auto flex items-center gap-3 mr-1.5">
                <x-calculator />
                <x-ui.theme-switcher.variants.inline />
                <x-ui.separator vertical />
                <div class="flex flex-col items-start text-left">
                    <span class="text-sm font-bold leading-none text-gray-800 dark:text-white">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="text-[10px] font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ auth()->user()->role ?? 'Pharmacist' }}
                    </span>
                </div>
            </div>
        </x-ui.layout.header>

        <div class="w-full h-[calc(100vh-57px)]">
            {{ $slot }}
        </div>
    </div>

</x-layouts.base>
