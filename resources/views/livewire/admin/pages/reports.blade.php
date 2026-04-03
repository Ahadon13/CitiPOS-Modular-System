<div class="max-w-7xl mx-auto space-y-6 p-5">

    {{-- Header & Master Filters --}}
    <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-4 bg-white dark:bg-[#0a1331] p-5 rounded-2xl border border-black/5 dark:border-white/10 shadow-sm">
        <div class="mb-2 xl:mb-0">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Business Reports</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Analyze your financial health and team performance</p>
        </div>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3 w-full xl:w-auto">

            {{-- Branch Filter --}}
            <div class="w-full sm:w-48">
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
            <div class="w-full sm:w-48">
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
            <div class="w-full sm:w-64">
                <x-ui.field class="mb-0">
                    <x-ui.label class="text-xs text-neutral-500">Date Range</x-ui.label>
                    <x-ui-date range wire:model.live="dateRange" format="YYYY-MM-DD" placeholder="Last 30 Days (Default)" />
                </x-ui.field>
            </div>
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
                    This represents the total capital currently tied up in physical stock across the selected filters, based on supplier cost.
                </div>
                <div class="mt-4 pt-4 border-t border-indigo-100 dark:border-indigo-900/30">
                    <x-ui.button variant="outline" class="w-full justify-center" href="{{ route('admin.branches') }}" wire:navigate>
                        Manage Branches &rarr;
                    </x-ui.button>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>
