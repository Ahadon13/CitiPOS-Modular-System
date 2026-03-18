<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Transactions</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Monitor your daily sales and revenue</p>
        </div>
        <div class="flex items-center gap-3 justify-end">
            <x-ui.button variant="outline" icon="arrow-path" wire:click="$refresh">
                Refresh
            </x-ui.button>
            <x-ui.button variant="outline" icon="document-text" wire:click="openDailyReportModal">
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

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- 1. Total Transactions --}}
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500!">
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
        <x-ui.card hoverless size="full" class="border-l-4 border-l-green-500!">
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
        <x-ui.card hoverless size="full" class="border-l-4 border-l-indigo-500!">
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
        <x-ui.card hoverless size="full" class="border-l-4 border-l-purple-500!">
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
                    wire:click="exportTransactions"
                    wire:loading.attr="disabled"
                    wire:target="exportTransactions"
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
                                <th class="px-6 py-4">Date & Time</th>
                                <th class="px-6 py-4">Cashier</th>
                                <th class="px-6 py-4">Customer</th>
                                <th class="px-6 py-4 text-center">Items</th>
                                <th class="px-6 py-4 text-center">Payment</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4 text-right">Grand Total</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->transactions as $transaction)
                                <tr class="hover:bg-white/5 transition-colors group">
                                    <td class="px-6 py-4">
                                        <span class="font-mono font-medium text-blue-600 dark:text-blue-400">{{ $transaction->reference_no }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-neutral-900 dark:text-white">{{ $transaction->created_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-neutral-500">{{ $transaction->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-neutral-900 dark:text-white">
                                        {{ $transaction->user->name ?? 'Unknown' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $transaction->customer->name ?? 'Walk-in' }}
                                    </td>
                                    <td class="px-6 py-4 text-center text-neutral-600 dark:text-neutral-400">
                                        {{ $transaction->sale_items_count }} items
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center rounded-md bg-neutral-100 dark:bg-white/5 px-2 py-1 text-xs font-medium text-neutral-700 dark:text-neutral-300 ring-1 ring-inset ring-neutral-500/20">
                                            {{ $transaction->paymentMethod->name ?? 'Unspecified' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($transaction->status === 'completed')
                                            <span class="inline-flex items-center rounded-md bg-green-400/10 px-2 py-1 text-xs font-medium text-green-600 dark:text-green-400 ring-1 ring-inset ring-green-400/20">
                                                Completed
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-red-400/10 px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 ring-1 ring-inset ring-red-400/20">
                                                {{ ucfirst($transaction->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right text-neutral-900 dark:text-white font-bold tracking-tight">
                                        @money($transaction->grand_total)
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <x-ui.button size="xs" variant="ghost" icon="eye" href="#">View</x-ui.button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-24 text-center">
                                        <x-ui.empty>
                                            <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                                <x-ui.icon name="document-text" class="size-6" />
                                            </x-ui.empty.media>

                                            <x-ui.empty.contents>
                                                <x-ui.heading>No transactions found</x-ui.heading>
                                                <x-ui.text class="opacity-70">
                                                    Start selling at your POS to see transactions appear here.
                                                </x-ui.text>

                                                <x-ui.button icon="shopping-cart" size="sm" class="mt-3" href="{{ route('pos.pharmacy.process-sale') }}">
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
</div>
