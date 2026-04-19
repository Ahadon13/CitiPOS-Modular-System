<div>
    <x-ui.modal id="manage-branch" width="4xl" heading="Manage Branches">

        {{-- TOP SECTION: Add/Edit Form --}}
        <form wire:submit="save" class="space-y-4 bg-neutral-50 dark:bg-white/5 p-4 rounded-lg border border-black/10 dark:border-white/10">

            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">
                    {{ $branch_id ? 'Edit Branch' : 'Add New Branch' }}
                </h3>
                @if($branch_id)
                    <button type="button" wire:click="resetForm" class="text-xs text-blue-600 hover:underline">
                        Cancel Edit
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Name --}}
                <x-ui.field required>
                    <x-ui.label>Branch Name</x-ui.label>
                    <x-ui.input wire:model="name" placeholder="e.g., Tagum City Main" />
                    <x-ui.error name="name" />
                </x-ui.field>

                {{-- Category --}}
                <x-ui.field required>
                    <x-ui.label>Primary Category</x-ui.label>
                    <x-ui-select.styled
                        invalidate
                        wire:model="product_category_id"
                        :options="$this->categories"
                        searchable
                        placeholder="Select branch category..."
                    />
                    <x-ui.error name="product_category_id" />
                </x-ui.field>

                {{-- Address (Full width) --}}
                <div class="md:col-span-2">
                    <x-ui.field required>
                        <x-ui.label>Full Address</x-ui.label>
                        <x-ui.input wire:model="address" placeholder="e.g., 123 Quezon St, Tagum City" />
                        <x-ui.error name="address" />
                    </x-ui.field>
                </div>

                {{-- Status --}}
                <div class="md:col-span-2 pt-2">
                    <x-ui.checkbox wire:model="is_active" label="Branch is currently active and operational" />
                    <x-ui.error name="is_active" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <x-ui.button type="submit" size="sm" class="w-full sm:w-auto justify-center" wire:loading.attr="disabled" wire:target="save" icon="check-circle">
                    {{ $branch_id ? 'Update Branch' : 'Save Branch' }}
                </x-ui.button>
            </div>
        </form>

        {{-- BOTTOM SECTION: List --}}
        <div class="mt-6">

            {{-- Header & Search Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">Existing Branches</h3>
                <div class="w-full sm:w-64">
                    <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Search branches..." icon="magnifying-glass" clearable />
                </div>
            </div>

            <div class="max-h-[40vh] overflow-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 z-10 border-b border-black/10 dark:border-white/10">
                        <tr>
                            <th class="px-4 py-3">Branch Info</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                        @forelse($this->branches as $branch)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors {{ $branch_id === $branch->id ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">

                                <td class="px-4 py-3">
                                    <span class="font-bold text-neutral-900 dark:text-white block">{{ $branch->name }}</span>
                                    <span class="text-xs text-neutral-500 truncate max-w-[200px] block" title="{{ $branch->address }}">{{ $branch->address ?? 'No address' }}</span>
                                </td>

                                <td class="px-4 py-3 text-neutral-600 dark:text-neutral-400">
                                    <span class="inline-flex items-center rounded-md bg-purple-100 dark:bg-white/10 px-2 py-1 text-xs font-medium text-purple-600 dark:text-neutral-300 ring-1 ring-inset ring-purple-500/20">
                                        {{ $branch->productCategory->name ?? 'Uncategorized' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if($branch->is_active)
                                        <span class="px-2 py-1 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400 uppercase tracking-wider">Active</span>
                                    @else
                                        <span class="px-2 py-1 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 uppercase tracking-wider">Inactive</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="edit({{ $branch->id }})">
                                            Edit
                                        </x-ui.button>

                                        <x-ui.button size="xs" variant="danger" icon="trash" wire:click="delete({{ $branch->id }})" wire:custom-confirm="Are you sure you want to delete {{ $branch->name }}?">
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-neutral-500">
                                    @if($search)
                                        No branches found matching "<strong>{{ $search }}</strong>".
                                    @else
                                        No branches found. Create one above.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-ui.pagination minimal wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->branches" />
        </div>

        {{-- Modal Footer --}}
        <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
            <x-ui.button variant="outline" color="neutral" type="button" class="w-full sm:w-auto justify-center" x-on:click="$dispatch('close-modal', { id: 'manage-branch' })">
                Close
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
