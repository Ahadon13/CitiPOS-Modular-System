<div class="max-w-7xl mx-auto space-y-6">
    {{-- Header & Action Buttons --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Purchase Orders</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Manage supplier orders and receiving</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 justify-end">
            <x-ui.button variant="outline" icon="truck"  x-on:click="$dispatch('open-modal', { id: 'create-supplier' })">
                Suppliers
            </x-ui.button>
            <x-ui.button variant="outline" icon="inbox-arrow-down" href="{{ route('inventory.pharmacy.purchases.record') }}" wire:navigate>
                Record Receiving
            </x-ui.button>
            <x-ui.button icon="plus" href="{{ route('inventory.pharmacy.purchases.create') }}" wire:navigate>
                New Purchase Order
            </x-ui.button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500!">
            <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Total Orders</p>
            <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-400 mt-1">{{ number_format($this->stats['total_orders']) }}</h3>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-yellow-500!">
            <p class="text-xs font-medium text-yellow-600/80 uppercase tracking-wide">Pending</p>
            <h3 class="text-2xl font-bold text-yellow-700 dark:text-yellow-400 mt-1">{{ number_format($this->stats['pending_orders']) }}</h3>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-green-500!">
            <p class="text-xs font-medium text-green-600/80 uppercase tracking-wide">Received</p>
            <h3 class="text-2xl font-bold text-green-700 dark:text-green-400 mt-1">{{ number_format($this->stats['receiving_orders']) }}</h3>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-indigo-500!">
            <p class="text-xs font-medium text-indigo-600/80 uppercase tracking-wide">Suppliers</p>
            <h3 class="text-2xl font-bold text-indigo-700 dark:text-indigo-400 mt-1">{{ number_format($this->stats['total_suppliers']) }}</h3>
        </x-ui.card>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-black/10 dark:border-white/10">
        <nav class="-mb-px flex space-x-6 overflow-x-auto custom-scrollbar" aria-label="Tabs">
            @php
                $tabs = [
                    'all' => 'All Orders',
                    'pending' => 'Pending',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <button
                    wire:click="$set('statusFilter', '{{ $key }}')"
                    class="whitespace-nowrap py-3 px-2 border-b-2 font-medium text-sm transition-colors {{ $statusFilter === $key ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-300 dark:hover:border-neutral-600' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Data Table --}}
    <x-ui.card hoverless size="full" class="p-0">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="w-full md:w-80">
                <x-ui.input clearable wire:model.live.debounce.300ms="search" leftIcon="magnifying-glass" placeholder="Search PO Number or Supplier..." class="w-full" />
            </div>
            <div class="flex items-center gap-3">
                <x-ui.field class="w-70!">
                    <x-ui-select.styled
                        wire:model.live="supplierFilter"
                        placeholder="All Suppliers"
                        :options="$this->supplierOptions"
                        select="label:label|value:value"
                        searchable
                    />
                </x-ui.field>
                <x-ui.button
                    size="sm"
                    variant="outline"
                    icon="arrow-down-tray"
                    wire:click="exportPurchases"
                    wire:loading.attr="disabled"
                    wire:target="exportPurchases"
                >
                    Export in Excel
                </x-ui.button>
            </div>
        </div>

        <div class="w-full text-sm text-neutral-300 overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                        <th class="px-6 py-4">PO Number</th>
                        <th class="px-6 py-4">Expected Delivery Date</th>
                        <th class="px-6 py-4">Supplier</th>
                        <th class="px-6 py-4 text-center">Items</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Total Cost</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                    @forelse ($this->purchases as $po)
                        <tr class="hover:bg-white/5 transition-colors group">
                            <td class="px-6 py-4 font-mono font-medium text-blue-600 dark:text-blue-400">
                                {{ $po->reference_no }}
                            </td>
                            <td class="px-6 py-4 text-neutral-900 dark:text-white">
                                {{ $po->expected_delivery_date ? $po->expected_delivery_date->format('M d, Y') : 'Not specified' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-neutral-900 dark:text-white">
                                {{ $po->supplier->name ?? 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 text-center text-neutral-600 dark:text-neutral-400">
                                {{ $po->purchase_items_count }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($po->status->value === 'completed')
                                    <span class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 ring-1 ring-inset ring-green-400/20">Fully Received</span>
                                @elseif($po->status->value === 'receiving')
                                    <span class="inline-flex items-center rounded-md bg-orange-400/10 px-2 py-1 text-xs font-medium text-orange-600 dark:text-orange-400 ring-1 ring-inset ring-orange-400/20">Receiving</span>
                                @elseif($po->status->value === 'pending')
                                    <span class="inline-flex items-center rounded-md bg-yellow-400/10 px-2 py-1 text-xs font-medium text-yellow-600 dark:text-yellow-400 ring-1 ring-inset ring-yellow-400/20">Pending</span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">Cancelled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-neutral-900 dark:text-white font-bold tracking-tight">
                                @money($po->total_cost)
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if($po->status->value !== 'completed' && $po->status->value !== 'cancelled' )
                                    <x-ui.button
                                        size="xs"
                                        variant="outline"
                                        icon="inbox-arrow-down"
                                        x-on:click="await $wire.set('selected_purchase', {{ $po }}, false); $dispatch('open-modal', { id: 'receive-purchase' });"
                                    >
                                        Receive
                                    </x-ui.button>
                                    @endif
                                    <x-ui.button
                                        size="xs"
                                        variant="outline"
                                        icon="eye"
                                        x-on:click="await $wire.set('view_purchase', {{ $po }}, false); $dispatch('open-modal', { id: 'view-purchase' });"
                                    >
                                        View
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-24 text-center">
                                <x-ui.empty>
                                    <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                        <x-ui.icon name="truck" class="size-6" />
                                    </x-ui.empty.media>

                                    <x-ui.empty.contents>
                                        <x-ui.heading>No purchases found</x-ui.heading>
                                        <x-ui.text class="opacity-70">
                                            We couldn't find any purchases, try adjusting your filters or add new purchase orders to see them here.
                                        </x-ui.text>
                                    </x-ui.empty.contents>
                                </x-ui.empty>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center">
            <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->purchases" />
        </div>
    </x-ui.card>

    {{-- Receive Order Modal --}}
    <livewire:inventory.pages.pharmacy.purchase.receive-purchase-modal wire:model="selected_purchase" />
    {{-- View/Print Modal --}}
    <livewire:inventory.pages.pharmacy.purchase.view-purchase-modal wire:model="view_purchase" />
    {{-- Create Supplier Modal --}}
    <livewire:inventory.pages.pharmacy.common.create-supplier-modal />
</div>
