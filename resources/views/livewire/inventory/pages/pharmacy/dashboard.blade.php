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
        <x-ui.button href="#" icon="plus" wire:navigate>
            Add Stock
        </x-ui.button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

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
                        <h3 class="text-3xl font-bold text-orange-900 dark:text-orange-200">{{ $this->expiringBatches->count() }}</h3>
                        @if($this->expiringBatches->count() > 0)
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
                            $catName = $batch->product->category->name ?? '';
                            $isPharmacy = str_contains($catName, 'Pharmacy') || str_contains($catName, 'Medicine');
                        @endphp
                        <div class="flex items-start gap-3 pb-3 border-b border-white/10 last:border-0 last:pb-0">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium text-neutral-900 dark:text-white truncate">
                                    {{ $batch->product->brand_name }}
                                </p>
                                <p class="text-xxs text-neutral-500 dark:text-neutral-400 truncate">
                                    {{ $batch->batch_number }} • {{ number_format($batch->quantity_on_hand, 2) }} left
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

    <x-ui.card hoverless size="full" class="overflow-hidden p-0">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex items-center justify-between">
            <div>
                <x-ui.heading level="h3" size="sm">Low Stock Alerts</x-ui.heading>
                <p class="text-sm text-neutral-500 mt-1">Products below reorder level</p>
            </div>

            <x-ui.button size="sm" variant="outline" icon="printer">
                Print Report
            </x-ui.button>
        </div>

        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">

                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">SKU/Barcode</th>
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
                                        <div class="font-medium text-black dark:text-white">{{ $product->brand_name }}</div>
                                        <div class="text-xs text-neutral-500">{{ $product->generic_name }}</div>
                                    </td>

                                    <td class="px-6 py-4 font-mono text-neutral-400">
                                        {{ $basePkg->barcode ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center rounded-md bg-blue-400/10 px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 ring-1 ring-inset ring-blue-400/20">
                                            {{ $product->product_code ?? 'N/A' }}
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
</div>
