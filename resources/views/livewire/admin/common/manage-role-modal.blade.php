<div>
    <x-ui.modal id="manage-role" width="4xl" heading="Manage Access Roles">

        {{-- TOP SECTION: Add/Edit Form --}}
        <form wire:submit="save" class="space-y-4 bg-neutral-50 dark:bg-white/5 p-4 rounded-lg border border-black/10 dark:border-white/10">

            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">
                    {{ $role_id ? 'Edit Role' : 'Create New Role' }}
                </h3>
                @if($role_id)
                <button type="button" wire:click="resetForm" class="text-xs text-blue-600 hover:underline">
                    Cancel Edit
                </button>
                @endif
            </div>

            {{-- Role Name --}}
            <x-ui.field required>
                <x-ui.label>Role Name</x-ui.label>
                <x-ui.input wire:model="name" placeholder="e.g., Regional Manager, Junior Pharmacist" />
                <x-ui.error name="name" />
            </x-ui.field>

            <div class="flex justify-end pt-2">
                <x-ui.button type="submit" size="sm" class="w-full sm:w-auto justify-center" wire:loading.attr="disabled" wire:target="save" icon="check-circle">
                    {{ $role_id ? 'Update Role' : 'Save Role' }}
                </x-ui.button>
            </div>
        </form>

        {{-- BOTTOM SECTION: List --}}
        <div class="mt-6">

            {{-- Header & Search Bar --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                <h3 class="text-sm font-bold text-neutral-900 dark:text-white">Existing Roles</h3>
                <div class="w-full sm:w-64">
                    <x-ui.input wire:model.live.debounce.300ms="search" placeholder="Search roles..." icon="magnifying-glass" clearable />
                </div>
            </div>

            <div class="max-h-[40vh] overflow-auto border border-black/10 dark:border-white/10 rounded-lg custom-scrollbar">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="sticky top-0 bg-neutral-100 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 z-10 border-b border-black/10 dark:border-white/10">
                        <tr>
                            <th class="px-4 py-3">Role Name</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                        @forelse($this->roles as $role)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors {{ $role_id === $role->id ? 'bg-blue-50/50 dark:bg-blue-900/20' : '' }}">

                            <td class="px-4 py-3">
                                <span class="font-bold text-neutral-900 dark:text-white block">
                                    {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                </span>
                            </td>

                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(in_array($role->name, ['super-admin', 'admin']))
                                    <span class="text-[10px] text-neutral-400 italic">System Protected</span>
                                    @else
                                    <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="edit({{ $role->id }})">
                                        Edit
                                    </x-ui.button>

                                    <x-ui.button size="xs" variant="danger" icon="trash" wire:click="delete({{ $role->id }})" wire:custom-confirm="Are you sure you want to delete the {{ $role->name }} role?">
                                    </x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-neutral-500">
                                <x-ui.empty>
                                    <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-16 flex items-center justify-center">
                                        <x-ui.icon name="user-plus" class="size-8 text-neutral-400" />
                                    </x-ui.empty.media>
                                    <x-ui.empty.contents>
                                        <x-ui.text class="text-lg">No roles found.</x-ui.text>
                                        <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                            Create custom access roles to manage user permissions effectively.
                                        </x-ui.text>
                                    </x-ui.empty.contents>
                                </x-ui.empty>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-ui.pagination minimal wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->roles" />
        </div>

        {{-- Modal Footer --}}
        <div class="pt-4 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-black/10 dark:border-white/10 mt-6">
            <x-ui.button variant="outline" color="neutral" type="button" class="w-full sm:w-auto justify-center" x-on:click="$dispatch('close-modal', { id: 'manage-role' })">
                Close
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
