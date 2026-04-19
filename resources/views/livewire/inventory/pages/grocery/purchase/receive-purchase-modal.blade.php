<x-ui.modal id="receive-purchase" width="5xl" heading="Receive Purchase Order" :closeByEscaping="false" :closeButton="false" :closeByClickingAway="false">
    <div class="space-y-6"
    x-data="{
        original: {},
        items: @entangle('receiveItems'),

        setOriginal(purchase) {
            this.original = JSON.parse(JSON.stringify(purchase)); // deep clone
        },

        applyValues(purchase) {
            // Set the header Reference No
            $wire.set('referenceNo', purchase.reference_no, false);
            $wire.set('supplierName', purchase.supplier ? purchase.supplier.name : 'Unknown Supplier', false);

            // Map the nested items exactly like you did in PHP, but natively in JS
            const mappedItems = (purchase.purchase_items || []).map(item => {

                // Note: If your MoneyCast serializes cost_per_unit as raw cents (e.g., 8000),
                // you will need to divide it by 100 here in JS just like you did in PHP.
                // e.g. let cost = parseFloat(item.cost_per_unit) / 100;
                let cost = parseFloat(item.cost_per_unit.amount) / 100;

                return {
                    purchase_item_id: item.id,
                    product_id: item.product_id,
                    product_name: item.product ? (item.product.brand_name + ' - ' + (item.product.product_code || 'No code')) : 'Unknown',
                    actual_unit_id: item.unit_id,
                    actual_quantity: parseFloat(item.quantity_ordered),
                    actual_cost: cost,
                    batch_number: '',
                };
            });

            this.items = mappedItems;
        },

        resetForm() {
            if (!this.original) return;
            this.applyValues(this.original);
            $dispatch('close-modal', { id: 'receive-purchase' });
        }
    }"
    x-init="
        $wire.$watch('selected_purchase', value => {
            if (!value) {
                $dispatch('close-modal', { id: 'receive-purchase' });
                return;
            }

            // 1. Store original values
            setOriginal(Alpine.raw(value));

            // 2. Apply values to Livewire form
            applyValues(Alpine.raw(value));
        })
    "
    >
        {{-- Header Info --}}
        <div class="mb-4">
            {{-- Reference No --}}
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Verifying quantities for PO:
                <span class="font-bold text-blue-600" x-text="$wire.referenceNo"></span>
            </p>
            {{-- Supplier --}}
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Supplier:
                <span class="font-bold text-blue-600" x-text="$wire.supplierName"></span>
            </p>
        </div>

        {{-- Main Form --}}
        <form class="space-y-5" wire:submit.prevent="submit">

            <div class="max-h-[60vh] overflow-y-auto space-y-4 pr-2 custom-scrollbar">
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-4 bg-neutral-50 dark:bg-white/5 rounded-lg border border-black/10 dark:border-white/10">

                        <div class="mb-3">
                            <span class="text-sm font-bold text-neutral-900 dark:text-white" x-text="item.product_name"></span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <x-ui.field required>
                                <x-ui.label>Received Unit</x-ui.label>
                                <x-ui-select.styled invalidate x-model="item.actual_unit_id" :options="$this->units" searchable select="label:label|value:value" />
                            </x-ui.field>

                            <x-ui.field required>
                                <x-ui.label>Actual Qty</x-ui.label>
                                <x-ui.input type="number" step="any" min="0.01" x-model="item.actual_quantity" />
                            </x-ui.field>

                            <x-ui.field required>
                                <x-ui.label>Actual Cost</x-ui.label>
                                <x-ui.input type="number" step="any" min="0" x-model="item.actual_cost" />
                            </x-ui.field>

                            <x-ui.field required>
                                <x-ui.label>Expiration Date</x-ui.label>
                                <x-ui.input type="date" x-model="item.expiration_date" min="{{ now()->format('Y-m-d') }}" />
                            </x-ui.field>

                            <x-ui.field>
                                <x-ui.label>Batch No.</x-ui.label>
                                <x-ui.input type="text" x-model="item.batch_number" placeholder="B-1234" />
                            </x-ui.field>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Validation Errors --}}
            <x-ui.error name="receiveItems" />
            <x-ui.error name="receiveItems.*" class="p-3 bg-red-50 dark:bg-red-500/10 rounded-md border border-red-200 dark:border-red-500/20" />

            {{-- Submit / Cancel Buttons --}}
            <div class="pt-4 flex items-center justify-between gap-2 border-t border-black/10 dark:border-white/10">
                <div>
                    <x-ui.button variant="outline" color="neutral" type="button" x-on:click="resetForm()">
                        Close
                    </x-ui.button>
                </div>
                <div class="flex items-center gap-2">
                    <x-ui.button
                            type="button"
                            variant="danger"
                            icon="trash"
                            wire:click="cancelOrder"
                            wire:custom-confirm="Are you sure you want to cancel this entire Purchase Order? This action cannot be undone."
                        >
                            Cancel PO
                        </x-ui.button>
                    <x-ui.button type="submit" icon="check-circle" wire:custom-confirm="Please confirm that the received quantities and costs are correct before submitting.">
                        Confirm Receiving
                    </x-ui.button>
                </div>
            </div>

        </form>
    </div>
</x-ui.modal>
