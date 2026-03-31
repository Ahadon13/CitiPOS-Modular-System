<div class="max-w-7xl mx-auto space-y-6 p-5">

    {{-- Breadcrumbs --}}
    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('admin.branches') }}" wire:navigate>
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
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="7days">Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="this_month">This Month</option>
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

    </div>

    {{-- Activity Grids --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Recent Sales --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Recent Sales</h2>
            </div>

            <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
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
                                    <span class="block text-[10px] text-neutral-500">{{ $sale->created_at->diffForHumans() }}</span>
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
                                             <x-ui.icon name="shopping-cart" class="size-6 text-neutral-400" />
                                         </x-ui.empty.media>
                                         <x-ui.empty.contents>
                                             <x-ui.heading>No recent sales.</x-ui.heading>
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

        {{-- Recent Purchase Orders --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Recent Purchase Orders</h2>
            </div>

            <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
                <div class="overflow-x-auto custom-scrollbar">
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
                                <td colspan="5" class="p-6 text-center text-neutral-500">
                                    <x-ui.empty>
                                        <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                                            <x-ui.icon name="document-text" class="size-6 text-neutral-400" />
                                        </x-ui.empty.media>
                                        <x-ui.empty.contents>
                                            <x-ui.heading>No recent purchase orders.</x-ui.heading>
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
