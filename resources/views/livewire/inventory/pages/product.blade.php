<div>
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Products</h1>
                <p class="text-neutral-500">Manage your product catalog</p>
            </div>
            <x-ui.button href="" icon="plus" wire:navigate>
                Add Product
            </x-ui.button>
        </div>

        <x-ui.card hoverless size="full" class="overflow-hidden p-0">

            <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">

                <div class="w-full md:w-72">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        leftIcon="magnifying-glass"
                        placeholder="Search name, SKU, brand..."
                        class="w-full"
                    />
                </div>

                <div class="flex items-center gap-3">
                    <x-ui.checkbox wire:model.live="lowStockOnly" size="sm" label="Low Stock Only" />

                    <select
                        wire:model.live="categoryFilter"
                        class="block w-40 rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 dark:text-white dark:bg-deep-space ring-1 ring-inset ring-gray-300 dark:ring-white/10 focus:ring-2 focus:ring-blue-600 sm:text-sm sm:leading-6"
                    >
                        <option value="">All Categories</option>
                        @foreach($this->categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <x-ui.button size="sm" variant="outline" icon="printer">
                        Print
                    </x-ui.button>
                </div>
            </div>

            <div class="w-full">
                <div class="w-full text-sm text-neutral-300">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    <th class="px-6 py-4">
                                        Name
                                    </th>
                                    <th class="px-6 py-4">SKU/Barcode</th>
                                    <th class="px-6 py-4">Category</th>
                                    <th class="px-6 py-4 text-center">Stock</th>
                                    <th class="px-6 py-4 text-right">Base Price</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                                @forelse ($this->products as $product)
                                    @php
                                        // Logic to get the base packaging (piece) price/barcode
                                        $basePkg = $product->productPackagings->where('unit_id', $product->base_unit_id)->first()
                                                ?? $product->productPackagings->first();
                                    @endphp

                                    <tr class="hover:bg-white/5 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-neutral-900 dark:text-white">{{ $product->name }}</div>
                                            <div class="flex gap-2 text-xs text-neutral-500">
                                                <span>{{ $product->generic_name }}</span>
                                                @if($product->brand_name)
                                                    <span class="text-neutral-400">• {{ $product->brand_name }}</span>
                                                @endif
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 font-mono text-neutral-600 dark:text-neutral-400">
                                            {{ $basePkg->barcode ?? 'N/A' }}
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-md bg-blue-400/10 px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 ring-1 ring-inset ring-blue-400/20">
                                                {{ $product->category->name ?? 'Uncategorized' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            @php $stock = $product->total_stock ?? 0; @endphp

                                            @if($stock <= 0)
                                                <span class="inline-flex items-center rounded-full bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">
                                                    Out of Stock
                                                </span>
                                            @elseif($stock < $product->reorder_level)
                                                <span class="text-orange-500 font-bold" title="Below Reorder Level ({{ number_format($product->reorder_level) }})">
                                                    {{ number_format($stock, 0) }}
                                                </span>
                                                <span class="text-xs text-neutral-500">{{ $product->baseUnit->abbreviation ?? '' }}</span>
                                            @else
                                                <span class="text-neutral-900 dark:text-white font-medium">{{ number_format($stock, 0) }}</span>
                                                <span class="text-xs text-neutral-500">{{ $product->baseUnit->abbreviation ?? '' }}</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-right text-neutral-900 dark:text-white font-medium">
                                            {{ number_format($basePkg->price ?? 0, 2) }}
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            <x-ui.button size="xs" variant="ghost" icon="pencil-square" href="#">Edit</x-ui.button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-24 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-neutral-100 dark:bg-white/5">
                                                    <x-ui.icon name="magnifying-glass" class="h-5 w-5 text-neutral-400" />
                                                </div>
                                                <h3 class="text-sm font-semibold text-neutral-900 dark:text-white">No products found</h3>
                                                <p class="mt-1 text-sm text-neutral-500">Try adjusting your search or filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center">
                        <x-ui.pagination
                            wire:model.live="perPage"
                            :per-page-options="$perPageOptions"
                            :data="$this->products"
                        />
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
