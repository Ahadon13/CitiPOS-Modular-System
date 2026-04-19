<div class="max-w-7xl mx-auto space-y-6">

    {{-- Breadcrumbs & Header --}}
    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('inventory.pharmacy.products') }}">Products</x-ui.breadcrumbs.item>
        <x-ui.breadcrumbs.item active>Bulk Price Book</x-ui.breadcrumbs.item>
    </x-ui.breadcrumbs>

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Bulk Partnership Pricing</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Apply local mandated prices for {{ $this->user->branch->name ?? 'your branch' }}.</p>
        </div>
    </div>

    {{-- THE BULK ACTION BAR --}}
    <div class="bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/50 rounded-xl p-5 shadow-sm">
        <h3 class="text-sm font-bold text-blue-900 dark:text-blue-300 mb-5 flex items-center gap-2">
            <x-ui.icon name="bolt" class="size-5 text-blue-600" />
            Apply Prices to Selected Products
        </h3>

        <div class="flex flex-col sm:flex-row items-end gap-4">
            @foreach($this->customerTypes as $type)
            <div class="w-full sm:w-40">
                <x-ui.label class="text-blue-800 dark:text-blue-400">{{ $type->name }} Price</x-ui.label>
                <x-ui.input wire:model="bulkPrices.{{ $type->id }}" type="number" step="any" min="0" placeholder="Price (₱)" left-icon="currency-dollar" />
            </div>
            @endforeach

            <div>
                <x-ui.button wire:click="applyBulkPrices" color="primary" icon="check" wire:loading.attr="disabled" class="w-full sm:w-auto">
                    <span wire:loading.remove wire:target="applyBulkPrices">Apply to Selected</span>
                    <span wire:loading wire:target="applyBulkPrices">Applying...</span>
                </x-ui.button>
            </div>
        </div>

        @if(count($selectedPackagings) > 0)
        <p class="text-xs font-bold text-blue-600 mt-3 animate-pulse">
            {{ count($selectedPackagings) }} item(s) selected and ready to update.
        </p>
        @else
        <p class="text-xs text-blue-600/70 mt-3">Select items from the table below using the checkboxes.</p>
        @endif
    </div>

    <x-ui.card hoverless size="full" class="p-0">

        {{-- Filters (Category & Search) --}}
        <div class="p-4 rounded-t-lg border-b border-neutral-200 dark:border-white/10 bg-neutral-50 dark:bg-[#0a1331] flex flex-col sm:flex-row gap-4">
            <div class="w-full sm:w-64">
                <select wire:model.live="categoryId" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-[#060A23] text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <x-ui.input wire:model.live.debounce.500ms="search" clearable placeholder="Search by generic or brand name..." left-icon="magnifying-glass" />
            </div>
        </div>

        {{-- The Data Table --}}
        <div class="overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-neutral-100 dark:bg-white/5 text-xs uppercase text-neutral-500">
                    <tr>
                        <th class="px-4 py-3 w-10 text-center">
                            {{-- Native Checkbox for Select All --}}
                            <input type="checkbox" wire:model.live="selectAll" class="w-4 h-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 cursor-pointer">
                        </th>
                        <th class="px-4 py-3 font-bold">Product</th>
                        <th class="px-4 py-3 font-bold">Packaging Unit</th>
                        <th class="px-4 py-3 font-bold text-right border-r border-neutral-200 dark:border-white/10">Retail Price</th>

                        @foreach($this->customerTypes as $type)
                        <th class="px-4 py-3 font-bold bg-neutral-50 dark:bg-white/5 text-center">
                            {{ $type->name }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                    @forelse($this->packagings as $packaging)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors {{ in_array($packaging->id, $selectedPackagings) ? 'bg-blue-50/30 dark:bg-blue-900/10' : '' }}" wire:key="row-{{ $packaging->id }}">

                        {{-- Native Individual Row Checkbox --}}
                        <td class="px-4 py-3 text-center">
                            <input type="checkbox" wire:model.live="selectedPackagings" value="{{ $packaging->id }}" class="w-4 h-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 cursor-pointer">
                        </td>

                        {{-- Product Details --}}
                        <td class="px-4 py-3">
                            <div class="font-bold text-neutral-900 dark:text-white">{{ $packaging->product->brand_name }}</div>
                            <div class="text-xs text-neutral-500">{{ $packaging->product->generic_name }} • {{ $packaging->product->dosage }}</div>
                        </td>

                        {{-- Packaging Unit --}}
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 bg-neutral-100 dark:bg-white/10 rounded text-xs font-bold text-neutral-700 dark:text-neutral-300">
                                {{ $packaging->unit->name }}
                            </span>
                            @if($packaging->is_base)
                            <span class="text-[10px] text-blue-500 ml-1">(Base)</span>
                            @endif
                        </td>

                        {{-- Retail Price --}}
                        <td class="px-4 py-3 text-right font-mono font-bold border-r border-neutral-200 dark:border-white/10 text-neutral-900 dark:text-white">
                            @money($packaging->price)
                        </td>

                        {{-- Display Current Partnership Prices directly from Eloquent --}}
                        @foreach($this->customerTypes as $type)
                        <td class="px-4 py-3 text-center">
                            @php
                            // Find the partnership for this specific column's customer type
                            $partnership = $packaging->partnerships->firstWhere('customer_type_id', $type->id);
                            @endphp

                            @if($partnership)
                            <div class="group flex items-center justify-center gap-2">
                                <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                                    ₱{{ number_format($partnership->special_price->getAmount() / 100, 2) }}
                                </span>
                                <button wire:click="removePrice({{ $packaging->id }}, {{ $type->id }})" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 transition-opacity" title="Clear this price">
                                    <x-ui.icon name="x-mark" class="size-4" />
                                </button>
                            </div>
                            @else
                            <span class="text-neutral-400 dark:text-neutral-600">-</span>
                            @endif
                        </td>
                        @endforeach

                    </tr>
                    @empty
                    <tr>
                        <td colspan="100%" class="text-center py-12 text-neutral-500">
                            <x-ui.empty>
                                <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                    <x-ui.icon name="cube" class="size-6" />
                                </x-ui.empty.media>

                                <x-ui.empty.contents>
                                    <x-ui.heading>No products found</x-ui.heading>
                                    <x-ui.text class="opacity-70">
                                        Try adjusting your search or filter criteria.
                                    </x-ui.text>
                                </x-ui.empty.contents>
                            </x-ui.empty>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-4 pb-4 border-t border-neutral-200 dark:border-white/10">
            <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->packagings" />
        </div>
    </x-ui.card>

</div>
