<div class="max-w-7xl mx-auto space-y-6"
    x-data="{
        items: @entangle('form.orderItems'),
        // Calculate total quantity instantly
        get totalQuantity() {
            return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
        },
        // Calculate total cost (Qty * Cost) instantly
        get totalCost() {
            return this.items.reduce((sum, item) => sum + ((parseFloat(item.quantity) || 0) * (parseFloat(item.cost) || 0)), 0);
        },
        // Helper to format as Philippine Peso
        formatMoney(value) {
            return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(value);
        }
    }">
    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('inventory.pharmacy.purchases') }}" wire:navigate>
            Purchases
        </x-ui.breadcrumbs.item>
        <x-ui.breadcrumbs.item active>
            Create Purchase Order
        </x-ui.breadcrumbs.item>
    </x-ui.breadcrumbs>

    {{-- Header --}}
    <div class="flex items-center gap-5 justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">New Purchase Order</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Create an order request for your suppliers.</p>
        </div>
         <x-ui.button variant="outline" icon="arrow-left" color="neutral" href="{{ route('inventory.pharmacy.purchases') }}" wire:navigate>
             Back to Orders
         </x-ui.button>
    </div>

    <form wire:submit="save" class="space-y-6">

        {{-- TOP SECTION: Purchase Order Details --}}
        <x-ui.card hoverless size="full">
            <x-ui.heading level="h3" size="md" class="mb-4 text-blue-600">Order Information</x-ui.heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Supplier Selection --}}
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

                {{-- Expected Delivery Date --}}
                <x-ui.field>
                    <x-ui.label>Expected Delivery Date</x-ui.label>
                    <x-ui.input
                        type="date"
                        wire:model="form.expected_delivery_date"
                        min="{{ now()->format('Y-m-d') }}"
                    />
                    <x-ui.error name="form.expected_delivery_date" />
                </x-ui.field>

                {{-- Branch (Read Only) --}}
                <x-ui.field>
                    <x-ui.label>Requesting Branch</x-ui.label>
                    <x-ui.input
                        readonly
                        disabled
                        label="Branch"
                        :value="$this->user->branch->name ?? 'Main Branch'"
                    />
                </x-ui.field>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <x-ui.heading level="h3" size="md" class="text-blue-600">Order Items</x-ui.heading>
                    <p class="text-xs text-neutral-500">Add the products you want to order.</p>
                </div>
                <x-ui.button type="button" size="sm" x-on:click="items.push({ product_id: '', unit_id: '', quantity: 1, cost: 0 })" icon="plus">
                    Add Product
                </x-ui.button>
            </div>

            <div class="space-y-4">
                {{-- The Alpine Template Loop --}}
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-4 bg-neutral-50 dark:bg-white/5 rounded-lg relative group border border-transparent dark:border-white/5">

                        {{-- Remove Button --}}
                        <button type="button" x-on:click="items.splice(index, 1)" class="absolute top-3 right-3 text-neutral-400 hover:text-red-500 transition-colors">
                            <x-ui.icon name="x-mark" class="size-5" />
                        </button>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-2 pr-8">

                            {{-- Product Selection --}}
                            <div class="md:col-span-2">
                                <x-ui.field required>
                                    <x-ui.label>Product</x-ui.label>
                                    <x-ui-select.styled
                                        invalidate
                                        x-model="item.product_id"
                                        :options="$this->products"
                                        searchable
                                        select="label:label|value:value"
                                        placeholder="Select product"
                                    />
                                </x-ui.field>
                            </div>

                            {{-- Unit Selection --}}
                            <x-ui.field required>
                                <x-ui.label>Order Unit</x-ui.label>
                                <x-ui-select.styled
                                    invalidate
                                    x-model="item.unit_id"
                                    :options="$this->units"
                                    searchable
                                    select="label:label|value:value"
                                    placeholder="e.g. Box"
                                />
                            </x-ui.field>

                            {{-- Quantity & Cost --}}
                            <div class="flex gap-3">
                                <x-ui.field class="w-1/2" required>
                                    <x-ui.label>Qty</x-ui.label>
                                    <x-ui.input
                                        type="number" step="any" min="0.01"
                                        x-model="item.quantity"
                                        placeholder="0"
                                    />
                                </x-ui.field>

                                <x-ui.field class="w-1/2" required>
                                    <x-ui.label>Unit Cost</x-ui.label>
                                    <x-ui.input
                                        type="number" step="any" min="0"
                                        x-model="item.cost"
                                        placeholder="0.00"
                                    />
                                </x-ui.field>
                            </div>

                        </div>
                    </div>
                </template>
            </div>

            <x-ui.error name="form.orderItems" class="mt-4" />
            <x-ui.error name="form.orderItems.*" class="mt-2 p-3 bg-red-50 dark:bg-red-500/10 rounded-md border border-red-200 dark:border-red-500/20" />
            {{-- REAL-TIME SUMMARY FOOTER --}}
            <div class="mt-6 pt-3 border-t border-black/10 dark:border-white/10 flex flex-col sm:flex-row justify-start items-start gap-6 sm:gap-10">
                {{-- Total Quantity --}}
                <div class="text-right">
                    <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total Items / Qty</p>
                    <p class="text-xs font-bold text-neutral-900 dark:text-white mt-1">
                        <span x-text="items.length" class="text-neutral-400 mr-1"></span>
                        / <span x-text="totalQuantity.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2})"></span>
                    </p>
                </div>

                {{-- Estimated Total Cost --}}
                <div class="text-right">
                    <p class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total</p>
                    <p class="text-xs font-black text-green-600 dark:text-green-400 mt-1" x-text="formatMoney(totalCost)">
                        ₱0.00
                    </p>
                </div>
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <x-ui.button variant="danger" href="#" wire:navigate>
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" size="md" icon="paper-airplane">
                Submit Purchase Order
            </x-ui.button>
        </div>
    </form>
</div>
