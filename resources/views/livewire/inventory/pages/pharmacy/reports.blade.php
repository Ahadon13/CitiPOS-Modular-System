<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header & Date Filter --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white dark:bg-[#0a1331] p-5 rounded-2xl border border-black/5 dark:border-white/10 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Business Reports</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Analyze your financial health and team performance</p>
        </div>
        <div class="w-full md:w-72">
            <x-ui.field class="mb-0">
                <x-ui.label class="sr-only">Date Range</x-ui.label>
                <x-ui-date range wire:model.live="dateRange" format="YYYY-MM-DD" placeholder="Select date range" />
            </x-ui.field>
        </div>
    </div>

    {{-- Financial KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Revenue --}}
        <x-ui.card hoverless class="border-l-4 border-l-blue-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Revenue</p>
            <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1">@money($this->financials['revenue'])</h3>
            <p class="text-xs text-neutral-400 mt-2">Gross incoming cash</p>
        </x-ui.card>

        {{-- Expenses --}}
        <x-ui.card hoverless class="border-l-4 border-l-rose-500!">
            <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Expenses</p>
            <h3 class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">@money($this->financials['expenses'])</h3>
            <p class="text-xs text-neutral-400 mt-2">Operational costs & bills</p>
        </x-ui.card>

        {{-- Gross Profit --}}
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

        {{-- NET PROFIT --}}
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

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Trend Line Chart --}}
        <div class="lg:col-span-2">
            <x-ui.card hoverless size="full" class="h-full">
                <x-ui.heading level="h3" size="sm" class="mb-6 flex items-center gap-2">
                    <x-ui.icon name="chart-bar" class="size-5 text-blue-500" />
                    Revenue vs. Expenses Trend
                </x-ui.heading>

                {{-- Replace 'x-charts.line' with whatever you named your Line Chart component --}}
                <x-ui.chart.line-chart :categories="$this->trendChartData['categories']" :data="$this->trendChartData['data']" :names="['Revenue', 'Expenses']" dispatch_name="update-trend-chart" :enable_tool_tip="true" height="350" />
            </x-ui.card>
        </div>

        {{-- Expense Category Pie Chart --}}
        <div class="lg:col-span-1">
            <x-ui.card hoverless size="full" class="h-full flex flex-col">
                <x-ui.heading level="h3" size="sm" class="mb-6 flex items-center gap-2">
                    <x-ui.icon name="chart-pie" class="size-5 text-rose-500" />
                    Expense Distribution
                </x-ui.heading>

                @if(count($this->expensePieData['labels']) > 0)
                <div class="flex-1 flex items-center justify-center">
                    {{-- Replace 'x-charts.pie' with whatever you named your Pie Chart component --}}
                    <x-ui.chart.pie-chart :width="350" :labels="$this->expensePieData['labels']" :series="$this->expensePieData['series']" dispatch_name="update-pie-chart" :enable_tool_tip="true" />
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

    {{-- Bottom Data Grids --}}
    <div class="grid grid-cols-1 gap-6">
        {{-- Inventory Snapshot --}}
        <div class="space-y-4">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Current Inventory Snapshot</h2>
            <x-ui.card hoverless size="full" class="bg-gradient-to-br from-indigo-50 to-white dark:from-indigo-900/20 dark:to-[#0a1331] border-indigo-100 dark:border-indigo-900/30">
                <div class="flex items-center gap-4 mb-4">
                    <div class="p-3 bg-indigo-100 dark:bg-indigo-500/20 rounded-xl">
                        <x-ui.icon name="archive-box" class="size-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Total Value on Shelves</p>
                        <h3 class="text-2xl font-black text-indigo-900 dark:text-indigo-400 mt-1">
                            @money($this->inventorySnapshot['total_value'])
                        </h3>
                    </div>
                </div>
                <div class="pt-4 border-t border-indigo-100 dark:border-indigo-900/30 text-xs text-neutral-500 leading-relaxed">
                    This represents the total capital currently tied up in physical stock inside the pharmacy, based on supplier cost.
                </div>
                <div class="mt-4 pt-4 border-t border-indigo-100 dark:border-indigo-900/30">
                    <x-ui.button variant="outline" class="w-full justify-center" href="{{ route('inventory.pharmacy.stocks') }}">
                        View Stock Ledger &rarr;
                    </x-ui.button>
                </div>
            </x-ui.card>
        </div>

        {{-- Selling Price Comparison --}}
        <x-ui.card hoverless size="full" class="overflow-hidden p-0 border-blue-500/30">
            <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 bg-blue-50/30 dark:bg-blue-900/10 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                    <div>
                        <x-ui.heading level="h3" size="sm" class="text-blue-700 dark:text-blue-400 flex items-center gap-2">
                            <x-ui.icon name="currency-dollar" class="size-5" />
                            Selling Price Comparison
                        </x-ui.heading>
                        <p class="text-sm text-neutral-500 mt-1">Compare regular packaging prices against customer type partnership prices.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button size="sm" variant="outline" icon="x-mark" wire:click="clearSellingPriceFilters">
                            Clear
                        </x-ui.button>
                        <x-ui.button
                            size="sm"
                            variant="outline"
                            icon="arrow-down-tray"
                            wire:click="exportSellingPrices"
                            wire:loading.attr="disabled"
                            wire:target="exportSellingPrices"
                        >
                            Export Excel
                        </x-ui.button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <x-ui.field>
                        <x-ui.label>Search</x-ui.label>
                        <x-ui.input wire:model.live.debounce.300ms="sellingPriceSearch" clearable leftIcon="magnifying-glass" placeholder="Product, code, barcode..." />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Customer Type</x-ui.label>
                        <x-ui-select.styled
                            wire:model.live="sellingPriceCustomerTypeFilter"
                            placeholder="All customer types"
                            :options="$this->sellingPriceCustomerTypeOptions"
                            select="label:label|value:value"
                        />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Product Category</x-ui.label>
                        <x-ui-select.styled
                            wire:model.live="sellingPriceCategoryFilter"
                            placeholder="All categories"
                            :options="$this->sellingPriceCategoryOptions"
                            select="label:label|value:value"
                        />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Partnership Only</x-ui.label>
                        <label class="flex h-10 items-center gap-2 rounded-lg border border-black/10 dark:border-white/10 px-3 text-sm text-neutral-700 dark:text-neutral-300">
                            <input type="checkbox" wire:model.live="sellingPriceOnlyPartnership" class="rounded border-neutral-300 text-blue-600 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800">
                            Show rows with partnership prices
                        </label>
                    </x-ui.field>
                </div>
            </div>

            <div class="w-full">
                <div class="w-full text-sm text-neutral-300">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    <th class="px-6 py-4">Product</th>
                                    <th class="px-6 py-4">Packaging</th>
                                    <th class="px-6 py-4">Barcode</th>
                                    <th class="px-6 py-4 text-right">Regular Price</th>
                                    <th class="px-6 py-4">Customer Type</th>
                                    <th class="px-6 py-4 text-right">Partnership Price</th>
                                    <th class="px-6 py-4 text-right">Difference</th>
                                    <th class="px-6 py-4">Updated</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                                @forelse($this->sellingPrices as $packaging)
                                    @php
                                        $partnerships = $packaging->partnerships;
                                        $regularCents = (int) $packaging->getRawOriginal('price');
                                    @endphp

                                    @if($partnerships->isEmpty())
                                        <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-medium text-black dark:text-white">{{ $packaging->product->brand_name ?? 'Unknown' }}</div>
                                                <div class="text-xs text-neutral-500">
                                                    {{ $packaging->product->generic_name ?? '-' }}
                                                    @if($packaging->product?->dosage || $packaging->product?->form)
                                                        • {{ trim(($packaging->product->dosage ?? '') . ' ' . ($packaging->product->form ?? '')) }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-neutral-900 dark:text-white">{{ $packaging->unit->name ?? $packaging->unit->abbreviation ?? 'Unit' }}</td>
                                            <td class="px-6 py-4 font-mono text-xs text-neutral-500">{{ $packaging->barcode ?? '-' }}</td>
                                            <td class="px-6 py-4 text-right font-semibold text-neutral-900 dark:text-white">@money($packaging->price)</td>
                                            <td class="px-6 py-4 text-neutral-500">-</td>
                                            <td class="px-6 py-4 text-right text-neutral-500">-</td>
                                            <td class="px-6 py-4 text-right text-neutral-500">-</td>
                                            <td class="px-6 py-4 text-neutral-500">-</td>
                                        </tr>
                                    @else
                                        @foreach($partnerships as $partnership)
                                            @php
                                                $partnerCents = (int) $partnership->getRawOriginal('special_price');
                                            @endphp
                                            <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="font-medium text-black dark:text-white">{{ $packaging->product->brand_name ?? 'Unknown' }}</div>
                                                    <div class="text-xs text-neutral-500">
                                                        {{ $packaging->product->generic_name ?? '-' }}
                                                        @if($packaging->product?->dosage || $packaging->product?->form)
                                                            • {{ trim(($packaging->product->dosage ?? '') . ' ' . ($packaging->product->form ?? '')) }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 text-neutral-900 dark:text-white">{{ $packaging->unit->name ?? $packaging->unit->abbreviation ?? 'Unit' }}</td>
                                                <td class="px-6 py-4 font-mono text-xs text-neutral-500">{{ $packaging->barcode ?? '-' }}</td>
                                                <td class="px-6 py-4 text-right font-semibold text-neutral-900 dark:text-white">@money($packaging->price)</td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center rounded-md bg-emerald-400/10 px-2 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400 ring-1 ring-inset ring-emerald-400/20">
                                                        {{ $partnership->customerType->name ?? 'Customer Type' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 text-right font-semibold text-emerald-600 dark:text-emerald-400">@money($partnership->special_price)</td>
                                                <td class="px-6 py-4 text-right font-semibold text-neutral-900 dark:text-white">
                                                    @money(\Money\Money::PHP($regularCents - $partnerCents))
                                                </td>
                                                <td class="px-6 py-4 text-neutral-500">{{ $partnership->updated_at?->format('M d, Y') ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-transparent ring-2 ring-blue-400">
                                                    <x-ui.icon name="currency-dollar" class="h-5 w-5 text-blue-400!" />
                                                </div>
                                                <h3 class="text-sm font-semibold text-black dark:text-white">No prices found</h3>
                                                <p class="mt-1 text-sm text-neutral-500">Try adjusting the search or filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-black/10 dark:border-white/10 pb-4 px-4 flex justify-center">
                        <x-ui.pagination
                            wire:model.live="perPage"
                            :per-page-options="$perPageOptions"
                            :data="$this->sellingPrices"
                        />
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Stock Movement Ledger --}}
        <x-ui.card hoverless size="full" class="overflow-hidden p-0 border-emerald-500/30">
            <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 bg-emerald-50/30 dark:bg-emerald-900/10 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                    <div>
                        <x-ui.heading level="h3" size="sm" class="text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                            <x-ui.icon name="arrows-right-left" class="size-5" />
                            Stock Movement Report
                        </x-ui.heading>
                        <p class="text-sm text-neutral-500 mt-1">Inventory transaction ledger for stock IN and OUT within the selected report date range.</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button
                            size="sm"
                            variant="outline"
                            icon="x-mark"
                            wire:click="clearStockMovementFilters"
                        >
                            Clear
                        </x-ui.button>
                        <x-ui.button
                            size="sm"
                            variant="outline"
                            icon="arrow-down-tray"
                            wire:click="exportStockMovements"
                            wire:loading.attr="disabled"
                            wire:target="exportStockMovements"
                        >
                            Export Excel
                        </x-ui.button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <x-ui.field>
                        <x-ui.label>Transaction Type</x-ui.label>
                        <x-ui-select.styled
                            wire:model.live="stockMovementTypeFilter"
                            placeholder="All transaction types"
                            :options="$this->stockMovementTypeOptions"
                            select="label:label|value:value"
                        />
                    </x-ui.field>

                    <div class="md:col-span-2 flex items-end">
                        <p class="text-xs text-neutral-500 dark:text-neutral-400">
                            Date range is controlled by the report filter above.
                        </p>
                    </div>
                </div>
            </div>

            <div class="w-full">
                <div class="w-full text-sm text-neutral-300">
                    <div class="w-full overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    <th class="px-6 py-4 whitespace-nowrap">Date & Time</th>
                                    <th class="px-6 py-4">Product</th>
                                    <th class="px-6 py-4">Batch No.</th>
                                    <th class="px-6 py-4 text-center">Type</th>
                                    <th class="px-6 py-4 text-right">IN</th>
                                    <th class="px-6 py-4 text-right">OUT</th>
                                    <th class="px-6 py-4 text-right">Running Balance</th>
                                    <th class="px-6 py-4 text-right">Unit Cost</th>
                                    <th class="px-6 py-4 text-right">Unit Price</th>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4">Remarks</th>
                                    <th class="px-6 py-4">Reference</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                                @forelse ($this->stockMovements as $transaction)
                                    @php
                                        $isAddition = $transaction->type?->isAddition() ?? false;
                                        $referenceLabel = '-';

                                        if ($transaction->reference) {
                                            $referenceNo = $transaction->reference->reference_no
                                                ?? $transaction->reference->payment_reference
                                                ?? $transaction->reference->id
                                                ?? '-';

                                            $referenceLabel = class_basename($transaction->reference_type) . ' #' . $referenceNo;
                                        }
                                    @endphp

                                    <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors group">
                                        <td class="px-6 py-4">
                                            <div class="text-neutral-900 dark:text-white">{{ $transaction->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-neutral-500">{{ $transaction->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-black dark:text-white">{{ $transaction->product->brand_name ?? $transaction->product->name ?? 'Unknown' }}</div>
                                            <div class="text-xs text-neutral-500">
                                                {{ $transaction->product->generic_name ?? '-' }}
                                                @if($transaction->product?->dosage || $transaction->product?->form)
                                                    • {{ trim(($transaction->product->dosage ?? '') . ' ' . ($transaction->product->form ?? '')) }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center rounded-md bg-neutral-400/10 px-2 py-1 text-xs font-medium text-neutral-600 dark:text-neutral-400 ring-1 ring-inset ring-neutral-400/20">
                                                {{ $transaction->batch->batch_number ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $isAddition ? 'bg-emerald-400/10 text-emerald-600 dark:text-emerald-400 ring-emerald-400/20' : 'bg-red-400/10 text-red-600 dark:text-red-400 ring-red-400/20' }}">
                                                {{ $transaction->type?->label() ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @if($isAddition)
                                                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ number_format((float) $transaction->quantity, 2) }}</span>
                                            @else
                                                <span class="text-neutral-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @if(! $isAddition)
                                                <span class="font-bold text-red-600 dark:text-red-400">{{ number_format((float) $transaction->quantity, 2) }}</span>
                                            @else
                                                <span class="text-neutral-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="font-semibold text-neutral-900 dark:text-white">{{ number_format((float) $transaction->running_balance, 2) }}</span>
                                            <span class="text-xs text-neutral-500 ml-1">{{ $transaction->product->baseUnit->abbreviation ?? 'pcs' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-right font-semibold text-black dark:text-white">
                                            @money($transaction->unit_cost ?? \Money\Money::PHP(0))
                                        </td>
                                        <td class="px-6 py-4 text-right font-semibold text-black dark:text-white">
                                            @if($transaction->unit_price)
                                                @money($transaction->unit_price)
                                            @else
                                                <span class="text-neutral-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-neutral-900 dark:text-white">
                                            {{ $transaction->user->name ?? 'Unknown' }}
                                        </td>
                                        <td class="px-6 py-4 text-neutral-500 dark:text-neutral-400 max-w-64">
                                            <span class="line-clamp-2">{{ $transaction->remarks ?? '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4 font-mono text-xs text-neutral-500 whitespace-nowrap">
                                            {{ $referenceLabel }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="px-6 py-16 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-transparent ring-2 ring-emerald-400">
                                                    <x-ui.icon name="arrows-right-left" class="h-5 w-5 text-emerald-400!" />
                                                </div>
                                                <h3 class="text-sm font-semibold text-black dark:text-white">No stock movements found</h3>
                                                <p class="mt-1 text-sm text-neutral-500">No inventory transactions match this report range.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-black/10 dark:border-white/10 pb-4 px-4 flex justify-center">
                        <x-ui.pagination
                            wire:model.live="perPage"
                            :per-page-options="$perPageOptions"
                            :data="$this->stockMovements"
                        />
                    </div>
                </div>
            </div>
        </x-ui.card>

    </div>
</div>
