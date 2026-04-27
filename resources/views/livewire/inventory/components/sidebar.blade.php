<x-ui.sidebar>
    <x-slot:brand>
        <x-ui.brand name="CitiPOS" href="/" logoClass="size-9!" logo="{{ asset('favicon.svg') }}" />
    </x-slot:brand>

    @if($this->branches->count() > 1)
        <div class="px-3 pb-3 [:has([data-collapsed]_&)_&]:hidden">
            <x-ui.field class="mb-0">
                <select
                    wire:change="switchBranch($event.target.value)"
                    class="w-full rounded-lg border-neutral-300 bg-white text-xs text-neutral-700 focus:ring-blue-500 dark:border-neutral-700 dark:bg-[#0a1331] dark:text-neutral-200"
                >
                    @foreach($this->branches as $branch)
                        <option value="{{ $branch->id }}" @selected(auth()->user()->branch_id === $branch->id)>
                            {{ $branch->name }} / {{ $branch->productCategory->name ?? 'module' }}
                        </option>
                    @endforeach
                </select>
            </x-ui.field>
        </div>
    @endif

    @if ($module === 'pharmacy')
        <x-pharmacy-sidebar-item />
    @elseif ($module === 'grocery')
        <x-grocery-sidebar-item />
    @elseif ($module === 'motor-shop')
        <x-motor-shop-sidebar-item />
    @endif

    <x-ui.navlist class="mt-auto">

        @if ($module === 'pharmacy')
        <x-ui.navlist.item size="sm" label="Settings" icon="cog-6-tooth" href="{{ route('inventory.pharmacy.settings') }}" active="inventory.pharmacy.settings.*" />
        @elseif ($module === 'grocery')
        <x-ui.navlist.item size="sm" label="Settings" icon="cog-6-tooth" href="{{ route('inventory.grocery.settings') }}" active="inventory.grocery.settings.*" />
        @elseif ($module === 'motor-shop')
        <x-ui.navlist.item size="sm" label="Settings" icon="cog-6-tooth" href="{{ route('inventory.motor-shop.settings') }}" active="inventory.motor-shop.settings.*" />
        @endif

        <form method="POST" action="{{ route('logout') }}" x-data>
            @csrf
            <x-ui.navlist.item
                size="sm"
                label="Logout"
                icon="arrow-right-start-on-rectangle"
                href="#"
                @click.prevent="$root.submit()"
            />
        </form>
    </x-ui.navlist>
</x-ui.sidebar>
