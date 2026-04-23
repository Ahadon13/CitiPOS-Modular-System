<div class="max-w-7xl mx-auto space-y-6" x-data="{
    items: @entangle('form.orderItems'),
    receiveType: @entangle('form.receive_type'),

    get totalQuantity() { return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0); },
    get totalCost() { return this.items.reduce((sum, item) => sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.cost) || 0)), 0); },
    formatMoney(value) { return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value); }
}">

    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('inventory.motor-shop.purchases') }}">Purchases</x-ui.breadcrumbs.item>
        <x-ui.breadcrumbs.item active>Record Received Purchase</x-ui.breadcrumbs.item>
    </x-ui.breadcrumbs>

    <div class="flex items-center gap-5 justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Record Delivery</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Log physical deliveries into your inventory.</p>
        </div>
        <x-ui.button variant="outline" icon="arrow-left" color="neutral" href="{{ route('inventory.motor-shop.purchases') }}">
             Back to Orders
         </x-ui.button>
    </div>

    <form x-on:submit.prevent="$wire.save(items)" class="space-y-6">

        {{-- TOP SECTION: Configuration --}}
        <x-ui.card hoverless size="full">
            <x-ui.heading level="h3" size="md" class="mb-6 text-blue-600">Receiving Configuration</x-ui.heading>

            <div class="space-y-6">
                {{-- Type Toggle --}}
                <x-ui.field>
                    <x-ui.label>How are you receiving this delivery?</x-ui.label>
                    <div class="flex flex-col sm:flex-row gap-4 mt-2 w-full">
                        <label class="w-full flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors"
                               :class="receiveType === 'po' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5'">
                            <input type="radio" wire:model.live="form.receive_type" value="po" class="size-4 text-blue-600">
                            <div>
                                <p class="font-bold text-neutral-900 dark:text-white">From existing Purchase Order</p>
                                <p class="text-xs text-neutral-500">I have a pending PO number for this delivery.</p>
                            </div>
                        </label>

                        <label class="w-full flex items-center gap-3 p-4 border rounded-lg cursor-pointer transition-colors"
                               :class="receiveType === 'direct' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5'">
                            <input type="radio" wire:model.live="form.receive_type" value="direct" class="size-4 text-blue-600">
                            <div>
                                <p class="font-bold text-neutral-900 dark:text-white">Direct Receiving (No PO)</p>
                                <p class="text-xs text-neutral-500">I bought this directly without making a PO first.</p>
                            </div>
                        </label>
                    </div>
                </x-ui.field>

                <div class="flex gap-6 w-full">
                    {{-- IF PO --}}
                    <div x-show="receiveType === 'po'" x-cloak class="w-full">
                        <x-ui.field required>
                            <x-ui.label>Pending Purchase Order</x-ui.label>
                            <x-ui-select.styled
                                invalidate
                                wire:model.live="form.purchase_id"
                                :options="$this->pendingPurchases"
                                searchable
                                select="label:label|value:value"
                                placeholder="Select PO Number"
                            />
                            <x-ui.error name="form.purchase_id" />
                        </x-ui.field>
                    </div>

                    {{-- IF DIRECT --}}
                    <div x-show="receiveType === 'direct'" x-cloak class="w-full">
                        <x-ui.field required>
                            <x-ui.label>Supplier</x-ui.label>
                            <x-ui-select.styled
                                invalidate
                                wire:model="form.supplier_id"
                                :options="$this->suppliers"
                                searchable
                                select="label:label|value:value"
                                placeholder="Select a supplier"
                            />
                            <x-ui.error name="form.supplier_id" />
                        </x-ui.field>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- BOTTOM SECTION: Received Items --}}
        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <x-ui.heading level="h3" size="md" class="text-blue-600">Physical Delivery</x-ui.heading>
                    <p class="text-xs text-neutral-500">Verify quantities, enter actual costs, and assign batches.</p>
                </div>

                {{-- Only allow adding random products if it's a DIRECT receive --}}
                <div x-show="receiveType === 'direct'" x-cloak>
                    <x-ui.button type="button" size="sm" x-on:click="items.push({ product_id: '', unit_id: '', quantity: 1, cost: 0, batch_number: '' })" icon="plus">
                        Add Product
                    </x-ui.button>
                </div>
            </div>

            <div class="space-y-4">
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-4 bg-neutral-50 dark:bg-white/5 rounded-lg border border-transparent dark:border-white/10 relative">

                        {{-- Allow row deletion so they can skip items that didn't arrive --}}
                        <button type="button" x-on:click="items.splice(index, 1)" class="absolute top-3 right-3 text-neutral-400 hover:text-red-500">
                            <x-ui.icon name="x-mark" class="size-5" />
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pr-8">

                            {{-- Product Selection/Display --}}
                            <div class="md:col-span-2">
                                <x-ui.field required>
                                    <x-ui.label>Product</x-ui.label>

                                    <template x-if="receiveType === 'po'">
                                        {{-- If PO, product is locked. Display name only. --}}
                                        <div class="h-10 px-3 flex items-center border border-neutral-200 dark:border-white/10 bg-neutral-100 dark:bg-white/5 rounded-md text-sm font-semibold text-neutral-900 dark:text-white">
                                            <span x-text="item.product_name"></span>
                                        </div>
                                    </template>

                                    <template x-if="receiveType === 'direct'">
                                        {{-- If Direct, they choose the product --}}
                                        <x-ui-select.styled
                                            invalidate
                                            x-model="item.product_id"
                                            :options="$this->products"
                                            searchable
                                            select="label:label|value:value"
                                            placeholder="Select product"
                                        />
                                    </template>
                                </x-ui.field>
                            </div>

                            <x-ui.field required>
                                <x-ui.label>Received Unit</x-ui.label>
                                <x-ui-select.styled invalidate x-model="item.unit_id" :options="$this->units" searchable select="label:label|value:value" />
                            </x-ui.field>

                            <x-ui.field required>
                                <x-ui.label>Actual Qty</x-ui.label>
                                <x-ui.input type="number" step="any" min="0.01" x-model="item.quantity" />
                            </x-ui.field>

                            <x-ui.field required>
                                <x-ui.label>Actual Cost</x-ui.label>
                                <x-ui.input type="number" step="any" min="0" x-model="item.cost" />
                            </x-ui.field>

                            <div class="md:col-span-2">
                                <x-ui.field>
                                    <x-ui.label>Batch No.</x-ui.label>
                                    <x-ui.input type="text" x-model="item.batch_number" placeholder="B-123" />
                                </x-ui.field>
                            </div>
                        </div>
                    </div>
                </template>

                {{-- Show message if PO is selected but array is empty --}}
                <div x-show="items.length === 0 && receiveType === 'po' && $wire.form.purchase_id" x-cloak class="p-6 text-center text-neutral-500 bg-neutral-50 dark:bg-white/5 rounded-lg border border-dashed border-neutral-300 dark:border-white/20">
                    No items found for this Purchase Order, or all items were removed.
                </div>
            </div>

            <x-ui.error name="form.orderItems" class="mt-4" />
            <x-ui.error name="form.orderItems.*" class="mt-2 p-3 bg-red-50 dark:bg-red-500/10 rounded-md border border-red-200 dark:border-red-500/20" />

            {{-- Summary Footer --}}
            <div class="mt-6 pt-4 border-t border-black/10 dark:border-white/10 flex flex-col sm:flex-row justify-start items-start gap-6 sm:gap-10">
                <div class="text-right">
                    <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total Items / Qty</p>
                    <p class="text-xs font-bold text-neutral-900 dark:text-white mt-1">
                        <span x-text="items.length" class="text-xs text-neutral-400 mr-1"></span>
                        / <span x-text="totalQuantity.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2})"></span>
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total Actual Value</p>
                    <p class="text-xs font-black text-green-600 dark:text-green-400 mt-1" x-text="formatMoney(totalCost)">₱0.00</p>
                </div>
            </div>
        </x-ui.card>

        <div class="flex justify-end gap-3">
            <x-ui.button variant="danger" wire:loading.attr="disabled" href="#">Cancel</x-ui.button>
            <x-ui.button type="submit" wire:loading.attr="disabled" size="md" icon="check-circle">Confirm & Add to Inventory</x-ui.button>
        </div>
    </form>
</div>
