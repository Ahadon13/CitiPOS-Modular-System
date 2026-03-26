<div class="max-w-7xl mx-auto space-y-6 p-5">

    {{-- ========================================== --}}
    {{-- HEADER & DYNAMIC FILTERS                   --}}
    {{-- ========================================== --}}
    <x-ui.card size="full" hoverless class="flex justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Overview</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Monitor your entire business ecosystem</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
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
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="7days">Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="this_month">This Month</option>
                </select>
            </x-ui.field>
        </div>
    </x-ui.card>

    {{-- ========================================== --}}
    {{-- STATS CARDS                                --}}
    {{-- ========================================== --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">

        {{-- 1. Revenue --}}
        <x-ui.card hoverless class="border-l-4 border-l-blue-500!">
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
        <x-ui.card hoverless class="border-l-4 border-l-emerald-500!">
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
        <x-ui.card hoverless class="border-l-4 border-l-cyan-500!">
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
        <x-ui.card hoverless class="border-l-4 border-l-indigo-500!">
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
        <x-ui.card hoverless class="border-l-4 border-l-purple-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-purple-100 dark:bg-purple-500/20 rounded-xl">
                    <x-ui.icon name="cube" class="size-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Active Catalog</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['products']) }} <span class="text-sm font-medium text-neutral-400">Items</span>
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 6. Low Stock Alerts --}}
        <x-ui.card hoverless class="border-l-4 border-l-rose-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-rose-100 dark:bg-rose-500/20 rounded-xl">
                    <x-ui.icon name="exclamation-triangle" class="size-6 text-rose-600 dark:text-rose-400" />
                </div>
                <div>
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Low Stock Alerts</p>
                    <h3 class="text-2xl font-black text-neutral-900 dark:text-white">
                        {{ number_format($this->stats['low_stock']) }}
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

            <x-ui.card hoverless class="p-0 overflow-hidden">
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
                            <p class="text-sm font-bold text-neutral-900 dark:text-white">₱{{ number_format($product->total_revenue, 2) }}</p>
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
</div>
