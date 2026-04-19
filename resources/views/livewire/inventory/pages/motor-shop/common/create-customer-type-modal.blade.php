<div>
    <x-ui.modal id="create-customer-type" width="2xl" heading="Manage Customer Types">

        {{-- TOP SECTION: Add/Edit Form --}}
        <form wire:submit="save" class="space-y-4 bg-neutral-50 dark:bg-white/5 p-4 rounded-lg border border-black/10 dark:border-white/10">

            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">
                    {{ $type_id ? 'Edit Customer Type' : 'Add New Customer Type' }}
                </h3>
                @if($type_id)
                    <button type="button" wire:click="resetForm" class="text-xs text-blue-600 hover:underline">
                        Cancel Edit
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.field required>
                    <x-ui.label>Customer Type Name</x-ui.label>
                    <x-ui.input
                        wire:model="name"
                        placeholder="e.g., Senior Citizen, PWD, VIP"
                    />
                    <x-ui.error name="name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Discount Percentage (%)</x-ui.label>
                    <x-ui.input
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        wire:model="discount_percentage"
                        placeholder="e.g., 20"
                    />
                    <x-ui.error name="discount_percentage" />
                </x-ui.field>
            </div>

            <div class="flex justify-end pt-2">
                <x-ui.button type="submit" size="sm" wire:loading.attr="disabled" wire:target="save" icon="check-circle">
                    {{ $type_id ? 'Update Type' : 'Save Type' }}
                </x-ui.button>
            </div>
        </form>

        {{-- BOTTOM SECTION: List --}}
        <div class="mt-6">

            {{-- Header & Search Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">Existing Types</h3>
                <div class="w-full sm:w-64">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search types..."
                        icon="magnifying-glass"
                        clearable
                    />
                </div>
            </div>

            <div class="max-h-[40vh] overflow-y-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 z-10 border-b border-black/10 dark:border-white/10">
                        <tr>
                            <th class="px-4 py-3">Type Name</th>
                            <th class="px-4 py-3 text-center">Discount</th>
                            <th class="px-4 py-3 text-center">Users</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                        @forelse($this->customerTypes as $type)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors {{ $type_id === $type->id ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">
                                <td class="px-4 py-3 font-medium text-neutral-900 dark:text-white">
                                    {{ $type->name }}
                                </td>
                                <td class="px-4 py-3 text-center text-neutral-600 dark:text-neutral-400">
                                    {{ number_format((float) $type->discount_percentage, 0) }}%
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full bg-neutral-100 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-neutral-600 dark:text-neutral-400">
                                        {{ $type->customers_count }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="edit({{ $type->id }})">
                                            Edit
                                        </x-ui.button>

                                        <x-ui.button
                                            size="xs"
                                            variant="danger"
                                            icon="trash"
                                            wire:click="delete({{ $type->id }})"
                                            wire:custom-confirm="Are you sure you want to delete {{ $type->name }}?"
                                        >
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-neutral-500">
                                    @if($search)
                                        No customer types found matching "<strong>{{ $search }}</strong>".
                                    @else
                                        No customer types found. Create one above.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-ui.pagination minimal wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->customerTypes" />
        </div>

        {{-- Modal Footer --}}
        <div class="pt-4 flex items-center justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
            <x-ui.button variant="outline" color="neutral" type="button" x-on:click="$dispatch('close-modal', { id: 'create-customer-type' })">
                Close
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
