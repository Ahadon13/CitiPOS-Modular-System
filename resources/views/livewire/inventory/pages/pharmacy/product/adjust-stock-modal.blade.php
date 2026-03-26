<x-ui.modal id="adjust-stock" width="lg" heading="Adjust Stock" :closeByEscaping="true" :closeByClickingAway="true">
    <div class="space-y-6">
        <form wire:submit.prevent="submit" class="space-y-5">

            @if($adjust_product)
            {{-- Header Info --}}
            <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-100 dark:border-blue-800">
                <p class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wide font-bold mb-1">Target Product</p>
                <p class="text-lg font-bold text-neutral-900 dark:text-white">
                    {{ $adjust_product['brand_name'] }}
                    <span class="text-sm font-normal text-neutral-500">({{ $adjust_product['generic_name'] }})</span>
                </p>
            </div>
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
                    <div class="grid grid-cols-2 gap-4">
                        <x-ui.field required>
                            <x-ui.label>Batch Number</x-ui.label>
                            <x-ui.input wire:model="new_batch_number" />
                            <x-ui.error name="new_batch_number" />
                        </x-ui.field>
                        <x-ui.field required>
                            <x-ui.label>Expiry Date</x-ui.label>
                            <x-ui.input type="date" wire:model="new_expiry_date" />
                            <x-ui.error name="new_expiry_date" />
                        </x-ui.field>
                    </div>
                @endif

                {{-- COMMON FIELDS --}}
                <x-ui.field required>
                    <x-ui.label>Quantity <span class="text-xs font-normal">(in {{ $adjust_product['base_unit'] ?? 'pcs' }})</span></x-ui.label>
                    <x-ui.input type="number" step="any" min="0.01" wire:model="quantity" />
                    <x-ui.error name="quantity" />
                </x-ui.field>
            </div>

            {{-- Actions --}}
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
                <x-ui.button variant="outline" color="neutral" type="button" x-on:click="$dispatch('close-modal', { id: 'adjust-stock' })">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="submit" :color="$adjustment_type === 'deduct' ? 'rose' : 'emerald'" icon="check-circle">
                    Confirm Adjustment
                </x-ui.button>
            </div>

        </form>
    </div>
</x-ui.modal>
