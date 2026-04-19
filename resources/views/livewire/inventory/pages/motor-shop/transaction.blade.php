<div class="max-w-7xl mx-auto space-y-6" x-data="transactionManager()">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Transactions</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Monitor your daily sales and revenue</p>
        </div>
        <div class="flex items-center gap-3 justify-end">
            <x-ui.button variant="outline" icon="arrow-path" wire:click="$refresh">
                Refresh
            </x-ui.button>
            <x-ui.button variant="outline" icon="document-text" wire:loading.attr="disabled" wire:click="openDailyReportModal">
                Daily Report
            </x-ui.button>
        </div>
    </div>

    {{-- Tabs for Date Filtering --}}
    <div class="border-b border-black/10 dark:border-white/10">
        <nav class="-mb-px flex space-x-6 overflow-x-auto custom-scrollbar" aria-label="Tabs">
            @php
                $tabs = [
                    'today' => 'Today',
                    'yesterday' => 'Yesterday',
                    '7days' => 'Last 7 Days',
                    '30days' => 'Last 30 Days',
                    'all' => 'All Time',
                ];
            @endphp
            @foreach($tabs as $key => $label)
                <button
                    wire:click="$set('dateFilter', '{{ $key }}')"
                    class="whitespace-nowrap py-3 px-2 border-b-2 font-medium text-sm transition-colors {{ $dateFilter === $key ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-neutral-500 hover:text-neutral-700 hover:border-neutral-300 dark:text-neutral-400 dark:hover:text-neutral-300 dark:hover:border-neutral-600' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Top Demand Products (Cards) --}}
    <div class="w-full space-y-4 flex flex-col">
        <div class="flex items-center gap-5 w-full justify-between">
            <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Top Demand Products</h2>
            <x-ui.button size="sm" variant="outline" icon="arrow-down-tray" wire:loading.attr="disabled" wire:click="openExportModal('demand')">
                Export in Excel
            </x-ui.button>
        </div>

        @if(count($this->topDemandProducts) > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 w-full">
                @foreach($this->topDemandProducts as $index => $item)
                    <x-ui.card hoverless size="full" class="relative overflow-hidden p-5 flex flex-col justify-between h-full group transition-all border border-black/5 dark:border-white/5 hover:border-blue-500/50 dark:hover:border-blue-400/50 hover:shadow-md">
                        <div class="absolute top-0 right-0 -mt-2 -mr-2 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                            <x-ui.icon name="fire" class="size-16 text-neutral-900 dark:text-white" />
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-4">
                                <div class="size-8 rounded-lg flex items-center justify-center font-black text-sm {{ $index === 0 ? 'bg-amber-100 text-amber-600 shadow-sm' : ($index === 1 ? 'bg-slate-200 text-slate-600 shadow-sm' : ($index === 2 ? 'bg-orange-100 text-orange-700 shadow-sm' : 'bg-neutral-100 dark:bg-white/10 text-neutral-500')) }}">
                                    #{{ $index + 1 }}
                                </div>
                            </div>

                            <h3 class="font-bold text-neutral-900 dark:text-white line-clamp-2 leading-tight mb-1" title="{{ $item->product->brand_name }}">
                                {{ $item->product->brand_name }}
                            </h3>
                            <p class="text-[10px] text-neutral-500 uppercase tracking-wider truncate mb-4">
                                {{ $item->product->category->name ?? 'Uncategorized' }}
                            </p>
                        </div>

                        <div class="pt-4 border-t border-black/5 dark:border-white/5 flex items-end justify-between">
                            <div>
                                <p class="text-[10px] text-neutral-500 uppercase font-medium">Revenue</p>
                                <p class="font-bold text-sm text-green-600 dark:text-green-400">
                                    @money(\Money\Money::PHP((int) ($item->total_revenue ?? 0)))
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-neutral-500 uppercase font-medium">Sold</p>
                                <p class="font-bold text-sm text-neutral-900 dark:text-white">
                                    {{ number_format($item->total_sold) }} <span class="text-[10px] font-normal text-neutral-500">{{ $item->product->baseUnit->abbreviation ?? 'pcs' }}</span>
                                </p>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @else
            <x-ui.card size="full" hoverless class="p-8 border-dashed border-2 flex flex-col items-center justify-center">
                <x-ui.empty>
                    <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                        <x-ui.icon name="star" class="size-6 text-neutral-400" />
                    </x-ui.empty.media>
                    <x-ui.empty.contents>
                        <x-ui.heading>No Top Products Yet</x-ui.heading>
                        <x-ui.text class="text-center">There are no completed sales matching the current filters.</x-ui.text>
                    </x-ui.empty.contents>
                </x-ui.empty>
            </x-ui.card>
        @endif
    </div>

    {{-- Stats Cards --}}
    <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- 1. Total Transactions --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500! flex flex-col justify-center">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <x-ui.icon name="document-text" class="size-6 text-blue-600! dark:text-blue-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Total Transactions</p>
                    <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-400">
                        {{ number_format($this->stats['total_count']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 2. Revenue --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-green-500! flex flex-col justify-center">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <x-ui.icon name="banknotes" class="size-6 text-green-600! dark:text-green-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-green-600/80 uppercase tracking-wide">Total Revenue</p>
                    <h3 class="text-2xl font-bold text-green-700 dark:text-green-400">
                        @money($this->stats['total_revenue'])
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 3. Total Completed --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-indigo-500! flex flex-col justify-center">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <x-ui.icon name="check-circle" class="size-6 text-indigo-600! dark:text-indigo-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-indigo-600/80 uppercase tracking-wide">Completed</p>
                    <h3 class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">
                        {{ number_format($this->stats['completed_count']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        {{-- 4. Average Value --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-purple-500! flex flex-col justify-center">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <x-ui.icon name="chart-bar" class="size-6 text-purple-600! dark:text-purple-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-purple-600/80 uppercase tracking-wide">Average Value</p>
                    <h3 class="text-2xl font-bold text-purple-700 dark:text-purple-400">
                        @money($this->stats['average_value'])
                    </h3>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Transactions Table --}}
    <x-ui.card hoverless size="full" class="p-0">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="w-full md:w-72">
                <x-ui.input
                    wire:model.live.debounce.300ms="search"
                    leftIcon="magnifying-glass" clearable
                    placeholder="Search invoice no, cashier..."
                    class="w-full"
                />
            </div>
            <div class="flex items-center justify-between gap-3">
                 <x-ui.field class="w-70!">
                    <x-ui-select.styled
                        wire:model.live="paymentMethodFilter"
                        placeholder="All Methods"
                        :options="$this->paymentMethodOptions"
                        select="label:label|value:value"
                    />
                </x-ui.field>
                <x-ui.button
                    size="sm"
                    variant="outline"
                    icon="arrow-down-tray"
                    wire:loading.attr="disabled"
                    wire:click="openExportModal('transactions')"
                >
                    Export in Excel
                </x-ui.button>
            </div>
        </div>

        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Reference No.</th>
                                <th class="px-6 py-4 whitespace-nowrap">Date & Time</th>
                                <th class="px-6 py-4">Cashier</th>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4 text-center">Items</th>
                                <th class="px-6 py-4 text-center">Payment</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-center">Change</th>
                                <th class="px-6 py-4 text-center">Grand Total</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->transactions as $transaction)
                                <tr class="hover:bg-white/5 transition-colors group">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-medium text-blue-600 dark:text-blue-400">{{ $transaction->payment_reference ?? '-' }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-neutral-900 dark:text-white">{{ $transaction->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-neutral-500">{{ $transaction->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-neutral-900 dark:text-white">
                                        {{ $transaction->user->name ?? 'Unknown' }}
                                    </td>
                                    <td class="px-6 py-4 text-neutral-500 dark:text-neutral-400">
                                        {{ $transaction->customer->name ?? 'Walk-in' }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-neutral-600 dark:text-neutral-400">
                                        {{ $transaction->sale_items_count }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center rounded-md bg-neutral-100 dark:bg-white/5 px-2 py-1 text-xs font-medium text-neutral-700 dark:text-neutral-300 ring-1 ring-inset ring-neutral-500/20">
                                            {{ $transaction->paymentMethod->name ?? 'Unspecified' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($transaction->status === \App\Enums\Sale\Status::Completed)
                                            <span class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 ring-1 ring-inset ring-green-400/20">
                                                Completed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">
                                                {{ $transaction->status->label() ?? 'Unknown' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-center text-neutral-900 dark:text-white font-bold tracking-tight">
                                        @money($transaction->change_amount)
                                    </td>
                                    <td class="px-6 py-4 text-center text-neutral-900 dark:text-white font-bold tracking-tight">
                                        @money($transaction->grand_total)
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <x-ui.button
                                            size="xs"
                                            variant="outline"
                                            icon="eye"
                                            x-on:click='viewTx({{ json_encode($transaction) }})'
                                        >
                                            View
                                        </x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-6 py-24 text-center">
                                        <x-ui.empty>
                                            <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                                <x-ui.icon name="document-text" class="size-6" />
                                            </x-ui.empty.media>

                                            <x-ui.empty.contents>
                                                <x-ui.heading>No transactions found</x-ui.heading>
                                                <x-ui.text class="opacity-70">
                                                    Start selling at your POS to see transactions appear here.
                                                </x-ui.text>

                                                <x-ui.button icon="shopping-cart" size="sm" class="mt-3" href="{{ route('pos.motor-shop.process-sale') }}">
                                                    Go to POS
                                                </x-ui.button>
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
                        :data="$this->transactions"
                    />
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Daily Report Modal --}}
    <x-ui.modal id="daily-report-modal" width="sm" heading="Download Daily Report">
        <form wire:submit.prevent="downloadDailyReport" class="space-y-4">

            <p class="text-sm text-neutral-500">
                Select a specific date to instantly generate a complete transaction ledger for that day.
            </p>

            <x-ui.field required>
                <x-ui.label>Report Date</x-ui.label>
                {{-- Native date input is perfectly reliable here, restricting future dates --}}
                <x-ui.input type="date" wire:model="dailyReportDate" max="{{ now()->format('Y-m-d') }}" />
                <x-ui.error name="dailyReportDate" />
            </x-ui.field>

            <div class="pt-4 flex justify-end gap-3 mt-4 border-t border-black/10 dark:border-white/10">
                <x-ui.button type="button" variant="outline" wire:click="$set('showDailyReportModal', false)">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="downloadDailyReport" icon="arrow-down-tray">
                    Download
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- ========================================== --}}
    {{--        VIEW TRANSACTION MODAL (ALPINE)     --}}
    {{-- ========================================== --}}
    <x-ui.modal id="view-transaction-modal" width="3xl" heading="Transaction Details">
        <template x-if="selectedTx">
            <div class="space-y-6">

                {{-- Header Details (Ref & Dates) --}}
                <div class="flex flex-col md:flex-row justify-between gap-4 bg-neutral-50 dark:bg-white/5 p-4 rounded-xl border border-black/5 dark:border-white/5">
                    <div>
                        <p class="text-xs text-neutral-500 uppercase tracking-wider mb-1">Reference Number</p>
                        <p class="text-lg font-mono font-bold text-electric-blue dark:text-blue-400" x-text="selectedTx.payment_reference || 'N/A'"></p>
                        <div class="flex items-center gap-2 mt-2">
                            <template x-if="selectedTx.status === 'completed'">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400 uppercase tracking-wider">Completed</span>
                            </template>
                            <template x-if="selectedTx.status !== 'completed'">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 uppercase tracking-wider" x-text="capitalize(selectedTx.status)"></span>
                            </template>
                        </div>
                    </div>
                    <div class="md:text-right">
                        <p class="text-xs text-neutral-500 uppercase tracking-wider mb-1">Date & Time</p>
                        <p class="font-medium text-neutral-900 dark:text-white" x-text="formatDate(selectedTx.created_at)"></p>
                        <p class="text-sm text-neutral-500" x-text="formatTime(selectedTx.created_at)"></p>
                    </div>
                </div>

                {{-- People & Payment Info Grid --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-3 border border-black/10 dark:border-white/10 rounded-lg bg-white dark:bg-[#0a1331]">
                        <p class="text-[10px] text-neutral-500 uppercase tracking-wider mb-1">Cashier</p>
                        <p class="font-medium text-neutral-900 dark:text-white flex items-center gap-2">
                            <x-ui.icon name="user" class="size-5 text-neutral-400" />
                            <span x-text="selectedTx.user?.name || 'Unknown'"></span>
                        </p>
                    </div>
                    <div class="p-3 border border-black/10 dark:border-white/10 rounded-lg bg-white dark:bg-[#0a1331]">
                        <p class="text-[10px] text-neutral-500 uppercase tracking-wider mb-1">Customer</p>
                        <div class="font-medium text-neutral-900 dark:text-white flex items-center gap-2">
                            <x-ui.icon name="user-group" class="size-5 text-neutral-400" />
                            <span class="truncate" x-text="selectedTx.customer?.name || 'Walk-in'"></span>
                        </div>
                        <template x-if="selectedTx.customer?.customer_type">
                            <p class="text-[10px] mt-1 text-purple-600 dark:text-purple-400 font-bold" x-text="selectedTx.customer.customer_type.name"></p>
                        </template>
                    </div>
                    <div class="p-3 border border-black/10 dark:border-white/10 rounded-lg bg-white dark:bg-[#0a1331]">
                        <p class="text-[10px] text-neutral-500 uppercase tracking-wider mb-1">Payment Method</p>
                        <p class="font-medium text-neutral-900 dark:text-white flex items-center gap-2">
                            <x-ui.icon name="credit-card" class="size-5 text-neutral-400" />
                            <span x-text="selectedTx.payment_method?.name || 'Unspecified'"></span>
                        </p>
                    </div>
                </div>

                {{-- Items Purchased Table --}}
                <div>
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-white mb-3 flex items-center gap-2">
                        <x-ui.icon name="shopping-bag" class="size-5 text-electric-blue" />
                        Items Purchased
                    </h3>
                    <div class="max-h-[35vh] overflow-y-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-[10px] uppercase text-neutral-500 z-10 border-b border-black/10 dark:border-white/10">
                                <tr>
                                    <th class="px-4 py-3">Product</th>
                                    <th class="px-4 py-3 text-center">Qty</th>
                                    <th class="px-4 py-3 text-right">Price</th>
                                    <th class="px-4 py-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-black/5 dark:divide-white/5 bg-white dark:bg-[#060A23]">
                                <template x-for="item in selectedTx.sale_items" :key="item.id">
                                    <tr class="hover:bg-neutral-50 dark:hover:bg-white/5">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-neutral-900 dark:text-white" x-text="item.product?.brand_name || 'Unknown Product'"></p>
                                            <p class="text-[10px] text-neutral-500" x-text="item.product?.product_code || ''"></p>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="font-medium" x-text="item.quantity"></span>
                                            <span class="text-xs text-neutral-500" x-text="item.unit?.abbreviation || 'Unit'"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-neutral-600 dark:text-neutral-400 font-mono" x-text="formatMoney(item.price_at_moment.amount)"></td>
                                        <td class="px-4 py-3 text-right font-bold text-neutral-900 dark:text-white font-mono" x-text="formatMoney(item.subtotal.amount)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Totals Calculation Block --}}
                <div class="flex flex-col items-end border-t border-black/10 dark:border-white/10 pt-4">
                    <div class="w-full md:w-64 space-y-2 text-sm">
                        <template x-if="selectedTx.discount_amount > 0">
                            <div class="flex justify-between text-green-600 dark:text-green-400">
                                <span>Discount:</span>
                                <span class="font-mono" x-text="'- ' + formatMoney(selectedTx.discount_amount)"></span>
                            </div>
                        </template>

                        <div class="flex justify-between font-bold text-lg text-neutral-900 dark:text-white border-t border-black/5 dark:border-white/10 pt-2 mt-2">
                            <span>Grand Total:</span>
                            <span class="font-mono text-electric-blue dark:text-blue-400" x-text="formatMoney(selectedTx.grand_total.amount)"></span>
                        </div>

                        <div class="flex justify-between text-neutral-500 pt-2">
                            <span>Tendered:</span>
                            <span class="font-mono" x-text="formatMoney(selectedTx.amount_tendered.amount)"></span>
                        </div>
                        <div class="flex justify-between text-neutral-500">
                            <span>Change:</span>
                            <span class="font-mono" x-text="formatMoney(selectedTx.change_amount.amount)"></span>
                        </div>
                    </div>
                </div>

            </div>
        </template>

        {{-- Footer --}}
        <div class="pt-4 flex justify-end gap-3 mt-6 border-t border-black/10 dark:border-white/10">
            <x-ui.button variant="outline" type="button" x-on:click="$dispatch('close-modal', { id: 'view-transaction-modal' })">
                Close
            </x-ui.button>
            <x-ui.button color="primary" icon="printer">
                Print Receipt
            </x-ui.button>
        </div>
    </x-ui.modal>

    {{-- ========================================== --}}
    {{-- DATE RANGE EXPORT MODAL             --}}
    {{-- ========================================== --}}
    <x-ui.modal id="export-range-modal" width="sm" heading="Select Export Range">
        <form wire:submit.prevent="processExport" class="space-y-4">

            <p class="text-sm text-neutral-500">
                Please select the start and end dates for the data you wish to export.
            </p>

            <x-ui.field required>
                <x-ui.label>Date Range</x-ui.label>
                {{-- TallStackUI Date Range Picker --}}
                <x-ui-date range wire:model="exportDateRange" format="YYYY-MM-DD" />
                <x-ui.error name="exportDateRange" />
            </x-ui.field>

            <div class="pt-4 flex justify-end gap-3 mt-4 border-t border-black/10 dark:border-white/10">
                <x-ui.button type="button" variant="outline" wire:click="$dispatch('close-modal', { id: 'export-range-modal' })">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="processExport" icon="arrow-down-tray">
                    Export Data
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
