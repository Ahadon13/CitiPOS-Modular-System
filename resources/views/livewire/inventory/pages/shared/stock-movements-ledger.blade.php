@php
    $stockMovementTitle = $stockMovementTitle ?? 'Stock Movements';
    $stockMovementDescription = $stockMovementDescription ?? 'Inventory transaction ledger for stock IN and OUT.';
    $stockMovementEmptyDescription = $stockMovementEmptyDescription ?? 'Inventory transactions will appear here once stock moves in or out.';
    $showStockMovementDateFilters = $showStockMovementDateFilters ?? true;
    $stockMovementModule = $stockMovementModule ?? 'inventory';
@endphp

<x-ui.card hoverless size="full" class="overflow-hidden p-0 border-emerald-500/30">
    <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 bg-emerald-50/30 dark:bg-emerald-900/10 space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h3" size="sm" class="text-emerald-700 dark:text-emerald-400 flex items-center gap-2">
                    <x-ui.icon name="arrows-right-left" class="size-5" />
                    {{ $stockMovementTitle }}
                </x-ui.heading>
                <p class="text-sm text-neutral-500 mt-1">{{ $stockMovementDescription }}</p>
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

            @if($showStockMovementDateFilters)
                <div class="flex items-end gap-3 col-span-1 md:col-span-2">
                    <x-ui.field class="w-full!">
                        <x-ui.label>Date From</x-ui.label>
                        <x-ui.input type="date" wire:model.live="stockMovementDateFrom" />
                    </x-ui.field>

                    <x-ui.field class="w-full!">
                        <x-ui.label>Date To</x-ui.label>
                        <x-ui.input type="date" wire:model.live="stockMovementDateTo" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.button size="sm" variant="outline" icon="x-mark" wire:click="clearStockMovementFilters">
                            Clear
                        </x-ui.button>
                    </x-ui.field>
                </div>
            @else
                <div class="md:col-span-2 flex items-end justify-between gap-3">
                    <p class="text-xs text-neutral-500 dark:text-neutral-400">
                        Date range is controlled by the report filter above.
                    </p>
                    <x-ui.button size="sm" variant="outline" icon="x-mark" wire:click="clearStockMovementFilters">
                        Clear
                    </x-ui.button>
                </div>
            @endif
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

                                $secondaryProductLine = match ($stockMovementModule) {
                                    'pharmacy' => trim(($transaction->product->generic_name ?? '-') . ' ' . (($transaction->product?->dosage || $transaction->product?->form) ? ('- ' . trim(($transaction->product->dosage ?? '') . ' ' . ($transaction->product->form ?? ''))) : '')),
                                    'motor-shop' => trim('Part: ' . (data_get($transaction->product->attributes, 'part_number') ?: $transaction->product->product_code ?: '-') . ' / OEM: ' . (data_get($transaction->product->attributes, 'oem_number') ?: '-')),
                                    default => 'Code: ' . ($transaction->product->product_code ?? '-'),
                                };
                            @endphp

                            <tr class="hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="text-neutral-900 dark:text-white">{{ $transaction->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-neutral-500">{{ $transaction->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-black dark:text-white">{{ $transaction->product->brand_name ?? $transaction->product->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-neutral-500">{{ $secondaryProductLine ?: '-' }}</div>
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
                                        <p class="mt-1 text-sm text-neutral-500">{{ $stockMovementEmptyDescription }}</p>
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
