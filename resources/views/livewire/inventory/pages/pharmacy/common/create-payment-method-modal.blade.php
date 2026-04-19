<x-ui.modal id="create-payment-method" width="2xl" heading="Manage Payment Methods">

    {{-- TOP SECTION: Add/Edit Form --}}
    <form wire:submit="save" class="space-y-4 bg-neutral-50 dark:bg-white/5 p-4 rounded-lg border border-black/10 dark:border-white/10">

        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-bold text-neutral-900 dark:text-white">
                {{ $payment_method_id ? 'Edit Payment Method' : 'Add New Payment Method' }}
            </h3>
            @if($payment_method_id)
                <button type="button" wire:click="resetForm" class="text-xs text-blue-600 hover:underline">
                    Cancel Edit
                </button>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4">
            <x-ui.field required>
                <x-ui.label>Method Name</x-ui.label>
                <x-ui.input
                    wire:model="name"
                    placeholder="e.g., GCash, Credit Card, Cash"
                />
                <x-ui.error name="name" />
            </x-ui.field>
        </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-6 mt-2">
            <x-ui.checkbox wire:model="is_active" label="Active (Visible in POS)" />
            <x-ui.checkbox wire:model="requires_reference" label="Requires Reference Number" />
        </div>

        <div class="flex justify-end pt-2">
            <x-ui.button type="submit" size="sm" class="w-full sm:w-auto justify-center" wire:loading.attr="disabled" wire:target="save" icon="check-circle">
                {{ $payment_method_id ? 'Update Method' : 'Save Method' }}
            </x-ui.button>
        </div>
    </form>

    {{-- BOTTOM SECTION: Payment Method List --}}
    <div class="mt-6">

        {{-- Header & Search Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
            <h3 class="text-sm font-bold text-neutral-900 dark:text-white">Existing Methods</h3>
            <div class="w-full sm:w-64">
                <x-ui.input
                    wire:model.live="search"
                    placeholder="Search methods..."
                    icon="magnifying-glass"
                    clearable
                />
            </div>
        </div>

        <div class="max-h-[40vh] overflow-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 z-10 border-b border-black/10 dark:border-white/10">
                    <tr>
                        <th class="px-4 py-3">Method Name</th>
                        <th class="px-4 py-3">Settings</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                    @forelse($this->paymentMethods as $method)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors {{ $payment_method_id === $method->id ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">
                            <td class="px-4 py-3 font-medium text-neutral-900 dark:text-white">
                                {{ $method->name }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1">
                                    @if($method->is_active)
                                        <span class="text-[10px] font-bold text-green-600 uppercase tracking-wider">Active</span>
                                    @else
                                        <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Inactive</span>
                                    @endif

                                    @if($method->requires_reference)
                                        <span class="text-[10px] text-neutral-500">Requires Ref. #</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="edit({{ $method->id }})">
                                        Edit
                                    </x-ui.button>

                                    <x-ui.button
                                        size="xs"
                                        variant="danger"
                                        icon="trash"
                                        wire:click="delete({{ $method->id }})"
                                        wire:custom-confirm="Are you sure you want to delete {{ $method->name }}?"
                                    >
                                    </x-ui.button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-neutral-500">
                                @if($search)
                                    No methods found matching "<strong>{{ $search }}</strong>".
                                @else
                                    No payment methods found. Create one above.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">
            <x-ui.pagination minimal wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->paymentMethods" />
        </div>
    </div>

    {{-- Modal Footer --}}
    <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
        <x-ui.button variant="outline" color="neutral" type="button" class="w-full sm:w-auto justify-center" x-on:click="$dispatch('close-modal', { id: 'create-payment-method' })">
            Close
        </x-ui.button>
    </div>
</x-ui.modal>
