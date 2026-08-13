@use('App\Support\MoneyHelper')

<div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 px-3 py-4 sm:p-5">

    {{-- Header & Master Filters --}}
    <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-4 bg-white dark:bg-[#0a1331] p-4 sm:p-5 rounded-2xl border border-black/5 dark:border-white/10 shadow-sm">
        <div class="mb-2 xl:mb-0">
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">Business Reports</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Analyze your financial health and team performance</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 xl:flex xl:items-end gap-3 w-full xl:w-auto">

            {{-- Branch Filter --}}
            <div class="w-full xl:w-48">
                <x-ui.field class="mb-0">
                    <x-ui.label class="text-xs text-neutral-500">Location</x-ui.label>
                    <select wire:model.live="branchId" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-card text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                        <option value="">All Branches</option>
                        @foreach($this->branches as $branch)
                        <option value="{{ $branch['value'] }}">{{ $branch['label'] }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
            </div>

            {{-- Module/Category Filter --}}
            <div class="w-full xl:w-48">
                <x-ui.field class="mb-0">
                    <x-ui.label class="text-xs text-neutral-500">Module / Category</x-ui.label>
                    <select wire:model.live="categoryId" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-card text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                        <option value="">All Modules</option>
                        @foreach($this->categories as $category)
                        <option value="{{ $category['value'] }}">{{ $category['label'] }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
            </div>

            {{-- Date Filter --}}
            <div class="w-full xl:w-64" wire:ignore>
                <x-ui.field class="mb-0">
                    <x-ui.label class="text-xs text-neutral-500">Date Range</x-ui.label>
                    <x-ui-date range wire:model.live="dateRange" format="YYYY-MM-DD" placeholder="Last 30 Days (Default)" />
                </x-ui.field>
            </div>
        </div>
    </div>

    {{--
        Tab strip.

        Driven by Livewire rather than the sheaf tabs component: that component
        marks its root wire:ignore, which would stop these tables ever
        refreshing, and rendering one panel at a time means a filter change
        only runs the queries for the tab you are looking at.
    --}}
    <div class="border-b border-neutral-200 dark:border-white/10">
        <nav class="-mb-px flex gap-6 overflow-x-auto custom-scrollbar" role="tablist">
            @foreach($this->tabs() as $index => $tab)
                <button
                    type="button"
                    role="tab"
                    wire:click="$set('activeTab', '{{ $tab['value'] }}')"
                    wire:loading.attr="disabled"
                    aria-selected="{{ $activeTab === $tab['value'] ? 'true' : 'false' }}"
                    @class([
                        'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-bold transition-colors shrink-0',
                        'border-blue-500 text-blue-600 dark:text-blue-400' => $activeTab === $tab['value'],
                        'border-transparent text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300' => $activeTab !== $tab['value'],
                    ])
                >
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ================= OVERVIEW ================= --}}
    @if($activeTab === 'overview')
        <div class="space-y-4 sm:space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui.card hoverless class="border-l-4 border-l-blue-500!">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Revenue</p>
                    <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">@money($this->financials['revenue'])</h3>
                    <p class="text-xs text-neutral-400 mt-2">Gross incoming cash</p>
                </x-ui.card>

                <x-ui.card hoverless class="border-l-4 border-l-rose-500!">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Expenses</p>
                    <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">@money($this->financials['expenses'])</h3>
                    <p class="text-xs text-neutral-400 mt-2">Operational costs & bills</p>
                </x-ui.card>

                <x-ui.card hoverless class="border-l-4 border-l-amber-500!">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Gross Profit</p>
                            <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">@money($this->financials['gross_profit'])</h3>
                        </div>
                        <span class="px-2 py-1 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded text-xs font-bold">{{ number_format($this->financials['gross_margin'], 1) }}% Margin</span>
                    </div>
                    <p class="text-xs text-neutral-400 mt-2">Revenue minus product costs (COGS)</p>
                </x-ui.card>

                <x-ui.card hoverless class="border-l-4 border-l-emerald-500! bg-emerald-50/30 dark:bg-emerald-900/10">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-500 uppercase tracking-wider">Net Profit</p>
                            <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">@money($this->financials['net_profit'])</h3>
                        </div>
                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded text-xs font-bold">{{ number_format($this->financials['net_margin'], 1) }}% Margin</span>
                    </div>
                    <p class="text-xs text-emerald-600/70 dark:text-emerald-500/70 mt-2 font-medium">True bottom line (Take-home)</p>
                </x-ui.card>
            </div>

            <div class="flex justify-end">
                <x-ui.button wire:click="exportSalesReport" wire:loading.attr="disabled" wire:target="exportSalesReport" icon="arrow-down-tray" variant="outline" size="sm">
                    <span wire:loading.remove wire:target="exportSalesReport">Sales Report (Excel)</span>
                    <span wire:loading wire:target="exportSalesReport">Generating...</span>
                </x-ui.button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <x-ui.card hoverless size="full" class="h-full">
                        <x-ui.heading level="h3" size="sm" class="mb-1 flex items-center gap-2">
                            <x-ui.icon name="chart-bar" class="size-5 text-blue-500" />
                            Revenue vs. Expenses Trend
                        </x-ui.heading>
                        <p class="text-xs text-neutral-500 mb-5">{{ $this->trendChartData['granularity'] }} totals for the selected range</p>

                        <div wire:ignore>
                            <x-ui.chart.line-chart
                                :categories="$this->trendChartData['categories']"
                                :data="$this->trendChartData['data']"
                                :names="['Revenue', 'Expenses']"
                                dispatch_name="update-trend-chart"
                                :enable_tool_tip="true"
                                height="350" />
                        </div>
                    </x-ui.card>
                </div>

                <div class="lg:col-span-1">
                    <x-ui.card hoverless size="full" class="h-full flex flex-col">
                        <x-ui.heading level="h3" size="sm" class="mb-6 flex items-center gap-2">
                            <x-ui.icon name="chart-pie" class="size-5 text-rose-500" />
                            Expense Distribution
                        </x-ui.heading>

                        @if(count($this->expensePieData['labels']) > 0)
                            <div class="flex-1 flex items-center justify-center overflow-hidden" wire:ignore>
                                <x-ui.chart.pie-chart :width="300" :labels="$this->expensePieData['labels']" :series="$this->expensePieData['series']" dispatch_name="update-pie-chart" :enable_tool_tip="true" />
                            </div>
                        @else
                            <div class="flex-1 flex items-center justify-center py-12">
                                <x-ui.empty>
                                    <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                        <x-ui.icon name="banknotes" class="size-6 text-neutral-400" />
                                    </x-ui.empty.media>
                                    <x-ui.empty.contents>
                                        <x-ui.text>No expenses recorded in this period.</x-ui.text>
                                    </x-ui.empty.contents>
                                </x-ui.empty>
                            </div>
                        @endif
                    </x-ui.card>
                </div>
            </div>

            <x-ui.card hoverless size="full" class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/20 dark:to-[#0a1331] border-indigo-100 dark:border-indigo-900/30">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-500/20 rounded-xl">
                        <x-ui.icon name="archive-box" class="size-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Value on Shelves</p>
                        <h3 class="text-2xl font-black text-indigo-900 dark:text-indigo-400 mt-1">@money($this->inventorySnapshot['total_value'])</h3>
                        <p class="text-xs text-neutral-500 mt-1">Capital currently tied up in physical stock, at supplier cost.</p>
                    </div>
                </div>
            </x-ui.card>
        </div>
    @endif

    {{-- ================= PARTNERSHIPS ================= --}}
    @if($activeTab === 'partnerships')
        <div class="space-y-4 sm:space-y-6">

            {{-- Partnership KPIs --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui.card hoverless class="border-l-4 border-l-blue-500!">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Partnership Revenue</p>
                    <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">@money($this->partnershipSummary['revenue'])</h3>
                    <p class="text-xs text-neutral-400 mt-2">Charged at partner prices</p>
                </x-ui.card>

                <x-ui.card hoverless class="border-l-4 border-l-neutral-400!">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Value at Regular Price</p>
                    <h3 class="text-2xl font-black text-neutral-700 dark:text-neutral-200 mt-1">@money($this->partnershipSummary['regular_value'])</h3>
                    <p class="text-xs text-neutral-400 mt-2">What the same items normally cost</p>
                </x-ui.card>

                <x-ui.card hoverless class="border-l-4 border-l-amber-500!">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Partner Savings Given</p>
                    <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">@money($this->partnershipSummary['savings'])</h3>
                    <p class="text-xs text-neutral-400 mt-2">Total discount extended to partners</p>
                </x-ui.card>

                <x-ui.card hoverless class="border-l-4 border-l-emerald-500!">
                    <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Share of Sales</p>
                    <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($this->priceSourceMix['partnership_share'], 1) }}%</h3>
                    <p class="text-xs text-neutral-400 mt-2">{{ $this->partnershipSummary['partners'] }} partner(s), {{ number_format($this->partnershipSummary['lines']) }} line(s)</p>
                </x-ui.card>
            </div>

            {{-- Partnership sales trend: one line per partner --}}
            <x-ui.card hoverless size="full">
                <x-ui.heading level="h3" size="sm" class="mb-1 flex items-center gap-2">
                    <x-ui.icon name="presentation-chart-line" class="size-5 text-blue-500" />
                    Partnership Sales Trend
                </x-ui.heading>
                <p class="text-xs text-neutral-500 mb-5">
                    {{ $this->partnershipTrend['granularity'] }} sales per partner
                    @if($this->partnershipTrend['has_other'])
                        &middot; partners beyond the top 8 are grouped as &ldquo;Other partners&rdquo;
                    @endif
                </p>

                @if(count($this->partnershipTrend['series']) > 0)
                    <div wire:ignore>
                        <x-ui.chart.line-chart
                            :categories="$this->partnershipTrend['labels']"
                            :series="$this->partnershipTrend['series']"
                            :colors="$this->partnershipPalette['light']"
                            :dark-colors="$this->partnershipPalette['dark']"
                            dispatch_name="update-partnership-trend-chart"
                            :enable_tool_tip="true"
                            height="360" />
                    </div>
                @else
                    <div class="py-12">
                        <x-ui.empty>
                            <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                <x-ui.icon name="user-group" class="size-6 text-neutral-400" />
                            </x-ui.empty.media>
                            <x-ui.empty.contents>
                                <x-ui.text>No partnership-priced sales in this period.</x-ui.text>
                            </x-ui.empty.contents>
                        </x-ui.empty>
                    </div>
                @endif
            </x-ui.card>

            {{-- Per-partner summary --}}
            <x-ui.card hoverless size="full" class="p-0">
                <div class="px-3 sm:px-6 py-4 border-b border-black/10 dark:border-white/10">
                    <h2 class="text-base font-bold text-neutral-900 dark:text-white">Summary by Partner</h2>
                </div>
                <div class="w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                            <tr>
                                <th class="px-6 py-4">Partner (Customer Type)</th>
                                <th class="px-6 py-4 text-center">Orders</th>
                                <th class="px-6 py-4 text-center">Qty</th>
                                <th class="px-6 py-4 text-right">Revenue</th>
                                <th class="px-6 py-4 text-right">Savings Given</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                            @forelse($this->partnershipByPartner as $partner)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 font-bold text-neutral-900 dark:text-white">{{ $partner->partner_name ?? 'Unknown partner' }}</td>
                                    <td class="px-6 py-4 text-center">{{ number_format((int) $partner->orders) }}</td>
                                    <td class="px-6 py-4 text-center">{{ rtrim(rtrim(number_format((float) $partner->quantity, 2), '0'), '.') }}</td>
                                    <td class="px-6 py-4 text-right font-semibold">{{ MoneyHelper::formatCents($partner->revenue) }}</td>
                                    <td class="px-6 py-4 text-right text-amber-600 dark:text-amber-400 font-semibold">{{ MoneyHelper::formatCents($partner->savings) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-neutral-500">No partners sold to in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            {{-- Detailed partnership transactions --}}
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Partnership Transactions</h2>
                        <p class="text-xs text-neutral-500">Each line shows the partner price actually charged next to the regular price it replaced.</p>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.button wire:click="exportPartnershipSales" wire:loading.attr="disabled" wire:target="exportPartnershipSales" icon="table-cells" variant="outline" size="sm">
                            <span wire:loading.remove wire:target="exportPartnershipSales">Excel</span>
                            <span wire:loading wire:target="exportPartnershipSales">...</span>
                        </x-ui.button>
                        <x-ui.button wire:click="exportPartnershipSalesPdf" wire:loading.attr="disabled" wire:target="exportPartnershipSalesPdf" icon="document-arrow-down" variant="outline" size="sm">
                            <span wire:loading.remove wire:target="exportPartnershipSalesPdf">PDF</span>
                            <span wire:loading wire:target="exportPartnershipSalesPdf">...</span>
                        </x-ui.button>
                    </div>
                </div>

                <x-ui.card hoverless size="full" class="p-0">
                    <div class="px-3 sm:px-6 py-4 sm:py-5 border-b border-black/10 dark:border-white/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div class="w-full md:w-80">
                            <x-ui.input wire:model.live.debounce.300ms="search" leftIcon="magnifying-glass" clearable placeholder="Search product or customer..." class="w-full" />
                        </div>
                        <div class="w-full sm:w-64">
                            <select wire:model.live="customerTypeId" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-[#0a1331] text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                                <option value="">All Partners</option>
                                @foreach($this->customerTypes as $type)
                                    <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="w-full overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                                <tr>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Sale</th>
                                    <th class="px-6 py-4">Partner / Customer</th>
                                    <th class="px-6 py-4">Product</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">Regular</th>
                                    <th class="px-6 py-4 text-right">Partner Price</th>
                                    <th class="px-6 py-4 text-right">Line Total</th>
                                    <th class="px-6 py-4 text-right">Saved</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                                @forelse($this->partnershipItems as $item)
                                    <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-neutral-900 dark:text-white">{{ \Illuminate\Support\Carbon::parse($item->sold_at)->format('M d, Y') }}</div>
                                            <div class="text-xs text-neutral-500">{{ \Illuminate\Support\Carbon::parse($item->sold_at)->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-neutral-700 dark:text-neutral-200">#{{ $item->sale_id }}</div>
                                            <div class="text-xs text-neutral-500">{{ $item->branch_name }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                {{ $item->partner_name ?? 'Unknown' }}
                                            </span>
                                            <div class="text-xs text-neutral-500 mt-1">{{ $item->customer_name ?? 'Walk-in' }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-neutral-900 dark:text-white">{{ $item->brand_name ?: ($item->product_name ?: '-') }}</div>
                                            <div class="text-xs text-neutral-500">{{ $item->generic_name ?: $item->product_code }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}
                                            <span class="text-xs text-neutral-500">{{ $item->unit_abbreviation ?? '' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right text-neutral-500 line-through">{{ MoneyHelper::formatCents($item->regular_price_at_moment ?? $item->price_at_moment) }}</td>
                                        <td class="px-6 py-4 text-right font-bold text-blue-600 dark:text-blue-400">{{ MoneyHelper::formatCents($item->price_at_moment) }}</td>
                                        <td class="px-6 py-4 text-right font-semibold">{{ MoneyHelper::formatCents($item->subtotal) }}</td>
                                        <td class="px-6 py-4 text-right text-amber-600 dark:text-amber-400 font-semibold">{{ MoneyHelper::formatCents($item->partner_savings) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-12 text-center text-neutral-500">
                                            <x-ui.empty>
                                                <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                                    <x-ui.icon name="user-group" class="size-6 text-neutral-400" />
                                                </x-ui.empty.media>
                                                <x-ui.empty.contents>
                                                    <x-ui.text>No partnership-priced transactions found for these filters.</x-ui.text>
                                                </x-ui.empty.contents>
                                            </x-ui.empty>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center w-full">
                        <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->partnershipItems" />
                    </div>
                </x-ui.card>
            </div>
        </div>
    @endif

    {{-- ================= BRANCHES & PAYMENTS ================= --}}
    @if($activeTab === 'branches')
        <div class="space-y-4 sm:space-y-6">
            <x-ui.card hoverless size="full">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 mb-1">
                    <div>
                        <x-ui.heading level="h3" size="sm" class="flex items-center gap-2">
                            <x-ui.icon name="building-storefront" class="size-5 text-blue-500" />
                            Branch Sales Trend
                        </x-ui.heading>
                        <p class="text-xs text-neutral-500 mt-1">
                            {{ $this->branchTrend['granularity'] }} revenue per branch
                            @if($this->branchTrend['has_other'])
                                &middot; beyond the top 8 grouped as &ldquo;Other branches&rdquo;
                            @endif
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.button wire:click="exportBranchPerformance" wire:loading.attr="disabled" wire:target="exportBranchPerformance" icon="table-cells" variant="outline" size="sm">
                            <span wire:loading.remove wire:target="exportBranchPerformance">Excel</span>
                            <span wire:loading wire:target="exportBranchPerformance">...</span>
                        </x-ui.button>
                        <x-ui.button wire:click="exportBranchPerformancePdf" wire:loading.attr="disabled" wire:target="exportBranchPerformancePdf" icon="document-arrow-down" variant="outline" size="sm">
                            <span wire:loading.remove wire:target="exportBranchPerformancePdf">PDF</span>
                            <span wire:loading wire:target="exportBranchPerformancePdf">...</span>
                        </x-ui.button>
                    </div>
                </div>

                @if(count($this->branchTrend['series']) > 0)
                    <div class="mt-4" wire:ignore>
                        <x-ui.chart.line-chart
                            :categories="$this->branchTrend['labels']"
                            :series="$this->branchTrend['series']"
                            :colors="$this->branchPalette['light']"
                            :dark-colors="$this->branchPalette['dark']"
                            dispatch_name="update-branch-trend-chart"
                            :enable_tool_tip="true"
                            height="360" />
                    </div>
                @else
                    <div class="py-12">
                        <x-ui.empty>
                            <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                <x-ui.icon name="building-storefront" class="size-6 text-neutral-400" />
                            </x-ui.empty.media>
                            <x-ui.empty.contents>
                                <x-ui.text>No completed sales in this period.</x-ui.text>
                            </x-ui.empty.contents>
                        </x-ui.empty>
                    </div>
                @endif
            </x-ui.card>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <x-ui.card hoverless size="full" class="p-0 h-full">
                        <div class="px-3 sm:px-6 py-4 border-b border-black/10 dark:border-white/10">
                            <h2 class="text-base font-bold text-neutral-900 dark:text-white">Branch Performance</h2>
                        </div>
                        <div class="w-full overflow-x-auto custom-scrollbar">
                            <table class="w-full text-left text-sm whitespace-nowrap">
                                <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                                    <tr>
                                        <th class="px-6 py-4">Branch</th>
                                        <th class="px-6 py-4">Module</th>
                                        <th class="px-6 py-4 text-center">Orders</th>
                                        <th class="px-6 py-4 text-right">Revenue</th>
                                        <th class="px-6 py-4 text-right">Discounts</th>
                                        <th class="px-6 py-4 text-right">Avg. Order</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                                    @forelse($this->branchPerformance as $row)
                                        <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                                            <td class="px-6 py-4 font-bold text-neutral-900 dark:text-white">{{ $row->branch_name }}</td>
                                            <td class="px-6 py-4 text-xs text-neutral-500">{{ $row->module ?? '-' }}</td>
                                            <td class="px-6 py-4 text-center">{{ number_format((int) $row->orders) }}</td>
                                            <td class="px-6 py-4 text-right font-semibold">{{ MoneyHelper::formatCents($row->revenue) }}</td>
                                            <td class="px-6 py-4 text-right text-neutral-500">{{ MoneyHelper::formatCents($row->discounts) }}</td>
                                            <td class="px-6 py-4 text-right">{{ MoneyHelper::formatCents((int) $row->orders > 0 ? (float) $row->revenue / (int) $row->orders : 0) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-6 py-10 text-center text-neutral-500">No sales in this period.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-ui.card>
                </div>

                <div class="lg:col-span-1 space-y-6">
                    <x-ui.card hoverless size="full" class="p-0">
                        <div class="px-4 py-4 border-b border-black/10 dark:border-white/10">
                            <h2 class="text-base font-bold text-neutral-900 dark:text-white">Payment Reconciliation</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">What the drawer should hold, per method</p>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                                <tr>
                                    <th class="px-4 py-3">Method</th>
                                    <th class="px-4 py-3 text-center">Orders</th>
                                    <th class="px-4 py-3 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                                @forelse($this->paymentMix['rows'] as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-neutral-900 dark:text-white">{{ $row['label'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ number_format($row['orders']) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">{{ MoneyHelper::formatCents($row['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-neutral-500">No payments recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </x-ui.card>

                    <x-ui.card hoverless size="full" class="p-0">
                        <div class="px-4 py-4 border-b border-black/10 dark:border-white/10">
                            <h2 class="text-base font-bold text-neutral-900 dark:text-white">Sale Status</h2>
                            <p class="text-xs text-neutral-500 mt-0.5">Pending special orders and non-completed sales</p>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                                <tr>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-center">Orders</th>
                                    <th class="px-4 py-3 text-right">Value</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                                @forelse($this->statusBreakdown as $row)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold capitalize text-neutral-900 dark:text-white">{{ $row['status'] }}</td>
                                        <td class="px-4 py-3 text-center">{{ number_format($row['orders']) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold">{{ MoneyHelper::formatCents($row['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-neutral-500">No sales recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </x-ui.card>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= CASHIERS ================= --}}
    @if($activeTab === 'cashiers')
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Cashier Performance</h2>
                    <p class="text-xs text-neutral-500">Who rang up what, including the discounts they applied.</p>
                </div>
                <div class="flex gap-2">
                    <x-ui.button wire:click="exportCashierPerformance" wire:loading.attr="disabled" wire:target="exportCashierPerformance" icon="table-cells" variant="outline" size="sm">
                        <span wire:loading.remove wire:target="exportCashierPerformance">Excel</span>
                        <span wire:loading wire:target="exportCashierPerformance">...</span>
                    </x-ui.button>
                    <x-ui.button wire:click="exportCashierPerformancePdf" wire:loading.attr="disabled" wire:target="exportCashierPerformancePdf" icon="document-arrow-down" variant="outline" size="sm">
                        <span wire:loading.remove wire:target="exportCashierPerformancePdf">PDF</span>
                        <span wire:loading wire:target="exportCashierPerformancePdf">...</span>
                    </x-ui.button>
                </div>
            </div>

            <x-ui.card hoverless size="full" class="p-0">
                <div class="w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                            <tr>
                                <th class="px-6 py-4">Cashier</th>
                                <th class="px-6 py-4">Branch</th>
                                <th class="px-6 py-4 text-center">Orders</th>
                                <th class="px-6 py-4 text-right">Revenue</th>
                                <th class="px-6 py-4 text-right">Discounts Given</th>
                                <th class="px-6 py-4 text-right">Avg. Order</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                            @forelse($this->cashierPerformance as $row)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 font-bold text-neutral-900 dark:text-white">{{ $row->cashier_name }}</td>
                                    <td class="px-6 py-4 text-xs text-neutral-500">{{ $row->branch_name }}</td>
                                    <td class="px-6 py-4 text-center">{{ number_format((int) $row->orders) }}</td>
                                    <td class="px-6 py-4 text-right font-semibold">{{ MoneyHelper::formatCents($row->revenue) }}</td>
                                    <td class="px-6 py-4 text-right text-amber-600 dark:text-amber-400">{{ MoneyHelper::formatCents($row->discounts) }}</td>
                                    <td class="px-6 py-4 text-right">{{ MoneyHelper::formatCents($row->average_order) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                        <x-ui.empty>
                                            <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                                <x-ui.icon name="users" class="size-6 text-neutral-400" />
                                            </x-ui.empty.media>
                                            <x-ui.empty.contents>
                                                <x-ui.text>No cashier activity in this period.</x-ui.text>
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
    @endif

    {{-- ================= PRODUCTS ================= --}}
    @if($activeTab === 'products')
        <div class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Top Products by Revenue</h2>
                <p class="text-xs text-neutral-500">Best sellers for the selected filters, with the margin each one earned.</p>
            </div>

            <x-ui.card hoverless size="full" class="p-0">
                <div class="w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                            <tr>
                                <th class="px-6 py-4">#</th>
                                <th class="px-6 py-4">Product</th>
                                <th class="px-6 py-4">Module</th>
                                <th class="px-6 py-4 text-center">Qty Sold</th>
                                <th class="px-6 py-4 text-right">Revenue</th>
                                <th class="px-6 py-4 text-right">COGS</th>
                                <th class="px-6 py-4 text-right">Gross Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                            @forelse($this->topProducts as $index => $row)
                                @php($revenue = (int) round((float) $row->revenue))
                                @php($cogs = (int) round((float) $row->cogs))
                                <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                                    <td class="px-6 py-4 text-neutral-400 font-bold">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-neutral-900 dark:text-white">{{ $row->brand_name ?: ($row->product_name ?: '-') }}</div>
                                        <div class="text-xs text-neutral-500">{{ $row->product_code }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-neutral-500">{{ $row->module ?? '-' }}</td>
                                    <td class="px-6 py-4 text-center">{{ rtrim(rtrim(number_format((float) $row->quantity, 2), '0'), '.') }}</td>
                                    <td class="px-6 py-4 text-right font-semibold">{{ MoneyHelper::formatCents($revenue) }}</td>
                                    <td class="px-6 py-4 text-right text-neutral-500">{{ MoneyHelper::formatCents($cogs) }}</td>
                                    <td class="px-6 py-4 text-right font-bold {{ $revenue - $cogs >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ MoneyHelper::formatCents($revenue - $cogs) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-neutral-500">
                                        <x-ui.empty>
                                            <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                                <x-ui.icon name="cube" class="size-6 text-neutral-400" />
                                            </x-ui.empty.media>
                                            <x-ui.empty.contents>
                                                <x-ui.text>No products sold in this period.</x-ui.text>
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
    @endif

    {{-- ================= STOCK LEDGER ================= --}}
    @if($activeTab === 'ledger')
        <div class="space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Stock Movement Ledger (In &amp; Out)</h2>
                <x-ui.button wire:click="exportLedger" wire:loading.attr="disabled" wire:target="exportLedger" icon="arrow-down-tray" variant="outline" size="sm" class="w-full sm:w-auto justify-center">
                    <span wire:loading.remove wire:target="exportLedger">Export</span>
                    <span wire:loading wire:target="exportLedger">Generating...</span>
                </x-ui.button>
            </div>

            <x-ui.card hoverless size="full" class="p-0">
                <div class="px-3 sm:px-6 py-4 sm:py-5 border-b border-black/10 dark:border-white/10 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div class="w-full md:w-80">
                        <x-ui.input wire:model.live.debounce.300ms="search" leftIcon="magnifying-glass" clearable placeholder="Search products..." class="w-full" />
                    </div>

                    <div class="w-full sm:w-64">
                        <select wire:model.live="transactionType" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-[#0a1331] text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                            <option value="">All Movement Types</option>
                            @foreach($this->transactionTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                            <tr>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Product Details</th>
                                <th class="px-6 py-4">Type</th>
                                <th class="px-6 py-4 text-center">Qty Change</th>
                                <th class="px-6 py-4 text-center">Running Bal.</th>
                                <th class="px-6 py-4">Branch / User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                            @forelse ($this->stockMovements as $tx)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-neutral-900 dark:text-white">{{ $tx->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-neutral-500">{{ $tx->created_at->format('h:i A') }}</div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="font-bold text-neutral-900 dark:text-white">
                                        {{ $tx->product->brand_name ?? $tx->product->name ?? $tx->product->product_code ?? 'Unknown product' }}
                                    </div>
                                    <div class="text-xs text-neutral-500">
                                        @if($tx->product->generic_name || $tx->product->dosage || $tx->product->form)
                                            {{ trim(($tx->product->generic_name ?? '') . ' ' . (($tx->product->dosage ?? '') ? "- {$tx->product->dosage}" : '') . ' ' . (($tx->product->form ?? '') ? "({$tx->product->form})" : '')) }}
                                        @else
                                            {{ $tx->product->product_code ?? 'No product code' }}
                                        @endif
                                    </div>
                                    <div class="text-xs text-neutral-500 mt-0.5">
                                        {{ $tx->product->category->name ?? 'Uncategorized' }}
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider {{ $tx->type->colorBadge() }}">
                                        {{ $tx->type->label() }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span class="font-black {{ $tx->type->isAddition() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                        {{ $tx->type->isAddition() ? '+' : '' }}{{ rtrim(rtrim($tx->quantity, '0'), '.') }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center font-bold text-neutral-700 dark:text-neutral-300">
                                    {{ rtrim(rtrim($tx->running_balance, '0'), '.') }}
                                </td>

                                <td class="px-6 py-4">
                                    <div class="text-neutral-900 dark:text-white text-xs font-semibold">{{ $tx->branch->name }}</div>
                                    <div class="text-[10px] text-neutral-500">By: {{ $tx->user->name }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="archive-box-x-mark" class="size-6 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.text>No stock movements found for the selected filters.</x-ui.text>
                                        </x-ui.empty.contents>
                                    </x-ui.empty>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center w-full">
                    <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->stockMovements" />
                </div>
            </x-ui.card>
        </div>
    @endif
</div>
