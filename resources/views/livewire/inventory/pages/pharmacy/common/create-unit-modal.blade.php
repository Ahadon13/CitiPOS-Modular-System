<div>
    <x-ui.modal id="create-unit" width="2xl" heading="Manage Measurement Units">

        {{-- TOP SECTION: Add/Edit Form --}}
        <form wire:submit="save" class="space-y-4 bg-neutral-50 dark:bg-white/5 p-4 rounded-lg border border-black/10 dark:border-white/10">

            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">
                    {{ $unit_id ? 'Edit Unit' : 'Add New Unit' }}
                </h3>
                @if($unit_id)
                    <button type="button" wire:click="resetForm" class="text-xs text-blue-600 hover:underline">
                        Cancel Edit
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.field required>
                    <x-ui.label>Unit Name</x-ui.label>
                    <x-ui.input
                        wire:model="name"
                        placeholder="e.g., Piece, Box, Bottle"
                    />
                    <x-ui.error name="name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Abbreviation</x-ui.label>
                    <x-ui.input
                        wire:model="abbreviation"
                        placeholder="e.g., pcs, bx, btl"
                    />
                    <x-ui.error name="abbreviation" />
                </x-ui.field>
            </div>

            <div class="flex justify-end pt-2">
                <x-ui.button type="submit" size="sm" class="w-full sm:w-auto justify-center" wire:loading.attr="disabled" wire:target="save" icon="check-circle">
                    {{ $unit_id ? 'Update Unit' : 'Save Unit' }}
                </x-ui.button>
            </div>
        </form>

        {{-- BOTTOM SECTION: List --}}
        <div class="mt-6">

            {{-- Header & Search Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">Existing Units</h3>
                <div class="w-full sm:w-64">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search units..."
                        icon="magnifying-glass"
                        clearable
                    />
                </div>
            </div>

            <div class="max-h-[40vh] overflow-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 z-10 border-b border-black/10 dark:border-white/10">
                        <tr>
                            <th class="px-4 py-3">Unit Name</th>
                            <th class="px-4 py-3">Abbreviation</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                        @forelse($this->units as $unit)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors {{ $unit_id === $unit->id ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">
                                <td class="px-4 py-3 font-medium text-neutral-900 dark:text-white">
                                    {{ $unit->name }}
                                </td>
                                <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400">
                                    <span class="inline-flex items-center rounded-md bg-neutral-100 dark:bg-white/10 px-2 py-1 text-xs font-medium text-neutral-600 dark:text-neutral-300 ring-1 ring-inset ring-neutral-500/20">
                                        {{ $unit->abbreviation }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="edit({{ $unit->id }})">
                                            Edit
                                        </x-ui.button>

                                        <x-ui.button
                                            size="xs"
                                            variant="danger"
                                            icon="trash"
                                            wire:click="delete({{ $unit->id }})"
                                            wire:custom-confirm="Are you sure you want to delete {{ $unit->name }}?"
                                        >
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-neutral-500">
                                    @if($search)
                                        No units found matching "<strong>{{ $search }}</strong>".
                                    @else
                                        No units found. Create one above.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-ui.pagination minimal wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->units" />
        </div>

        {{-- Modal Footer --}}
        <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
            <x-ui.button variant="outline" color="neutral" type="button" class="w-full sm:w-auto justify-center" x-on:click="$dispatch('close-modal', { id: 'create-unit' })">
                Close
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
