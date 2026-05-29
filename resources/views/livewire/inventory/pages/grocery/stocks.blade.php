<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Grocery Stocks</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Manage physical inventory batches and expiration dates</p>
        </div>
        <div class="flex items-center gap-3 justify-end">
            <x-ui.button variant="outline" icon="arrow-path" wire:click="$refresh">
                Refresh
            </x-ui.button>
        </div>
    </div>

    {{-- Tabs for Stock Filtering --}}
    <div class="border-b border-black/10 dark:border-white/10">
        <nav class="-mb-px flex space-x-6 overflow-x-auto custom-scrollbar" aria-label="Tabs">
            @php
                $tabs = [
                    'all' => 'Active Stocks',
                    'expiring' => 'Expiring Soon',
                    'expired' => 'Expired',
                    'out_of_stock' => 'Empty / Depleted Batches',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <button
                    wire:click="$set('stockFilter', '{{ $key }}')"
                    class="whitespace-nowrap py-3 px-2 border-b-2 font-medium text-sm transition-colors {{ $stockFilter === $key ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-300 dark:hover:border-neutral-600' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- 4 Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <x-ui.icon name="banknotes" class="size-6 text-blue-600! dark:text-blue-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Total Asset Value</p>
                    <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-400">
                        @money($this->totalStockValue)
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-emerald-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <x-ui.icon name="cube" class="size-6 text-emerald-600! dark:text-emerald-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-emerald-600/80 uppercase tracking-wide">Total Products</p>
                    <h3 class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                        {{ number_format($this->totalProductsCount) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-indigo-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <x-ui.icon name="archive-box" class="size-6 text-indigo-600! dark:text-indigo-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-indigo-600/80 uppercase tracking-wide">Active Batches</p>
                    <h3 class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">
                        {{ number_format($this->activeBatchesCount) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-orange-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <x-ui.icon name="exclamation-triangle" class="size-6 text-orange-600! dark:text-orange-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-orange-600/80 uppercase tracking-wide">Critical Expiries</p>
                    <h3 class="text-2xl font-bold text-orange-700 dark:text-orange-400">
                        {{ number_format($this->criticalExpiryCount) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Data Table Section --}}
    <x-ui.card hoverless size="full" class="p-0 overflow-hidden">

        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col lg:flex-row md:items-center justify-between gap-4">

            <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto flex-1">
                {{-- Search --}}
                <div class="w-full sm:w-72">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        leftIcon="magnifying-glass"
                        placeholder="Search batch or product..."
                        class="w-full"
                        clearable
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 w-full md:w-auto">
                {{-- Product Dropdown Filter --}}
                <div class="w-full sm:w-70">
                    <x-ui-select.styled
                        invalidate
                        wire:model.live="selectedProduct"
                        placeholder="Filter by Product..."
                        :options="$this->availableProducts"
                        searchable
                        select="label:label|value:value"
                    />
                </div>

                <div class="w-full sm:w-56">
                    <x-ui-date
                        invalidate
                        month-year-only
                        wire:model.live="expirationDateFilter"
                        placeholder="Filter by Expiry Date"
                        format="MMMM DD, YYYY"
                    />
                </div>

                <x-ui.button size="sm" variant="outline" icon="arrow-down-tray" wire:click="exportLedger" wire:loading.attr="disabled" wire:target="exportLedger">
                    Export in Excel
                </x-ui.button>
            </div>
        </div>

        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Product Name</th>
                                <th class="px-6 py-4">Base Unit</th>
                                <th class="px-6 py-4">Batch Number</th>
                                <th class="px-6 py-4 text-center">Available Qty</th>
                                <th class="px-6 py-4 text-center">Expiration Date</th>
                                <th class="px-6 py-4 text-right">Unit Cost</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->inventoryBatches as $batch)
                                @php
                                // 1. Parse the date EXACTLY ONCE to save memory/processing speed
                                $expirationDate = \Carbon\Carbon::parse($batch->expiration_date)->endOfDay();

                                // 2. Is it entirely in the past? (endOfDay ensures it doesn't flag as expired at 8 AM on the exact day it expires)
                                $isExpired = $expirationDate->isPast();

                                // 3. Is it NOT expired, but the date is less than or equal to exactly 3 months from right now?
                                $isExpiringSoon = !$isExpired && $expirationDate->lte(now()->addMonths(3));
                                @endphp
                                <tr class="hover:bg-white/5 transition-colors group {{ $batch->quantity_on_hand <= 0 ? 'opacity-50' : '' }}">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-neutral-900 dark:text-white block">{{ $batch->product->brand_name }}</span>
                                        <span class="text-xs text-neutral-500">{{ $batch->product->product_code }}</span>
                                    </td>
                                    <td class="px-6 py-4 text-neutral-700 dark:text-neutral-400">
                                        {{ $batch->product->baseUnit->name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-mono inline-flex items-center rounded-md bg-neutral-100 dark:bg-white/5 px-2 py-1 text-xs font-medium text-neutral-700 dark:text-neutral-300 ring-1 ring-inset ring-neutral-500/20">
                                            {{ filled($batch->batch_number) ? $batch->batch_number : 'N/A' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <span class="font-bold {{ $batch->quantity_on_hand <= 0 ? 'text-red-500' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ number_format($batch->quantity_on_hand, 2) }}
                                        </span>
                                        <span class="text-xs text-neutral-500 ml-1">{{ $batch->product->baseUnit->abbreviation ?? 'pcs' }}</span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if($isExpired)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-rose-50 px-2 py-1 text-xs font-bold text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-400">
                                                <x-ui.icon name="x-circle" class="size-4" /> Expired
                                            </span>
                                        @elseif($isExpiringSoon)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-orange-50 px-2 py-1 text-xs font-bold text-orange-700 ring-1 ring-inset ring-orange-600/20 dark:bg-orange-900/20 dark:text-orange-400">
                                                <x-ui.icon name="exclamation-triangle" class="size-4" /> {{ \Carbon\Carbon::parse($batch->expiration_date)->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-neutral-700 dark:text-neutral-300">{{ \Carbon\Carbon::parse($batch->expiration_date)->format('M d, Y') }}</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right font-medium text-neutral-900 dark:text-white">
                                        @money($batch->cost_per_unit)
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <x-ui.button
                                                size="xs"
                                                variant="outline"
                                                icon="pencil-square"
                                                color="blue"
                                                wire:click="$dispatch('load-edit-batch', { id: {{ $batch->id }} })"
                                                title="Edit Batch"
                                            />

                                            <x-ui.button
                                                size="xs"
                                                variant="outline"
                                                icon="trash"
                                                color="red"
                                                wire:click="deleteBatch({{ $batch->id }})"
                                                wire:custom-confirm="Permanently delete the product {{ $batch->product->brand_name }} batch {{ filled($batch->batch_number) ? $batch->batch_number : 'N/A' }}?"
                                                title="Delete Batch"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-24 text-center">
                                        <x-ui.empty>
                                            <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                                <x-ui.icon name="archive-box" class="size-6" />
                                            </x-ui.empty.media>

                                            <x-ui.empty.contents>
                                                <x-ui.heading>No stocks found</x-ui.heading>
                                                <x-ui.text class="opacity-70">
                                                    We couldn't find any stocks, try adjusting your filters or add new stock batches to see them here.
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
                    <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->inventoryBatches" />
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Edit Batch Form Modal (Powered by Alpine) --}}
    <x-ui.modal id="edit-batch" width="md" heading="Edit Batch Record" :closeByEscaping="true" :closeByClickingAway="true">
        <div x-data="{ productName: '' }" x-on:open-edit-batch-modal.window="productName = $event.detail.productName; $dispatch('open-modal', { id: 'edit-batch' })">
            <form wire:submit.prevent="updateBatch" class="space-y-4">

                <div class="bg-neutral-50 dark:bg-white/5 p-3 rounded border border-black/5 dark:border-white/10 mb-4">
                    <p class="text-xs text-neutral-500 uppercase">Target Product</p>
                    <p class="font-bold text-neutral-900 dark:text-white" x-text="productName"></p>
                </div>

                <x-ui.field>
                    <x-ui.label>Batch Number</x-ui.label>
                    <x-ui.input wire:model="form.batch_number" />
                    <x-ui.error name="form.batch_number" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Expiration Date</x-ui.label>
                    <x-ui.input type="date" wire:model="form.expiration_date" />
                    <x-ui.error name="form.expiration_date" />
                </x-ui.field>

                <div class="grid grid-cols-2 gap-4">
                    <x-ui.field required>
                        <x-ui.label>Actual Qty</x-ui.label>
                        <x-ui.input type="number" step="any" wire:model="form.quantity" />
                        <x-ui.error name="form.quantity" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Base Unit Cost ({{ $form->batch?->product->baseUnit->abbreviation }})</x-ui.label>
                        <x-ui.input type="number" step="any" wire:model="form.cost" />
                        <x-ui.error name="form.cost" />
                    </x-ui.field>
                </div>

                <div class="pt-4 flex justify-end gap-3 mt-4 border-t border-black/10 dark:border-white/10">
                    <x-ui.button type="button" variant="outline" x-on:click="$dispatch('close-modal', { id: 'edit-batch' })">Cancel</x-ui.button>
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateBatch" icon="check">Save Changes</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>
</div>
