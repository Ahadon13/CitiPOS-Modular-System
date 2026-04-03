<div class="max-w-7xl mx-auto space-y-6 p-5">

    {{-- ========================================== --}}
    {{-- HEADER & DYNAMIC FILTERS                   --}}
    {{-- ========================================== --}}
    <x-ui.card size="full" hoverless class="flex justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Overview</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Monitor your entire business ecosystem</p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            {{-- Branch Selector --}}
            <x-ui.field class="mb-0 w-full sm:w-60">
                <select wire:model.live="branchId" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-card text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                    <option value="">All Branches</option>
                    @foreach($this->branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            {{-- Module/Category Selector (The Magic Multi-Functional Filter) --}}
            <x-ui.field class="mb-0 w-full sm:w-60">
                <select wire:model.live="categoryId" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-card text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                    <option value="">All Modules</option>
                    @foreach($this->categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </x-ui.field>

            {{-- Date Range Selector --}}
            <x-ui.field class="mb-0 w-full sm:w-60">
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
        </div>
    </x-ui.card>

    {{-- ========================================== --}}
    {{-- STATS CARDS                                --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- 1. Revenue --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-500/20 rounded-xl">
                    <x-ui.icon name="banknotes" class="size-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Revenue</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                       @money($this->stats['revenue'])
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 2. Gross Profit (NEW) --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-emerald-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-500/20 rounded-xl">
                    <x-ui.icon name="arrow-trending-up" class="size-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Gross Profit</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        @money($this->stats['gross_profit'])
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 3. Profit Margin % (NEW) --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-cyan-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-cyan-100 dark:bg-cyan-500/20 rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6 text-cyan-600 dark:text-cyan-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 14.25L15 9m-l.5-l.5m-3 7.5l.5.5m-3-7.5h.008v.008H9V9zm4.5 4.5h.008v.008H13.5v-.008z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Avg. Gross Margin</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['margin'], 1) }}%
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 4. Total Orders --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-indigo-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-500/20 rounded-xl">
                    <x-ui.icon name="shopping-bag" class="size-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Orders</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['orders']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 5. Active Catalog --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-purple-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-500/20 rounded-xl">
                    <x-ui.icon name="cube" class="size-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Active Products</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['products']) }} <span class="text-sm font-medium text-neutral-400">Items</span>
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 6. Low Stock Alerts --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-orange-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-orange-100 dark:bg-orange-500/20 rounded-xl">
                    <x-ui.icon name="exclamation-triangle" class="size-6 text-orange-600 dark:text-orange-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Low Stock Alerts</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['low_stock']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 7. Near Expiry --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-amber-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-500/20 rounded-xl">
                    <x-ui.icon name="clock" class="size-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Near Expiry</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['near_expiry']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 8. Expired Items --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-rose-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-rose-100 dark:bg-rose-500/20 rounded-xl">
                    <x-ui.icon name="x-circle" class="size-6 text-rose-600 dark:text-rose-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Expired Items</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['expired']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- ========================================== --}}
    {{-- BOTTOM GRIDS: TRANSACTIONS & LEADERBOARD   --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Recent Transactions (Spans 2 columns) --}}
        <div class="lg:col-span-2 space-y-4">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Recent Transactions</h2>

            <x-ui.card size="full" hoverless class="p-0 overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Ref No.</th>
                                <th class="px-4 py-3">Branch</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5">
                            @forelse($this->recentTransactions as $tx)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3 font-mono text-blue-600 dark:text-blue-400 font-medium">{{ $tx->payment_reference ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-neutral-900 dark:text-white">{{ $tx->branch->name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400">{{ $tx->customer->name ?? 'Walk-in' }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded bg-neutral-100 dark:bg-white/10 text-[10px] font-bold text-neutral-600 dark:text-neutral-300">
                                        {{ $tx->paymentMethod->name ?? 'Cash' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-neutral-900 dark:text-white">@money($tx->grand_total)</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-8">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="document-text" class="size-6 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No transactions found</x-ui.heading>
                                            <x-ui.text>There are no sales matching these filters.</x-ui.text>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

        {{-- Top Selling Products (Spans 1 column) --}}
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Top Sellers</h2>

            <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
                <div class="divide-y divide-black/5 dark:divide-white/5">
                    @forelse($this->topSellingProducts as $index => $product)
                    <div class="p-4 flex items-center justify-between hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="size-8 rounded-lg flex items-center justify-center font-black text-sm {{ $index === 0 ? 'bg-amber-100 text-amber-600' : ($index === 1 ? 'bg-slate-200 text-slate-600' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-neutral-100 dark:bg-white/10 text-neutral-500')) }}">
                                #{{ $index + 1 }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-neutral-900 dark:text-white leading-tight">{{ $product->brand_name }}</p>
                                <p class="text-[10px] text-neutral-500 uppercase tracking-wider">{{ $product->category_name }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-neutral-900 dark:text-white">@money(\Money\Money::PHP((string) round((float) $product->total_revenue )))</p>
                            <p class="text-[10px] text-neutral-500">{{ number_format($product->total_sold) }} sold</p>
                        </div>
                    </div>
                    @empty
                    <div class="p-8">
                        <x-ui.empty>
                            <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                <x-ui.icon name="star" class="size-6 text-neutral-400" />
                            </x-ui.empty.media>
                            <x-ui.empty.contents>
                                <x-ui.heading>No data available</x-ui.heading>
                                <x-ui.text>Check back after a few sales.</x-ui.text>
                            </x-ui.empty.contents>
                        </x-ui.empty>
                    </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

    </div>

    {{-- ========================================== --}}
    {{-- PAGINATED TABLES: BRANCHES & PURCHASES     --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Branches Table --}}
        <div class="space-y-4 flex flex-col">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Active Branches</h2>
                <a class="text-blue-500 text-xs" href="{{ route('admin.branches') }}" wire:navigate>
                    View All
                </a>
            </div>

            <x-ui.card size="full" hoverless class="p-0 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Branch Name</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5 dark:bg-[#060A23]">
                            @forelse($this->paginatedBranches as $branch)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-neutral-900 dark:text-white block">{{ $branch->name }}</span>
                                    <span class="text-xs text-neutral-500 truncate max-w-50 block" title="{{ $branch->address }}">{{ $branch->address ?? 'No address' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($branch->is_active)
                                    <span class="px-2 py-1 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400 uppercase tracking-wider">Active</span>
                                    @else
                                    <span class="px-2 py-1 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 uppercase tracking-wider">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.button size="xs" variant="ghost" icon="arrow-right-end-on-rectangle" href="{{ route('admin.branches.view', $branch->id) }}" wire:navigate>
                                        Manage
                                    </x-ui.button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="p-6 text-center text-neutral-500">No branches found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center bg-white dark:bg-card shrink-0">
                    <x-ui.pagination
                        wire:model.live="perPage"
                        :per-page-options="$perPageOptions"
                        :data="$this->paginatedBranches"
                    />
                </div>
            </x-ui.card>
        </div>

        {{-- Purchase Orders Table --}}
        <div class="space-y-4 flex flex-col">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Recent Purchase Orders</h2>

            <x-ui.card size="full" hoverless class="p-0 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">PO Number</th>
                                <th class="px-4 py-3">Branch</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Cost</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5 dark:bg-[#060A23]">
                            @forelse($this->paginatedPurchases as $po)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3 font-mono text-purple-600 dark:text-purple-400 font-medium">
                                    {{ $po->reference_no ?? 'N/A' }}
                                    <span class="block text-[10px] text-neutral-500">{{ $po->created_at->format('M d, Y') }}</span>
                                </td>
                                <td class="px-4 py-3 text-neutral-900 dark:text-white">
                                    {{ $po->branch->name ?? 'Unknown' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider
                                        {{ $po->status === 'completed' ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400' : 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-400' }}">
                                        {{ $po->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-neutral-900 dark:text-white">
                                    @money($po->total_cost)
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.button
                                        size="xs"
                                        variant="outline"
                                        icon="eye"
                                        x-on:click="await $wire.set('view_purchase', {{ $po }}, false); $dispatch('open-modal', { id: 'view-purchase' });"
                                    >
                                        View
                                    </x-ui.button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-8">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="truck" class="size-6 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No Restocks Found</x-ui.heading>
                                            <x-ui.text>There are no purchase orders matching this filter.</x-ui.text>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center bg-white dark:bg-card shrink-0">
                    <x-ui.pagination
                        wire:model.live="perPage"
                        :per-page-options="$perPageOptions"
                        :data="$this->paginatedPurchases"
                    />
                </div>
            </x-ui.card>
        </div>

    </div>

    {{-- ========================================== --}}
    {{-- INVENTORY ALERTS TABLES                    --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Low Stock Products Table --}}
        <div class="space-y-4 flex flex-col">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Low Stock Products</h2>
            <x-ui.card size="full" hoverless class="p-0 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Branch</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3 text-center">Stock</th>
                                <th class="px-4 py-3 text-center">Reorder Lvl</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5 dark:bg-[#060A23]">
                            @forelse($this->lowStockProducts as $item)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-neutral-900 dark:text-white block">{{ $item->brand_name }} - ({{ $item->dosage }})</span>
                                    <span class="text-xs text-neutral-500">{{ $item->generic_name }}</span>
                                </td>
                                <td class="px-4 py-3 text-neutral-500">{{ $item->branch_name }}</td>
                                <td class="px-4 py-3 text-neutral-500">{{ $item->category_name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-center font-bold text-rose-500">{{ (int) $item->total_stock }}</td>
                                <td class="px-4 py-3 text-center text-neutral-500">{{ $item->reorder_level }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="check-circle" class="size-6 text-green-500" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>All stock levels are healthy.</x-ui.heading>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center bg-white dark:bg-card shrink-0">
                   <x-ui.pagination
                        wire:model.live="perPage"
                        :per-page-options="$perPageOptions"
                        :data="$this->lowStockProducts"
                    />
                </div>
            </x-ui.card>
        </div>

        {{-- Near Expiry Batches Table --}}
        <div class="space-y-4 flex flex-col">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Nearing Expiration</h2>
            <x-ui.card size="full" hoverless class="p-0 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Branch</th>
                                <th class="px-4 py-3">Batch No.</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3">Expires On</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5 dark:bg-[#060A23]">
                            @forelse($this->nearExpiryBatches as $batch)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-neutral-900 dark:text-white block">{{ $batch->product->brand_name }} - ({{ $batch->product->dosage }})</span>
                                    <span class="text-xs text-neutral-500">{{ $batch->product->generic_name }}</span>
                                </td>
                                <td class="px-4 py-3 text-neutral-500">{{ $batch->branch->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 font-mono text-neutral-500">{{ $batch->batch_number }}</td>
                                <td class="px-4 py-3 text-center font-bold">{{ number_format($batch->quantity_on_hand, 2) }}</td>
                                <td class="px-4 py-3 font-bold text-amber-600 dark:text-amber-400">
                                    {{ $batch->expiration_date->format('M d, Y') }}
                                    <span class="block text-xs font-normal text-neutral-500">({{ $batch->expiration_date->diffForHumans() }})</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="check-circle" class="size-6 text-green-500" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No items expiring soon.</x-ui.heading>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center bg-white dark:bg-card shrink-0">
                    <x-ui.pagination
                        wire:model.live="perPage"
                        :per-page-options="$perPageOptions"
                        :data="$this->nearExpiryBatches"
                    />
                </div>
            </x-ui.card>
        </div>

        {{-- Expired Batches Table --}}
        <div class="space-y-4 flex flex-col">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Expired Products</h2>
            <x-ui.card size="full" hoverless class="p-0 overflow-hidden flex-1 flex flex-col">
                <div class="overflow-x-auto custom-scrollbar flex-1">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-4 py-3">Product</th>
                                <th class="px-4 py-3">Branch</th>
                                <th class="px-4 py-3">Batch No.</th>
                                <th class="px-4 py-3 text-center">Qty</th>
                                <th class="px-4 py-3">Expired On</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5 dark:bg-[#060A23]">
                            @forelse($this->expiredBatches as $batch)
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-neutral-900 dark:text-white block">{{ $batch->product->brand_name }} - ({{ $batch->product->dosage }})</span>
                                    <span class="text-xs text-neutral-500">{{ $batch->product->generic_name }}</span>
                                </td>
                                <td class="px-4 py-3 text-neutral-500">{{ $batch->branch->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 font-mono text-neutral-500">{{ $batch->batch_number }}</td>
                                <td class="px-4 py-3 text-center font-bold">{{ number_format($batch->quantity_on_hand, 2) }}</td>
                                <td class="px-4 py-3 font-bold text-red-600 dark:text-red-400">
                                    {{ $batch->expiration_date->format('M d, Y') }}
                                    <span class="block text-xs font-normal text-neutral-500">({{ $batch->expiration_date->diffForHumans() }})</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="p-6 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="check-circle" class="size-6 text-green-500" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No expired items found.</x-ui.heading>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center bg-white dark:bg-card shrink-0">
                    <x-ui.pagination
                        wire:model.live="perPage"
                        :per-page-options="$perPageOptions"
                        :data="$this->expiredBatches"
                    />
                </div>
            </x-ui.card>
        </div>

        {{-- Top Pharmacists --}}
        <div class="space-y-4 flex flex-col">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Top Performing Staff</h2>
            <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                            <tr>
                                <th class="px-6 py-3">Rank</th>
                                <th class="px-6 py-3">Staff Name</th>
                                <th class="px-6 py-3 text-center">Transactions Processed</th>
                                <th class="px-6 py-3 text-right">Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/5 dark:divide-white/5  dark:bg-[#060A23]">
                            @forelse($this->topPharmacists as $index => $staff)
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="size-8 rounded-lg flex items-center justify-center font-black text-sm {{ $index === 0 ? 'bg-amber-100 text-amber-600' : ($index === 1 ? 'bg-slate-200 text-slate-600' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-neutral-100 dark:bg-white/10 text-neutral-500')) }}">
                                        #{{ $index + 1 }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-bold text-neutral-900 dark:text-white">
                                    {{ $staff->name }}
                                    <span class="block text-xs font-normal text-neutral-500">{{ $staff->role }}</span>
                                </td>
                                <td class="px-6 py-4 text-center font-medium text-neutral-600 dark:text-neutral-400">
                                    {{ number_format($staff->total_transactions) }} orders
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                    @money(\Money\Money::PHP((int) $staff->total_revenue))
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-8 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="user" class="size-6 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No staff data available.</x-ui.heading>
                                            <x-ui.text>Check back after some transactions have been processed.</x-ui.text>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>

    </div>

    {{-- View/Print Modal --}}
    <livewire:inventory.pages.pharmacy.purchase.view-purchase-modal wire:model="view_purchase" />

</div>
