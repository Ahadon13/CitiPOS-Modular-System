<x-ui.modal id="adjust-stock" width="lg" heading="Adjust Stock" :closeByEscaping="true" :closeByClickingAway="true">
    {{-- While open, this modal takes scans: a scan picks the product and unit, the same barcode again adds 1. --}}
    <div
        class="space-y-6"
        x-data="barcodeDialogScanner('adjust-stock', (code) => $wire.scanForAdjustment(code))"
        x-on:barcode-unresolved.window="BarcodeScanner.beep('error')"
        {{-- After a scan, focus the quantity so Enter confirms the adjustment. --}}
        x-on:adjust-stock-scanned.window="setTimeout(() => { const input = $el.querySelector('input[data-adjust-qty]'); input?.focus(); input?.select(); }, 50)"
    >
        <form wire:submit.prevent="submit" class="space-y-5">

            {{-- Opened with "Scan to Adjust": wait for the first scan. --}}
            @if($awaitingScan && ! $adjust_product)
                <div class="flex flex-col items-center text-center gap-3 rounded-xl border-2 border-dashed border-emerald-500/40 bg-emerald-50/50 dark:bg-emerald-900/10 px-6 py-10">
                    <div class="p-3 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                        <x-ui.icon name="qr-code" class="size-8" />
                    </div>
                    <p class="text-base font-bold text-neutral-900 dark:text-white">Scan a product barcode</p>
                    <p class="text-sm text-neutral-500 max-w-xs">
                        The scanned unit is used for counting &mdash; scan a box barcode to count boxes. Scan the same barcode again to add 1.
                    </p>
                </div>
            @endif

            @if($adjust_product)
            {{-- Header Info --}}
            <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                <p class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wide font-bold mb-1">Target Product</p>
                <p class="text-lg font-bold text-neutral-900 dark:text-white">
                    {{ $adjust_product['brand_name'] }}
                </p>
            </div>

            @if($scanSession || $lastScannedCode)
                <p class="-mt-2 flex items-center gap-1.5 text-xs text-emerald-700 dark:text-emerald-400">
                    <x-ui.icon name="qr-code" class="size-4" />
                    @if($lastScannedCode)
                        Scanned <span class="font-mono">{{ $lastScannedCode }}</span> &mdash; scan it again to add 1, press Enter to confirm.
                    @else
                        Scanner ready &mdash; scan the product to count it.
                    @endif
                </p>
            @endif

            {{-- Adjustment Type Toggle --}}
            <x-ui.field>
                <x-ui.label>Adjustment Type</x-ui.label>
                <div class="grid grid-cols-2 gap-4 mt-1">
                    <label class="flex items-center justify-center gap-2 p-3 border rounded-lg cursor-pointer transition-colors" :class="$wire.adjustment_type === 'deduct' ? 'border-rose-500 bg-rose-50/50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400' : 'border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5'">
                        <input type="radio" wire:model.live="adjustment_type" value="deduct" class="hidden">
                        <x-ui.icon name="minus-circle" class="size-5" />
                        <span class="font-bold">Remove Stock</span>
                    </label>

                    <label class="flex items-center justify-center gap-2 p-3 border rounded-lg cursor-pointer transition-colors" :class="$wire.adjustment_type === 'add' ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400' : 'border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5'">
                        <input type="radio" wire:model.live="adjustment_type" value="add" class="hidden">
                        <x-ui.icon name="plus-circle" class="size-5" />
                        <span class="font-bold">Add Stock</span>
                    </label>
                </div>
            </x-ui.field>

            <div class="grid grid-cols-1 gap-5">

                {{-- DEDUCT FIELDS --}}
                @if($adjustment_type === 'deduct')
                    <x-ui.field required>
                        <x-ui.label>Select Target Batch</x-ui.label>
                        <x-ui-select.styled
                            invalidate
                            wire:model="selected_batch_id"
                            :options="$this->activeBatches"
                            searchable
                            select="label:label|value:value"
                            placeholder="Select a batch to deduct from..."
                        />
                        <x-ui.error name="selected_batch_id" />
                    </x-ui.field>
                @endif

                {{-- ADD FIELDS --}}
                @if($adjustment_type === 'add')
                    <div class="grid grid-cols-1 gap-4">
                        <x-ui.field required>
                            <x-ui.label>Batch Number</x-ui.label>
                            <x-ui.input wire:model="new_batch_number" />
                            <x-ui.error name="new_batch_number" />
                        </x-ui.field>
                    </div>
                @endif

                {{-- COMMON FIELDS --}}
                @if(count($this->adjustPackagings) > 1)
                    <x-ui.field>
                        <x-ui.label>Count in</x-ui.label>
                        <select
                            wire:model.live="adjust_packaging_id"
                            class="w-full rounded-box border border-black/10 dark:border-white/10 bg-white dark:bg-deep-space/50 p-2 text-sm text-neutral-800 dark:text-neutral-300 focus:ring-2 focus:outline-none focus:ring-neutral-900/15"
                        >
                            @foreach($this->adjustPackagings as $packaging)
                                <option value="{{ $packaging['value'] }}">{{ $packaging['label'] }}</option>
                            @endforeach
                        </select>
                    </x-ui.field>
                @endif

                <x-ui.field required>
                    <x-ui.label>Quantity <span class="text-xs font-normal">(in {{ $this->adjustUnitLabel() }})</span></x-ui.label>
                    <x-ui.input type="number" step="any" min="0.01" wire:model="quantity" data-adjust-qty />
                    <x-ui.error name="quantity" />
                    @if($this->adjustConversionFactor() != 1)
                        <p class="text-[11px] text-neutral-500 mt-1">
                            Saved as {{ rtrim(rtrim(number_format($this->baseQuantity(), 4, '.', ''), '0'), '.') }} {{ $adjust_product['base_unit'] ?? 'pcs' }}
                            (1 {{ $this->adjustUnitLabel() }} = {{ rtrim(rtrim(number_format($this->adjustConversionFactor(), 4, '.', ''), '0'), '.') }} {{ $adjust_product['base_unit'] ?? 'pcs' }}).
                        </p>
                    @endif
                </x-ui.field>
            </div>
            @endif

            {{-- Actions --}}
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
                <x-ui.button variant="outline" color="neutral" type="button" x-on:click="$dispatch('close-modal', { id: 'adjust-stock' })">
                    Cancel
                </x-ui.button>
                @if($adjust_product)
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="submit" :color="$adjustment_type === 'deduct' ? 'rose' : 'emerald'" icon="check-circle">
                    Confirm Adjustment
                </x-ui.button>
                @endif
            </div>

        </form>
    </div>
</x-ui.modal>
