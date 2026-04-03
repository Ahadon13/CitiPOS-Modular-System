<div>
    <x-ui.modal id="manage-product-category" width="3xl" heading="Manage Product Categories">

        {{-- TOP SECTION: Add/Edit Form --}}
        <form wire:submit="save" class="space-y-4 bg-neutral-50 dark:bg-white/5 p-4 rounded-lg border border-black/10 dark:border-white/10">

            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">
                    {{ $category_id ? 'Edit Category' : 'Add New Category' }}
                </h3>
                @if($category_id)
                    <button type="button" wire:click="resetForm" class="text-xs text-blue-600 hover:underline">
                        Cancel Edit
                    </button>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                {{-- Name --}}
                <div class="md:col-span-2">
                    <x-ui.field required>
                        <x-ui.label>Category Name</x-ui.label>
                        <x-ui.input wire:model="name" placeholder="e.g., Pain Relievers, Antibiotics, Vitamins" />
                        <x-ui.error name="name" />
                    </x-ui.field>
                </div>

                {{-- Description --}}
                <div class="md:col-span-2">
                    <x-ui.field>
                        <x-ui.label>Description (Optional)</x-ui.label>
                        <textarea
                            wire:model="description"
                            rows="2"
                            class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-blue-500 custom-scrollbar"
                            placeholder="Briefly describe this category..."
                        ></textarea>
                        <x-ui.error name="description" />
                    </x-ui.field>
                </div>

                {{-- Status --}}
                <div class="md:col-span-2 pt-1">
                    <x-ui.checkbox wire:model="is_active" label="Category is active and available for selection" />
                    <x-ui.error name="is_active" />
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <x-ui.button type="submit" size="sm" wire:loading.attr="disabled" wire:target="save" icon="check-circle">
                    {{ $category_id ? 'Update Category' : 'Save Category' }}
                </x-ui.button>
            </div>
        </form>

        {{-- BOTTOM SECTION: List --}}
        <div class="mt-6">

            {{-- Header & Search Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">Existing Categories</h3>
                <div class="w-full sm:w-64">
                    <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Search categories..." icon="magnifying-glass" clearable />
                </div>
            </div>

            <div class="max-h-[40vh] overflow-y-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 z-10 border-b border-black/10 dark:border-white/10">
                        <tr>
                            <th class="px-4 py-3">Category Name</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                        @forelse($this->categories as $category)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors {{ $category_id === $category->id ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">

                                <td class="px-4 py-3">
                                    <span class="font-bold text-neutral-900 dark:text-white block">{{ $category->name }}</span>
                                    @if($category->description)
                                        <span class="text-xs text-neutral-500 truncate max-w-[250px] block" title="{{ $category->description }}">
                                            {{ $category->description }}
                                        </span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if($category->is_active ?? true)
                                        <span class="px-2 py-1 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400 uppercase tracking-wider">Active</span>
                                    @else
                                        <span class="px-2 py-1 rounded text-[10px] font-bold bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400 uppercase tracking-wider">Inactive</span>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="edit({{ $category->id }})">
                                            Edit
                                        </x-ui.button>

                                        <x-ui.button size="xs" variant="danger" icon="trash" wire:click="delete({{ $category->id }})" wire:custom-confirm="Are you sure you want to delete {{ $category->name }}?">
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-neutral-500">
                                    @if($search)
                                        No categories found matching "<strong>{{ $search }}</strong>".
                                    @else
                                        No categories found. Create one above.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-ui.pagination minimal wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->categories" />
        </div>

        {{-- Modal Footer --}}
        <div class="pt-4 flex items-center justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
            <x-ui.button variant="outline" color="neutral" type="button" x-on:click="$dispatch('close-modal', { id: 'manage-product-category' })">
                Close
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
