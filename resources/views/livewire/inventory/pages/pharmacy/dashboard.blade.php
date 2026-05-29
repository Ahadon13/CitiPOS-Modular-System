<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Overview</h1>
            <p class="text-neutral-500 dark:text-neutral-400">
                @if($this->isSuperAdmin)
                    Global Dashboard (All Branches)
                @else
                    {{ $this->user->branch->name ?? 'Main Branch' }}
                @endif
            </p>
        </div>
        <x-ui.button href="{{ route('inventory.pharmacy.stocks') }}" icon="arrow-right">
            Add Stock
        </x-ui.button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <x-ui.card hoverless size="full" class="border-l-4 border-l-[#2580ff]!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-lg">
                    <x-ui.icon name="cube" class="size-6 text-blue-600! dark:text-blue-400!" />
                </div>
                <div>
                    <p class="text-sm font-medium uppercase text-blue-500">Total Products</p>
                    <h3 class="text-3xl font-bold text-blue-900 dark:text-blue-200">
                        {{ number_format($this->totalProducts, 0) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

         <x-ui.card hoverless size="full" class="border-l-4 border-l-emerald-500!">
             <div class="flex items-center gap-4">
                 <div class="p-3 bg-emerald-100 dark:bg-emerald-900/50 rounded-lg">
                     <x-ui.icon name="squares-2x2" class="size-6 text-emerald-600! dark:text-emerald-400!" />
                 </div>
                 <div>
                     <p class="text-sm font-medium uppercase text-emerald-500">Total Items</p>
                     <h3 class="text-3xl font-bold text-emerald-900 dark:text-emerald-200">
                         {{ number_format($this->totalItems, 0) }}
                     </h3>
                 </div>
             </div>
         </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-yellow-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg">
                    <x-ui.icon name="clock" class="size-6 text-yellow-600! dark:text-yellow-400!" />
                </div>
                <div>
                    <p class="text-sm font-medium uppercase text-yellow-500">Low Stock</p>
                    <h3 class="text-3xl font-bold text-yellow-900 dark:text-yellow-200">
                        {{ number_format($this->lowStockCount) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-orange-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-orange-100 dark:bg-orange-900/50 rounded-lg">
                    <x-ui.icon name="exclamation-triangle" class="size-6 text-orange-600! dark:text-orange-400!" />
                </div>
                <div>
                    <p class="text-sm font-medium uppercase text-orange-500">Expiring Soon</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-orange-900 dark:text-orange-200">{{ number_format($this->expiringSoonCount) }}</h3>
                        @if($this->expiringSoonCount > 0)
                        <span class="text-xs text-orange-500 font-bold animate-pulse">Action Needed</span>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-red-500!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-red-100 dark:bg-red-900/50 rounded-lg">
                    <x-ui.icon name="x-circle" class="size-6 text-red-600! dark:text-red-400!" />
                </div>
                <div>
                    <p class="text-sm font-medium uppercase text-red-500">Out of Stock</p>
                    <h3 class="text-3xl font-bold text-red-900 dark:text-red-200">
                        {{ number_format($this->outOfStockCount, 0) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-rose-700!">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-rose-100 dark:bg-rose-900/50 rounded-lg">
                    <x-ui.icon name="archive-box-x-mark" class="size-6 text-rose-700! dark:text-rose-400!" />
                </div>
                <div>
                    <p class="text-sm font-medium uppercase text-rose-600">Already Expired</p>
                    <div class="flex items-baseline gap-2">
                        <h3 class="text-3xl font-bold text-rose-900 dark:text-rose-200">{{ number_format($this->expiredCount) }}</h3>
                        @if($this->expiredCount > 0)
                        <span class="text-xs text-rose-600 font-bold animate-pulse">Pull from shelves</span>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui.card>

    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2">
            <x-ui.card hoverless size="full" class="h-full">
                <x-ui.heading level="h3" size="sm" class="mb-6">
                    Sales Trend (7 Days)
                </x-ui.heading>

                <div
                    x-data="{
                        init() {
                            let options = {
                                series: [{
                                    name: 'Sales',
                                    data: @js($this->salesChartData['series'])
                                }],
                                chart: {
                                    type: 'area',
                                    height: 300,
                                    fontFamily: 'Inter, sans-serif',
                                    toolbar: { show: false }
                                },
                                colors: ['#2580ff'],
                                fill: {
                                    type: 'gradient',
                                    gradient: {
                                        shadeIntensity: 1,
                                        opacityFrom: 0.7,
                                        opacityTo: 0.1,
                                        stops: [0, 90, 100]
                                    }
                                },
                                dataLabels: { enabled: false },
                                stroke: { curve: 'smooth', width: 2 },
                                xaxis: {
                                    categories: @js($this->salesChartData['categories']),
                                    axisBorder: { show: false },
                                    axisTicks: { show: false }
                                },
                                yaxis: { show: false },
                                grid: { borderColor: '#f1f1f1' }
                            };
                            let chart = new ApexCharts(this.$el, options);
                            chart.render();
                        }
                    }"
                    class="w-full"
                ></div>
            </x-ui.card>
        </div>

        <div class="lg:col-span-1">
            <x-ui.card hoverless size="full" class="min-h-100!">
                <x-ui.heading level="h3" size="sm" class="mb-4 flex items-center justify-between">
                    <span>Critical Expiry</span>
                </x-ui.heading>

                <div class="space-y-4 overflow-y-scroll h-80 pr-2">
                    @forelse($this->expiringBatches as $batch)
                        @php
                            $catName = $batch->product->productCategory->name ?? '';
                            $isPharmacy = str_contains($catName, 'Pharmacy') || str_contains($catName, 'Medicine');
                        @endphp
                        <div class="flex items-start gap-3 pb-3 border-b border-white/10 last:border-0 last:pb-0">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-neutral-900 dark:text-white truncate">
                                    {{ $batch->product->brand_name }}
                                </p>
                                <p class="text-xxs text-neutral-500 dark:text-neutral-400 truncate">
                                    {{ filled($batch->batch_number) ? $batch->batch_number : 'N/A' }} • {{ number_format($batch->quantity_on_hand, 2) }} left
                                </p>
                                @if($this->isSuperAdmin)
                                    <p class="text-xxs text-blue-600 mt-1 uppercase tracking-wide">
                                        {{ $batch->branch->name }}
                                    </p>
                                @endif
                            </div>

                            <div class="text-right">
                                <p class="text-xs font-bold text-red-600">
                                    {{ \Carbon\Carbon::parse($batch->expiration_date)->format('M d') }}
                                </p>
                                <p class="text-[10px] text-neutral-400">
                                    {{ \Carbon\Carbon::parse($batch->expiration_date)->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <x-ui.icon name="check-circle" class="size-8 text-green-500! mx-auto mb-2" />
                            <p class="text-sm text-green-500">Stocks are healthy!</p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- IN-DEMAND PRODUCTS (TOP SELLERS)           --}}
    {{-- ========================================== --}}
    <x-ui.card hoverless size="full" class="overflow-hidden p-0 border-indigo-500/30">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex items-center justify-between bg-indigo-50/30 dark:bg-indigo-900/10">
            <div>
                <x-ui.heading level="h3" size="sm" class="text-indigo-700 dark:text-indigo-400 flex items-center gap-2">
                    <x-ui.icon name="fire" class="size-5" />
                    Top In-Demand Products
                </x-ui.heading>
                <p class="text-sm text-neutral-500 mt-1">Highest moving items in the last 30 days.</p>
            </div>

            <x-ui.button
                size="sm"
                variant="outline"
                icon="arrow-down-tray"
                wire:click="exportTopDemandProducts"
                wire:loading.attr="disabled"
                wire:target="exportTopDemandProducts"
            >
                Export Excel
            </x-ui.button>
        </div>

        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Rank</th>
                                <th class="px-6 py-4">Product Name</th>
                                <th class="px-6 py-4">Dosage</th>
                                <th class="px-6 py-4">Product Code</th>
                                <th class="px-6 py-4 text-center">Total Volume Sold</th>
                                <th class="px-6 py-4 text-right">Revenue Generated</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->topDemandProducts as $index => $item)
                            <tr class="hover:bg-indigo-50/50 dark:hover:bg-indigo-900/20 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="size-8 rounded-lg flex items-center justify-center font-black text-sm {{ $index === 0 ? 'bg-amber-100 text-amber-600' : ($index === 1 ? 'bg-slate-200 text-slate-600' : ($index === 2 ? 'bg-orange-100 text-orange-700' : 'bg-neutral-100 dark:bg-white/10 text-neutral-500')) }}">
                                        #{{ $index + 1 }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-black dark:text-white">{{ $item->product->brand_name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-neutral-500">{{ $item->product->generic_name ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-black dark:text-white">{{ $item->product->dosage ?? '-' }}</div>
                                    <div class="text-xs text-neutral-500">{{ $item->product->form ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-neutral-400">
                                    <span class="inline-flex items-center rounded-md bg-neutral-400/10 px-2 py-1 text-xs font-medium text-neutral-600 dark:text-neutral-400 ring-1 ring-inset ring-neutral-400/20">
                                        {{ $item->product->product_code ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold text-lg">{{ number_format($item->total_sold, 2) }}</span>
                                    <span class="text-xs text-neutral-500 ml-1">{{ $item->product->baseUnit->abbreviation ?? 'pcs' }}</span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-black dark:text-white">
                                    @money(\Money\Money::PHP((int) ($item->total_revenue ?? 0)))
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-transparent ring-2 ring-indigo-400">
                                            <x-ui.icon name="chart-bar" class="h-5 w-5 text-indigo-400!" />
                                        </div>
                                        <h3 class="text-sm font-semibold text-black dark:text-white">No sales data yet</h3>
                                        <p class="mt-1 text-sm text-neutral-500">Demand will be calculated once products are sold.</p>
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
                    :data="$this->topDemandProducts"
                />
            </div>
            </div>
        </div>
    </x-ui.card>

    {{-- ========================================== --}}
    {{-- STOCK MOVEMENTS (IN / OUT LEDGER)          --}}
    {{-- ========================================== --}}
    <x-ui.card hoverless size="full" class="overflow-hidden p-0 border-emerald-500/30">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 bg-emerald-50/30 dark:bg-emerald-900/10 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                <div>
                    <x-ui.heading level="h3" size="sm" class="text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                        <x-ui.icon name="arrows-right-left" class="size-5" />
                        Stock Movements
                    </x-ui.heading>
                    <p class="text-sm text-neutral-500 mt-1">Inventory transaction ledger for stock IN and OUT.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
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

                <div class="flex items-end gap-3 col-span-1 md:col-span-2">
                    <x-ui.field class="w-full!">
                        <x-ui.label>Date From</x-ui.label>
                        <x-ui.input
                            type="date"
                            wire:model.live="stockMovementDateFrom"
                        />
                    </x-ui.field>

                    <x-ui.field class="w-full!">
                        <x-ui.label>Date To</x-ui.label>
                        <x-ui.input
                            type="date"
                            wire:model.live="stockMovementDateTo"
                        />
                    </x-ui.field>
                    <x-ui.field>
                        <x-ui.button size="sm" variant="outline" icon="x-mark" wire:click="clearStockMovementFilters">
                            Clear
                        </x-ui.button>
                     </x-ui.field>
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
                                            <p class="mt-1 text-sm text-neutral-500">Inventory transactions will appear here once stock moves in or out.</p>
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

    <x-ui.card hoverless size="full" class="overflow-hidden p-0">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex items-center justify-between">
            <div>
                <x-ui.heading level="h3" size="sm">Low Stock Alerts</x-ui.heading>
                <p class="text-sm text-neutral-500 mt-1">Products below reorder level</p>
            </div>

            <x-ui.button
                size="sm"
                variant="outline"
                icon="arrow-down-tray"
                wire:click="exportLowStockProducts"
                wire:loading.attr="disabled"
                wire:target="exportLowStockProducts"
            >
                Export Excel
            </x-ui.button>
        </div>

        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">Dosage</th>
                                <th class="px-6 py-4">Product Code</th>
                                <th class="px-6 py-4 text-center">Current Stock</th>
                                <th class="px-6 py-4 text-center">Reorder At</th>
                                <th class="px-6 py-4 text-right">Selling Price</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->lowStockProducts as $product)
                                @php
                                    // Try to find packaging matching the base unit, otherwise take first available
                                    $basePkg = $product->productPackagings->where('unit_id', $product->base_unit_id)->first()
                                               ?? $product->productPackagings->first();
                                @endphp

                                <tr class="hover:bg-white/5 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-black dark:text-white">{{ $product->brand_name ?? 'Unknown'}}</div>
                                        <div class="text-xs text-neutral-500">{{ $product->generic_name ?? '-' }}</div>
                                    </td>

                                    <td class="px-6 py-4 font-mono text-neutral-400">
                                        <div class="font-medium text-black dark:text-white">{{ $product->dosage ?? '-' }}</div>
                                        <div class="text-xs text-neutral-500">{{ $product->form ?? '-' }}</div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-md bg-blue-400/10 px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 ring-1 ring-inset ring-blue-400/20">
                                            {{ $product->product_code ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if(($product->total_stock ?? 0) <= 0)
                                            <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-400 ring-1 ring-inset ring-red-400/20">
                                                Out of Stock
                                            </span>
                                        @else
                                            <span class="text-orange-400 font-bold">{{ number_format($product->total_stock, 2) }}</span>
                                            <span class="text-xs text-neutral-500 ml-1">{{ $product->baseUnit->abbreviation ?? 'pcs' }}</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-center text-neutral-500">
                                        {{ number_format($product->reorder_level, 2) }}
                                    </td>

                                    <td class="px-6 py-4 text-right font-semibold text-black dark:text-white">
                                        @money($basePkg->price)
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-24 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-transparent ring-2 ring-green-400">
                                                <x-ui.icon name="check" class="h-5 w-5 text-green-400!" />
                                            </div>
                                            <h3 class="text-sm font-semibold text-black dark:text-white">All products have sufficient stock</h3>
                                            <p class="mt-1 text-sm text-neutral-500">No products are currently below their reorder level.</p>
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
                        :data="$this->lowStockProducts"
                    />
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card hoverless size="full" class="overflow-hidden p-0 border-rose-500/30">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex items-center justify-between bg-rose-50/30 dark:bg-rose-900/10">
            <div>
                <x-ui.heading level="h3" size="sm" class="text-rose-700 dark:text-rose-400">Expired Batches</x-ui.heading>
                <p class="text-sm text-neutral-500 mt-1">These batches have officially passed their expiration date.</p>
            </div>

            <x-ui.button
                size="sm"
                variant="outline"
                icon="arrow-down-tray"
                wire:click="exportExpiredBatches"
                wire:loading.attr="disabled"
                wire:target="exportExpiredBatches"
            >
                Export Excel
            </x-ui.button>
        </div>

        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Product Name</th>
                                <th class="px-6 py-4">Dosage</th>
                                <th class="px-6 py-4">Batch Number</th>
                                <th class="px-6 py-4 text-center">Date Expired</th>
                                <th class="px-6 py-4 text-center">Quantity Lost</th>
                                <th class="px-6 py-4 text-right">Cost Per Unit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->expiredBatches as $batch)
                            <tr class="hover:bg-rose-50/50 dark:hover:bg-rose-900/20 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-black dark:text-white">{{ $batch->product->brand_name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-neutral-500">{{ $batch->product->generic_name ?? 'Unknown' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-neutral-400">
                                    <div class="font-medium text-black dark:text-white">{{ $batch->product->dosage ?? '-' }}</div>
                                    <div class="text-xs text-neutral-500">{{ $batch->product->form ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 font-mono text-neutral-400">
                                    <span class="inline-flex items-center rounded-md bg-neutral-400/10 px-2 py-1 text-xs font-medium text-neutral-600 dark:text-neutral-400 ring-1 ring-inset ring-neutral-400/20">
                                        {{ $batch->batch_number ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="font-bold text-rose-600 dark:text-rose-400">{{ \Carbon\Carbon::parse($batch->expiration_date)->format('M d, Y') }}</div>
                                    <div class="text-xs text-neutral-500">{{ \Carbon\Carbon::parse($batch->expiration_date)->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-neutral-900 dark:text-white font-bold">{{ number_format($batch->quantity_on_hand, 2) }}</span>
                                    <span class="text-xs text-neutral-500 ml-1">{{ $batch->product->baseUnit->abbreviation ?? 'pcs' }}</span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-black dark:text-white">
                                    @money($batch->cost_per_unit ?? 0)
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-transparent ring-2 ring-green-400">
                                            <x-ui.icon name="check" class="h-5 w-5 text-green-400!" />
                                        </div>
                                        <h3 class="text-sm font-semibold text-black dark:text-white">No expired inventory</h3>
                                        <p class="mt-1 text-sm text-neutral-500">All current stock is safely within expiration dates.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-black/10 dark:border-white/10 pb-4 px-4 flex justify-center">
                    <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->expiredBatches" />
                </div>
            </div>
        </div>
    </x-ui.card>
</div>
