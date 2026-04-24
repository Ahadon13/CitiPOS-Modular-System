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
                    This represents the total capital currently tied up in physical motor parts stock, based on supplier cost.
                </div>
                <div class="mt-4 pt-4 border-t border-indigo-100 dark:border-indigo-900/30">
                    <x-ui.button variant="outline" class="w-full justify-center" href="{{ route('inventory.motor-shop.stocks') }}">
                        View Stock Ledger &rarr;
                    </x-ui.button>
                </div>
            </x-ui.card>
        </div>

    </div>

    {{-- Service Reports --}}
    <div class="space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Service Performance</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">Labor/service revenue and completed service jobs within the selected report date range.</p>
            </div>
            <x-ui.button size="sm" variant="outline" icon="x-mark" wire:click="clearServiceFilters">
                Clear Service Filters
            </x-ui.button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-ui.card hoverless class="border-l-4 border-l-cyan-500!">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Service Revenue</p>
                <h3 class="text-2xl font-black text-cyan-600 dark:text-cyan-400 mt-1">@money($this->serviceStats['service_revenue'])</h3>
                <p class="text-xs text-neutral-400 mt-2">Total completed service labor</p>
            </x-ui.card>

            <x-ui.card hoverless class="border-l-4 border-l-emerald-500!">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Service Lines</p>
                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ number_format($this->serviceStats['total_services']) }}</h3>
                <p class="text-xs text-neutral-400 mt-2">Individual service entries</p>
            </x-ui.card>

            <x-ui.card hoverless class="border-l-4 border-l-violet-500!">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Service Orders</p>
                <h3 class="text-2xl font-black text-violet-600 dark:text-violet-400 mt-1">{{ number_format($this->serviceStats['service_orders']) }}</h3>
                <p class="text-xs text-neutral-400 mt-2">Transactions with services</p>
            </x-ui.card>

            <x-ui.card hoverless class="border-l-4 border-l-amber-500!">
                <p class="text-xs font-bold text-neutral-500 uppercase tracking-wider">Average Service</p>
                <h3 class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">@money($this->serviceStats['average_service_value'])</h3>
                <p class="text-xs text-neutral-400 mt-2">{{ number_format($this->serviceStats['total_quantity'], 2) }} total quantity</p>
            </x-ui.card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1">
                <x-ui.card hoverless size="full" class="h-full">
                    <x-ui.heading level="h3" size="sm" class="mb-4 flex items-center gap-2">
                        <x-ui.icon name="wrench-screwdriver" class="size-5 text-cyan-500" />
                        Top Services
                    </x-ui.heading>

                    @if($this->topServices->isNotEmpty())
                        <div class="space-y-3">
                            @foreach($this->topServices as $service)
                                <div class="flex items-center justify-between gap-3 rounded-lg border border-black/10 dark:border-white/10 bg-neutral-50 dark:bg-white/5 p-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-neutral-900 dark:text-white truncate">{{ $service->service_name }}</p>
                                        <p class="text-xs text-neutral-500">{{ number_format((float) $service->total_quantity, 2) }} qty / {{ number_format($service->service_count) }} lines</p>
                                    </div>
                                    <p class="font-mono font-black text-cyan-600 dark:text-cyan-400 whitespace-nowrap">
                                        @money(\Money\Money::PHP((int) $service->total_revenue))
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-10">
                            <x-ui.empty>
                                <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                    <x-ui.icon name="wrench-screwdriver" class="size-6 text-neutral-400" />
                                </x-ui.empty.media>
                                <x-ui.empty.contents>
                                    <x-ui.text>No services recorded in this period.</x-ui.text>
                                </x-ui.empty.contents>
                            </x-ui.empty>
                        </div>
                    @endif
                </x-ui.card>
            </div>

            <div class="lg:col-span-2">
                <x-ui.card hoverless size="full" class="p-0">
                    <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h3" size="sm">Service Transactions</x-ui.heading>
                            <p class="text-sm text-neutral-500 dark:text-neutral-400">All completed service lines with searchable customer, mechanic, reference, and service details.</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                            <x-ui.input
                                wire:model.live.debounce.300ms="serviceSearch"
                                leftIcon="magnifying-glass"
                                clearable
                                placeholder="Search service, mechanic, customer..."
                                class="w-full sm:w-72"
                            />
                            <div class="w-full sm:w-64">
                                <x-ui-select.styled
                                    wire:model.live="serviceMechanicFilter"
                                    placeholder="All Mechanics"
                                    :options="$this->serviceMechanicOptions"
                                    select="label:label|value:value"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                    <th class="px-6 py-4">Service</th>
                                    <th class="px-6 py-4">Reference</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Customer</th>
                                    <th class="px-6 py-4">Mechanic</th>
                                    <th class="px-6 py-4 text-center">Qty</th>
                                    <th class="px-6 py-4 text-right">Price</th>
                                    <th class="px-6 py-4 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                                @forelse($this->serviceTransactions as $service)
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-neutral-900 dark:text-white">{{ $service->service_name }}</p>
                                            @if($service->description)
                                                <p class="text-xs text-neutral-500 max-w-xs truncate">{{ $service->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="font-mono font-medium text-cyan-600 dark:text-cyan-400">{{ $service->sale->payment_reference ?? '-' }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-neutral-900 dark:text-white">{{ $service->sale?->created_at?->format('M d, Y') ?? $service->created_at->format('M d, Y') }}</div>
                                            <div class="text-xs text-neutral-500">{{ $service->sale?->created_at?->format('h:i A') ?? $service->created_at->format('h:i A') }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-neutral-500 dark:text-neutral-400">
                                            {{ $service->sale->customer->name ?? 'Walk-in' }}
                                        </td>
                                        <td class="px-6 py-4 text-neutral-900 dark:text-white">
                                            {{ $service->mechanic->name ?? 'No mechanic' }}
                                        </td>
                                        <td class="px-6 py-4 text-center text-neutral-600 dark:text-neutral-400">
                                            {{ number_format((float) $service->quantity, 2) }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-mono text-neutral-700 dark:text-neutral-300">
                                            @money($service->price_at_moment)
                                        </td>
                                        <td class="px-6 py-4 text-right font-mono font-bold text-neutral-900 dark:text-white">
                                            @money($service->subtotal)
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-16 text-center">
                                            <x-ui.empty>
                                                <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center mx-auto">
                                                    <x-ui.icon name="wrench-screwdriver" class="size-6 text-neutral-400" />
                                                </x-ui.empty.media>
                                                <x-ui.empty.contents>
                                                    <x-ui.heading>No service transactions found</x-ui.heading>
                                                    <x-ui.text class="opacity-70">Try adjusting the date range, service search, or mechanic filter.</x-ui.text>
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
                            :data="$this->serviceTransactions"
                        />
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>

    @include('livewire.inventory.pages.shared.stock-movements-ledger', [
        'stockMovementModule' => 'motor-shop',
        'stockMovementTitle' => 'Stock Movement Report',
        'stockMovementDescription' => 'Inventory transaction ledger for motor parts stock IN and OUT within the selected report date range.',
        'stockMovementEmptyDescription' => 'No Motor Shop inventory transactions match this report range.',
        'showStockMovementDateFilters' => false,
    ])
</div>
