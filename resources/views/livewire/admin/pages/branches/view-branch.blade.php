<div class="max-w-7xl mx-auto space-y-6 p-5">

    {{-- Breadcrumbs --}}
    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('admin.branches') }}">
            Branches
        </x-ui.breadcrumbs.item>
        <x-ui.breadcrumbs.item active>
            {{ $branch->name }}
        </x-ui.breadcrumbs.item>
    </x-ui.breadcrumbs>

    {{-- Header & Controls --}}
    <x-ui.card hoverless size="full" class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 sm:gap-6">

        {{-- Left Side: Title & Address --}}
        <div class="w-full sm:w-auto">
            <div class="flex items-center gap-3 mb-1">
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $branch->name }}</h1>
                @if($branch->is_active)
                <span class="px-2 py-1 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400 uppercase tracking-wider shrink-0">Active</span>
                @else
                <span class="px-2 py-1 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 uppercase tracking-wider shrink-0">Inactive</span>
                @endif
            </div>
            <p class="text-sm text-neutral-500 dark:text-neutral-400 flex items-start sm:items-center gap-1">
                <x-ui.icon name="map-pin" class="size-4 shrink-0 mt-0.5 sm:mt-0" />
                <span class="leading-tight">{{ $branch->address ?? 'No address provided' }}</span>
            </p>
        </div>

        {{-- Right Side: Controls --}}
        <div class="w-full sm:w-auto flex flex-col sm:flex-row items-stretch sm:items-center gap-3">

            {{-- Date Filter for the Overview --}}
            <x-ui.field class="mb-0 w-full sm:w-40">
               <select wire:model.live="dateRange" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-card text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                   <option value="all">All Time</option>
                   <option value="today">Today</option>
                   <option value="yesterday">Yesterday</option>
                   <option value="7days">Last 7 Days</option>
                   <option value="30days">Last 30 Days</option>
                   <option value="this_month">This Month</option>
                   <option value="this_year">This Year</option>
               </select>
            </x-ui.field>

            {{-- The Teleport Action Button --}}
            <x-ui.button class="w-full sm:w-auto justify-center" color="primary" icon="arrow-right-end-on-rectangle" wire:click="manageThisBranch" wire:loading.attr="disabled">
                Manage Branch
            </x-ui.button>

        </div>
    </x-ui.card>

    {{-- Top Level KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Revenue --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Gross Revenue</p>
            <h3 class="text-2xl font-black text-neutral-900 dark:text-white mt-1">@money($this->stats['revenue'])</h3>
            <p class="text-xs text-neutral-400 mt-1">{{ number_format($this->stats['orders_count']) }} transactions</p>
        </x-ui.card>

        {{-- Expenses --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-rose-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Operating Expenses</p>
            <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">@money($this->stats['expenses'])</h3>
            <p class="text-xs text-neutral-400 mt-1">Bills, payroll, etc.</p>
        </x-ui.card>

        {{-- Cash Flow / Profit --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-emerald-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Cash Flow (Net)</p>
            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">@money($this->stats['net_profit'])</h3>
            <p class="text-xs text-neutral-400 mt-1">Revenue minus expenses</p>
        </x-ui.card>

        {{-- Inventory Value --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-indigo-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Inventory Value</p>
            <h3 class="text-2xl font-black text-neutral-900 dark:text-white mt-1">@money($this->inventoryHealth['total_value'])</h3>
            @if($this->inventoryHealth['low_stock_count'] > 0)
            <p class="text-xs text-rose-500 font-bold mt-1 animate-pulse">{{ number_format($this->inventoryHealth['low_stock_count']) }} items low on stock!</p>
            @else
            <p class="text-xs text-neutral-400 mt-1">Stock levels are healthy</p>
            @endif
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-purple-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Products</p>
            <h3 class="text-2xl font-black text-neutral-900 dark:text-white mt-1">{{ number_format($this->productStats['total']) }}</h3>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-yellow-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Low Stock Items</p>
            <h3 class="text-2xl font-black text-yellow-600 dark:text-yellow-400 mt-1">{{ number_format($this->productStats['low_stock']) }}</h3>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-orange-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Near Expiry (90 Days)</p>
            <h3 class="text-2xl font-black text-orange-600 dark:text-orange-400 mt-1">{{ number_format($this->productStats['near_expiry']) }}</h3>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-red-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Expired Batches</p>
            <h3 class="text-2xl font-black text-red-600 dark:text-red-400 mt-1">{{ number_format($this->productStats['expired']) }}</h3>
        </x-ui.card>

    </div>

    {{-- Activity Grids --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Recent Sales --}}
        <div class="space-y-4 flex flex-col">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Recent Sales</h2>
                <x-ui.button size="sm" variant="outline" icon="arrow-down-tray" wire:click="exportSales" wire:loading.attr="disabled" wire:target="exportSales">
                    Export
                </x-ui.button>
            </div>

            <x-ui.card hoverless size="full" class="p-0 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Ref No.</th>
                                <th class="px-4 py-3">Cashier</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5">
                            @forelse($this->recentSales as $sale)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400 font-medium">
                                    {{ $sale->payment_reference ?? 'N/A' }}
                                    <span class="block text-[10px] text-neutral-500">{{ $sale->created_at->format('M d, Y') }}</span>
                                </td>
                                <td class="px-4 py-3 text-neutral-900 dark:text-white">{{ $sale->user->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded bg-neutral-100 dark:bg-white/10 text-[10px] font-bold text-neutral-600 dark:text-neutral-300">
                                        {{ $sale->paymentMethod->name ?? 'Cash' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-neutral-900 dark:text-white">
                                    @money($sale->grand_total)
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="shopping-cart" class="size-6 text-blue-500" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No sales found.</x-ui.heading>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center shrink-0">
                    <x-ui.pagination
                        wire:model.live="perPage"
                        :per-page-options="$perPageOptions"
                        :data="$this->recentSales"
                    />
                </div>
            </x-ui.card>
        </div>

        {{-- Recent Purchase Orders --}}
        <div class="space-y-4 flex flex-col">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Purchase Orders</h2>
                <x-ui.button size="sm" variant="outline" icon="arrow-down-tray" wire:click="exportPurchases" wire:loading.attr="disabled" wire:target="exportPurchases">
                    Export
                </x-ui.button>
            </div>

            <x-ui.card hoverless size="full" class="p-0 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">PO Number</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Cost</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5">
                            @forelse($this->recentPurchases as $po)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-mono text-purple-600 dark:text-purple-400 font-medium">
                                    {{ $po->reference_no ?? 'N/A' }}
                                    <span class="block text-[10px] text-neutral-500">{{ $po->created_at->format('M d, Y') }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider
                                            {{ ($po->status->value === 'completed') ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400' }}">
                                        {{ $po->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-neutral-900 dark:text-white">
                                    @money($po->total_cost)
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.button size="xs" variant="outline" icon="eye" x-on:click="await $wire.set('view_purchase', {{ $po }}, false); $dispatch('open-modal', { id: 'view-purchase' });">
                                        View
                                    </x-ui.button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="document-text" class="size-6 text-green-500" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No purchase orders found.</x-ui.heading>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center shrink-0">
                    <x-ui.pagination
                        wire:model.live="perPage"
                        :per-page-options="$perPageOptions"
                        :data="$this->recentPurchases"
                    />
                </div>
            </x-ui.card>
        </div>

        {{-- ========================================== --}}
        {{-- BRANCH INVENTORY & PRODUCTS MODULE         --}}
        {{-- ========================================== --}}
        <div class="col-span-2 space-y-4">

            <div>
                <h2 class="text-xl font-bold text-neutral-900 dark:text-white">Branch Inventory & Catalog</h2>
                <p class="text-sm text-neutral-500">Manage and monitor stock health specific to this branch.</p>
            </div>

            {{-- Products Table & Filters --}}
            <x-ui.card hoverless size="full" class="p-0">
                <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div class="w-full md:w-72">
                        <x-ui.input wire:model.live.debounce.300ms="search" leftIcon="magnifying-glass" clearable placeholder="Search name, SKU, brand..." class="w-full" />
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.dropdown checkbox checkboxVariant>
                            <x-slot:button>
                                <x-ui.button icon="funnel" variant="soft" size="sm">Filters</x-ui.button>
                            </x-slot:button>
                            <x-slot:menu>
                                <x-ui.dropdown.item wire:model.live="lowStockOnly">Low Stock</x-ui.dropdown.item>
                                <x-ui.dropdown.item wire:model.live="outOfStockOnly">Out of Stock</x-ui.dropdown.item>
                                <x-ui.dropdown.item wire:model.live="nearExpiryOnly">Near Expiry</x-ui.dropdown.item>
                                <x-ui.dropdown.item wire:model.live="expiredOnly">Expired Batches</x-ui.dropdown.item>
                                <x-ui.dropdown.item wire:model.live="requirePrescription">Requires Prescription</x-ui.dropdown.item>
                                <x-ui.dropdown.item wire:model.live="active">Enabled</x-ui.dropdown.item>
                                <x-ui.dropdown.item wire:model.live="disabled">Disabled</x-ui.dropdown.item>
                            </x-slot:menu>
                        </x-ui.dropdown>

                        <div class="w-48">
                            <x-ui-select.styled invalidate wire:model.live="productCategories" :options="$this->categories" class="w-full" searchable multiple placeholder="Select categories..." />
                        </div>

                        <x-ui.button size="sm" variant="outline" icon="arrow-down-tray" wire:click="exportProducts" wire:loading.attr="disabled" wire:target="exportProducts">
                            Export in Excel
                        </x-ui.button>
                    </div>
                </div>

                <div class="w-full">
                    <div class="w-full text-sm text-neutral-300">
                        <div class="w-full overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                        <th class="px-6 py-4">Name</th>
                                        <th class="px-6 py-4">Dosage / Form</th>
                                        <th class="px-6 py-4">Product Code</th>
                                        <th class="px-6 py-4 text-center">Stock</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                        <th class="px-6 py-4 text-right">Earliest Expiry</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                                    @forelse ($this->products as $product)
                                    @php
                                    $currentBatch = $product->inventoryBatches->first(); // Fetches oldest/current due to ordering
                                    $stock = $product->total_stock ?? 0;
                                    @endphp
                                    <tr class="hover:bg-white/5 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-neutral-900 dark:text-white">{{ $product->brand_name }}</div>
                                            <div class="flex gap-2 text-xs text-neutral-500"><span>{{ $product->generic_name }}</span></div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="text-neutral-900 dark:text-white font-medium">{{ $product->dosage ?? '-' }}</span>
                                            <span class="block text-xs text-neutral-500">{{ $product->form ?? '-' }}</span>
                                        </td>

                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-md bg-blue-400/10 px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 ring-1 ring-inset ring-blue-400/20">
                                                {{ $product->product_code ?? 'N/A' }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            @if($stock <= 0) <span class="inline-flex items-center rounded-md whitespace-nowrap bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">
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
                                            @if(!$product->is_active)
                                            <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">Disabled</span>
                                            @else
                                            <span class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 ring-1 ring-inset ring-green-400/20">Enabled</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-right">
                                            @if($currentBatch && $currentBatch->expiration_date)
                                            @php
                                            $expiryDate = \Carbon\Carbon::parse($currentBatch->expiration_date);
                                            $isSoon = $expiryDate->isBefore(now()->addMonths(3));
                                            $expired = $expiryDate->isPast() || $expiryDate->isToday();
                                            @endphp
                                            <div class="flex flex-col items-end">
                                                <span class="text-sm font-medium {{ $expired ? 'text-red-600' : ($isSoon ? 'text-orange-600' : 'text-neutral-900 dark:text-white') }}">
                                                    {{ $expiryDate->format('M d, Y') }}
                                                </span>
                                                <span class="text-[10px] uppercase {{ $expired ? 'text-red-500' : ($isSoon ? 'text-orange-500' : 'text-green-500') }}">
                                                    {{ $expired ? 'Expired' : ($isSoon ? 'Expiring Soon' : 'Healthy') }}
                                                </span>
                                            </div>
                                            @else
                                            <span class="text-neutral-500 italic text-xs">No active batches</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center">
                                            <x-ui.empty>
                                                <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                                    <x-ui.icon name="cube" class="size-6 text-neutral-400" />
                                                </x-ui.empty.media>
                                                <x-ui.empty.contents>
                                                    <x-ui.heading>No products match your filters</x-ui.heading>
                                                    <x-ui.text class="opacity-70">Try adjusting your search or filter settings.</x-ui.text>
                                                </x-ui.empty.contents>
                                            </x-ui.empty>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center w-full">
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

    {{-- View/Print Modal --}}
    <livewire:inventory.pages.pharmacy.purchase.view-purchase-modal wire:model="view_purchase" />
</div>
