
<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Pharmacy Products</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Manage medicines and pharmacy stock</p>
        </div>
        <div class="flex items-center gap-3 justify-end">
            <x-ui.button href="{{ route('inventory.pharmacy.products.import') }}" icon="arrow-down-tray">
                Import Products
            </x-ui.button>
            <x-ui.button href="{{ route('inventory.pharmacy.products.create') }}" icon="plus">
                Add Product
            </x-ui.button>
            <x-ui.button href="{{ route('inventory.pharmacy.products.bulk-pricing') }}" icon="currency-dollar">
                Bulk Pricing
            </x-ui.button>
            <x-ui.button variant="outline" icon="clipboard-document-list" wire:click="markSelectedAsSpecialOrder" wire:loading.attr="disabled" wire:target="markSelectedAsSpecialOrder">
                Mark Selected Special Order
            </x-ui.button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

        {{-- 1. Total Products --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <x-ui.icon name="cube" class="size-6 text-blue-600! dark:text-blue-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Total Products</p>
                    <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-400">
                        {{ number_format($this->stats['total']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 2. Out of Stock (Critical) --}}
        {{-- Clicking this filters the table to Out of Stock only --}}
        <x-ui.card hoverless size="full" class="relative border-l-4 border-l-red-500! cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors" wire:click="$toggle('outOfStockOnly')">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                    <x-ui.icon name="x-circle" class="size-6 text-red-600! dark:text-red-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-red-600/80 uppercase tracking-wide">Out of Stock</p>
                    <h3 class="text-2xl font-bold text-red-700 dark:text-red-400">
                        {{ number_format($this->stats['out_of_stock']) }}
                    </h3>
                </div>
            </div>
            @if($this->stats['out_of_stock'] > 0)
            <div class="absolute top-2 right-2 size-2 bg-red-500 rounded-full animate-pulse"></div>
            @endif
        </x-ui.card>

        {{-- 3. Low Stock (Warning) --}}
        {{-- Clicking this filters the table to Low Stock only --}}
        <x-ui.card hoverless size="full" class="relative border-l-4 border-l-orange-500! cursor-pointer hover:bg-orange-50 dark:hover:bg-orange-900/10 transition-colors" wire:click="$toggle('lowStockOnly')">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                    <x-ui.icon name="exclamation-triangle" class="size-6 text-orange-600! dark:text-orange-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-orange-600/80 uppercase tracking-wide">Expiring Soon</p>
                    <h3 class="text-2xl font-bold text-orange-700 dark:text-orange-400">
                        {{ number_format($this->stats['near_expiry']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 4. Near Expiry (Alert) --}}
        <x-ui.card hoverless size="full" class="relative border-l-4 border-l-yellow-400!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                    <x-ui.icon name="clock" class="size-6 text-yellow-600! dark:text-yellow-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-yellow-600/80 uppercase tracking-wide">Low Stock</p>
                    <h3 class="text-2xl font-bold text-yellow-700 dark:text-yellow-400">
                        {{ number_format($this->stats['low_stock']) }}
                    </h3>
                </div>
            </div>
            @if($this->stats['low_stock'] > 0)
            <div class="absolute top-2 right-2 size-2 bg-yellow-500 rounded-full animate-pulse"></div>
            @endif
        </x-ui.card>
    </div>

    <x-ui.card hoverless size="full" class="p-0">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3 w-full md:w-auto">
                <div class="w-full md:w-72">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        leftIcon="magnifying-glass" clearable
                        placeholder="Search name, SKU, brand..."
                        class="w-full"
                    />
                </div>
                <x-inventory.barcode-search :config="$this->scannerConfig" class="hidden lg:inline-flex" />

                {{-- Scan or search a product and jump straight to editing it. --}}
                <x-ui.button
                    variant="outline"
                    color="primary"
                    icon="qr-code"
                    size="sm"
                    class="shrink-0 whitespace-nowrap"
                    wire:click="openLookup('inventory-product-lookup-modal')"
                    title="Scan or search a product to view its details (F2)"
                >
                    Scan / Look up
                </x-ui.button>
            </div>

            <div class="flex items-center gap-3">
                <x-ui.dropdown checkbox checkboxVariant>
                    <x-slot:button>
                        <x-ui.button icon="funnel" variant="soft" size="sm">
                            Filters
                        </x-ui.button>
                    </x-slot:button>

                    <x-slot:menu>
                        <x-ui.dropdown.item wire:model.live="lowStockOnly">
                            Low Stock
                        </x-ui.dropdown.item>
                        <x-ui.dropdown.item wire:model.live="outOfStockOnly">
                            Out of Stock
                        </x-ui.dropdown.item>
                        <x-ui.dropdown.item wire:model.live="requirePrescription">
                            Requires Prescription
                        </x-ui.dropdown.item>
                        <x-ui.dropdown.item wire:model.live="active">
                            Enabled
                        </x-ui.dropdown.item>
                        <x-ui.dropdown.item wire:model.live="disabled">
                            Disabled
                        </x-ui.dropdown.item>
                    </x-slot:menu>
                </x-ui.dropdown>


                <x-ui-select.styled
                    invalidate
                    wire:model.live="productCategories"
                    :options="$this->categories"
                    class="w-full"
                    searchable
                    multiple
                    placeholder="Select categories..."
                />
                <x-ui.button
                    size="sm"
                    variant="outline"
                    icon="arrow-down-tray"
                    wire:click="exportProducts"
                    wire:loading.attr="disabled"
                    wire:target="exportProducts"
                >
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
                                <th class="px-6 py-4"></th>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">Dosage</th>
                                <th class="px-6 py-4">Form</th>
                                <th class="px-6 py-4">Barcode</th>
                                <th class="px-6 py-4">Product Code</th>
                                <th class="px-6 py-4">Product Category</th>
                                {{-- <th class="px-6 py-4 text-right">Expiration Date</th> --}}
                                <th class="px-6 py-4 text-center">Stock Type</th>
                                <th class="px-6 py-4 text-center">Stock</th>
                                <th class="px-6 py-4 text-center">Reorder Level</th>
                                <th class="px-6 py-4 text-center">Requires Prescription</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                {{-- <th class="px-6 py-4 text-right">Cost</th>
                                <th class="px-6 py-4 text-right">Price</th> --}}
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->products as $product)
                            @php
                            $basePkg = $product->productPackagings->where('unit_id', $product->base_unit_id)->first()
                            ?? $product->productPackagings->first();
                            // Since we sorted by expiration_date ASC in the controller, ->first() is the oldest/current batch.
                            $currentBatch = $product->inventoryBatches->first();
                            @endphp

                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4">
                                    <input type="checkbox" wire:model.live="selectedProductIds" value="{{ $product->id }}" class="rounded border-neutral-300 text-electric-blue focus:ring-electric-blue dark:border-white/20 dark:bg-[#060A23]" />
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <x-product.image
                                            :url="$product->imageUrl()"
                                            :alt="$product->brand_name"
                                            size="sm"
                                        />
                                        <div class="min-w-0">
                                            <div class="font-medium text-neutral-900 dark:text-white">{{ $product->brand_name }}</div>
                                            <div class="flex gap-2 text-xs text-neutral-500">
                                                <span>{{ $product->generic_name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-neutral-900 dark:text-white font-medium">{{ $product->dosage ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-neutral-900 dark:text-white font-medium">{{ $product->form ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 font-mono text-neutral-600 dark:text-neutral-400">
                                    {{ $basePkg->barcode ?: 'N/A' }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-blue-400/10 px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 ring-1 ring-inset ring-blue-400/20">
                                        {{ $product->product_code ?? 'N/A' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-md bg-purple-400/10 px-2 py-1 text-xs font-medium text-purple-600 dark:text-purple-400 ring-1 ring-inset ring-purple-400/20">
                                        {{ $product->category->name ?? 'Uncategorized' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if($product->stock_type === \App\Enums\Product\StockType::SpecialOrder)
                                        <span class="inline-flex items-center rounded-md bg-sky-400/10 px-2 py-1 text-xs font-medium text-sky-600 dark:text-sky-400 ring-1 ring-inset ring-sky-400/20">Special Order</span>
                                    @else
                                        <span class="inline-flex items-center rounded-md bg-emerald-400/10 px-2 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400 ring-1 ring-inset ring-emerald-400/20">Regular Stock</span>
                                    @endif
                                </td>

                                {{-- <td class="px-6 py-4 text-right">
                                        @if($currentBatch && $currentBatch->expiration_date)
                                            @php
                                                $expiryDate = \Carbon\Carbon::parse($currentBatch->expiration_date);
                                                $isSoon = $expiryDate->isBefore(now()->addMonths(3));
                                                $expired = $expiryDate->isPast() || $expiryDate->isToday();
                                            @endphp

                                            <div class="flex flex-col items-center">
                                                <span class="text-sm font-medium {{ $expired ? 'text-red-600' : ($isSoon ? 'text-orange-600' : 'text-neutral-900 dark:text-white') }}">
                                {{ $expiryDate->format('M d, Y') }}
                                </span>
                                <span class="text-[10px] uppercase {{ $expired ? 'text-red-500' : ($isSoon ? 'text-orange-500' : 'text-green-500') }}">
                                    {{ $expired ? 'Expired' : ($isSoon ? 'Expiring Soon' : 'Active Batch') }}
                                </span>
                                </div>
                                @else
                                <span class="text-neutral-500 italic">No Active Batches</span>
                                @endif
                                </td> --}}

                                <td class="px-6 py-4 text-center">
                                    @php $stock = $product->total_stock ?? 0; @endphp

                                    @if($product->stock_type === \App\Enums\Product\StockType::SpecialOrder)
                                        <span class="inline-flex items-center rounded-md whitespace-nowrap bg-sky-400/10 px-2 py-1 text-xs font-medium text-sky-600 dark:text-sky-400 ring-1 ring-inset ring-sky-400/20">Order Basis</span>
                                    @elseif($stock <= 0) <span class="inline-flex items-center rounded-md whitespace-nowrap bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">
                                        Out of Stock
                                        </span>
                                        @elseif($stock < $product->reorder_level)
                                            <span class="text-yellow-500 font-bold" title="Below Reorder Level ({{ number_format($product->reorder_level, 2) }})">
                                                {{ number_format($stock, 2) }}
                                            </span>
                                            <span class="text-xs text-neutral-500">{{ $product->baseUnit->abbreviation ?? '' }}</span>
                                            @else
                                            <span class="text-neutral-900 dark:text-white font-medium">{{ number_format($stock, 2) }}</span>
                                            <span class="text-xs text-neutral-500">{{ $product->baseUnit->abbreviation ?? '' }}</span>
                                            @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span class="text-neutral-900 dark:text-white font-medium">{{ number_format($product->reorder_level, 2) }}</span>
                                    <span class="text-xs text-neutral-500">{{ $product->baseUnit->abbreviation ?? '' }}</span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if(!$product->requires_prescription)
                                    <span class="inline-flex items-center rounded-md bg-yellow-400/10 px-2 py-1 text-xs font-medium text-yellow-600 dark:text-yellow-400 ring-1 ring-inset ring-yellow-400/20">
                                        No
                                    </span>
                                    @else
                                    <span class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 ring-1 ring-inset ring-green-400/20">
                                        Yes
                                    </span>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-center">
                                    @if(!$product->is_active)
                                    <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">
                                        Disabled
                                    </span>
                                    @else
                                    <span class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 ring-1 ring-inset ring-green-400/20">
                                        Enabled
                                    </span>
                                    @endif
                                </td>

                                {{-- <td class="px-6 py-4 text-right text-neutral-900 dark:text-white font-medium">
                                        @money($currentBatch?->cost_per_unit)
                                    </td>

                                    <td class="px-6 py-4 text-right text-neutral-900 dark:text-white font-medium">
                                        @money($basePkg->price)
                                    </td> --}}

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-ui.button size="xs" icon="shopping-cart" variant="outline" color="emerald" title="Adjust stock" x-on:click="$dispatch('open-adjust-stock-modal', { id: {{ $product->id }} })" />
                                        <x-ui.button size="xs" icon="pencil-square" variant="outline" color="blue" href="{{ route('inventory.pharmacy.products.edit', ['product' => $product]) }}" title="Edit Product" />
                                        <x-ui.button size="xs" :icon="$product->is_active ? 'eye-slash' : 'eye'" variant="outline" title="Toggle Status" :color="$product->is_active ? 'orange' : 'indigo'" wire:click="toggleStatus({{ $product }})" wire:custom-confirm="Are you sure you want to {{ $product->is_active ? 'disable' : 'enable' }} {{ $product->brand_name }}?" />
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="11" class="px-6 py-24 text-center">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                            <x-ui.icon name="cube" class="size-6" />
                                        </x-ui.empty.media>

                                        <x-ui.empty.contents>
                                            <x-ui.heading>No products found</x-ui.heading>
                                            <x-ui.text class="opacity-70">
                                                You haven't created any products yet.
                                            </x-ui.text>

                                            <div class="flex gap-2">
                                                <x-ui.button size="sm" href="{{ route('inventory.pharmacy.products.import') }}" icon="arrow-down-tray">
                                                    Import Products
                                                </x-ui.button>
                                                <x-ui.button size="sm" href="{{ route('inventory.pharmacy.products.create') }}" icon="plus">
                                                    Add Product
                                                </x-ui.button>
                                            </div>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
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

    {{-- Adjust Stock Modal --}}
    <livewire:inventory.pages.pharmacy.product.adjust-stock-modal wire:model="adjust_product" />

    {{-- Scan / search a product, see its details, then edit it. --}}
    <div
        x-data
        x-on:keydown.window.f2.prevent="$wire.openLookup('inventory-product-lookup-modal')"
        x-on:close-modal.window="if ($event.detail?.id === 'inventory-product-lookup-modal') $wire.closeLookup()"
    >
        <x-product.lookup-modal
            id="inventory-product-lookup-modal"
            heading="Product Lookup"
            description="Scan a barcode or search to view a product's stock and prices."
            :product="$lookupProduct"
            :error="$lookupError"
            :scanner-enabled="$this->scannerConfig['enabled']"
            edit-route="inventory.pharmacy.products.edit"
            shortcut="F2"
        />
    </div>
</div>
