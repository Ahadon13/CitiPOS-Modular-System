<x-ui.modal id="view-purchase" width="5xl" heading="View Purchase Order" :closeByEscaping="true" :closeByClickingAway="true">
    <div class="space-y-6"
    x-data="{
        po: {},
        items: [],

        applyValues(purchase) {
            this.po = purchase;
            this.items = purchase.purchase_items || [];
        },

        closeModal() {
            $dispatch('close-modal', { id: 'view-purchase' });
            $wire.set('view_purchase', null, false);
        },

        // Helper to format money natively in JS
        formatMoney(cents) {
            return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(cents / 100);
        },

        formatDate(dateString) {
            if (!dateString) return 'Not specified';
            return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }"
    x-init="
        $wire.$watch('view_purchase', value => {
            if (!value) {
                closeModal();
                return;
            }
            applyValues(Alpine.raw(value));
        })
    "
    >
        {{-- Modal Header / Overview --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 bg-neutral-50 dark:bg-white/5 p-5 rounded-lg border border-black/10 dark:border-white/10">

            {{-- Column 1 --}}
            <div class="space-y-4">
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">PO Number</p>
                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400 font-mono" x-text="po.reference_no"></p>
                </div>
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Supplier</p>
                    <p class="text-sm font-semibold text-neutral-900 dark:text-white" x-text="po.supplier?.name || 'Unknown'"></p>
                </div>
            </div>

            {{-- Column 2 --}}
            <div class="space-y-4">
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Order Date</p>
                    <p class="text-sm font-semibold text-neutral-900 dark:text-white" x-text="formatDate(po.created_at)"></p>
                </div>
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide flex items-center gap-1">
                        <x-ui.icon name="truck" class="size-4" />
                        Expected Delivery
                    </p>
                    <p class="text-sm font-bold text-orange-600 dark:text-orange-400" x-text="formatDate(po.expected_delivery_date)"></p>
                </div>
            </div>

            {{-- Column 3 --}}
            <div class="space-y-4 md:text-right">
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Status</p>
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset mt-1"
                          :class="{
                              'bg-green-400/10 text-green-600 ring-green-400/20': po.status === 'completed',
                              'bg-yellow-400/10 text-yellow-600 ring-yellow-400/20': po.status === 'pending',
                              'bg-orange-400/10 text-orange-600 ring-orange-400/20': po.status === 'receiving',
                              'bg-red-400/10 text-red-600 ring-red-400/20': po.status === 'cancelled'
                          }"
                          x-text="(po.status || '').toUpperCase()">
                    </span>
                </div>
                <div>
                    <p class="text-xs text-neutral-500 uppercase tracking-wide">Total Cost</p>
                    <p class="text-xl font-black text-neutral-900 dark:text-white" x-text="formatMoney(po.total_cost?.amount || 0)"></p>
                </div>
            </div>
        </div>

        {{-- Items Table --}}
        <div class="max-h-[50vh] overflow-y-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 dark:text-neutral-400 border-b border-black/10 dark:border-white/10 z-10">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3 text-center">Unit</th>
                        <th class="px-4 py-3 text-center">Qty Ordered</th>
                        <th class="px-4 py-3 text-center">Qty Received</th>
                        <th class="px-4 py-3 text-right">Unit Cost</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                    <template x-for="item in items" :key="item.id">
                        <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                            <td class="px-4 py-3">
                                <p class="font-bold text-neutral-900 dark:text-white" x-text="item.product?.brand_name + ' (' + item.product?.dosage + ')'"></p>
                                <p class="text-xs text-neutral-500" x-text="item.product?.generic_name"></p>
                            </td>
                            <td class="px-4 py-3 text-center font-medium text-neutral-700 dark:text-neutral-300" x-text="item.unit?.name"></td>
                            <td class="px-4 py-3 text-center text-neutral-900 dark:text-white" x-text="parseFloat(item.quantity_ordered)"></td>

                            {{-- Highlight received quantity if it doesn't match ordered --}}
                            <td class="px-4 py-3 text-center font-bold"
                                :class="parseFloat(item.quantity_received) !== parseFloat(item.quantity_ordered) ? 'text-orange-500' : 'text-neutral-900 dark:text-white'"
                                x-text="parseFloat(item.quantity_received)">
                            </td>

                            <td class="px-4 py-3 text-right text-neutral-700 dark:text-neutral-300" x-text="formatMoney(item.cost_per_unit.amount || 0)"></td>

                            {{-- Calculate Subtotal (Using received qty if > 0, else ordered qty) --}}
                            <td class="px-4 py-3 text-right font-bold text-neutral-900 dark:text-white"
                                x-text="formatMoney((parseFloat(item.quantity_received) > 0 ? parseFloat(item.quantity_received) : parseFloat(item.quantity_ordered)) * (item.cost_per_unit.amount || 0))">
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Modal Actions --}}
        <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-black/10 dark:border-white/10">
            <x-ui.button variant="outline" wire:loading.attr="disabled" wire:target="downloadExcel" color="neutral" type="button" x-on:click="closeModal()">
                Close
            </x-ui.button>
            {{-- Export Button triggers Laravel Excel --}}
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="downloadExcel" icon="arrow-down-tray" wire:click="downloadExcel">
                Download / Print
            </x-ui.button>
        </div>
    </div>
</x-ui.modal>
