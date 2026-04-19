<div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 px-3 py-4 sm:p-5">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="min-w-0">
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">User Management</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Manage system access, roles, and branch assignments.</p>
        </div>
        <div class="grid grid-cols-1 sm:flex sm:items-center gap-3 w-full sm:w-auto">
            <x-ui.button size="sm" icon="plus" class="w-full sm:w-auto justify-center" x-on:click="$dispatch('open-modal', { id: 'manage-user' })">
                Add New User
            </x-ui.button>
            <x-ui.button size="sm" icon="plus" class="w-full sm:w-auto justify-center" x-on:click="$dispatch('open-modal', { id: 'manage-role' })">
                Add New Role
            </x-ui.button>
        </div>
    </div>

    {{-- Users Table --}}
    <x-ui.card hoverless size="full" class="p-0">
        <div class="px-3 sm:px-6 py-4 sm:py-5 border-b border-black/10 dark:border-white/10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="w-full md:w-72">
                <x-ui.input wire:model.live.debounce.300ms="search" leftIcon="magnifying-glass" clearable placeholder="Search name or username..." class="w-full" />
            </div>

            <div class="w-full sm:w-48">
                <x-ui.field class="mb-0 w-full">
                    <select wire:model.live="roleFilter" class="w-full text-sm rounded-lg border-neutral-300 dark:border-neutral-700 dark:bg-card text-neutral-700 dark:text-neutral-200 focus:ring-blue-500">
                        <option value="">All Roles</option>
                        @foreach($this->roles as $roleOption)
                        <option value="{{ $roleOption['value'] }}">{{ $roleOption['label'] }}</option>
                        @endforeach
                    </select>
                </x-ui.field>
            </div>
        </div>

        <div class="w-full overflow-x-auto custom-scrollbar">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                    <tr>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4">Branch</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-white dark:bg-[#060A23]">
                    @forelse ($this->users as $user)
                    <tr class="hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-neutral-900 dark:text-white">{{ $user->name }}</div>
                            <div class="text-xs text-neutral-500">{{ $user->username }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-md bg-indigo-50 dark:bg-white/10 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-300 ring-1 ring-inset ring-indigo-600/20">
                                {{ ucfirst(str_replace('-', ' ', $user->roles->first()?->name ?? 'None')) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-neutral-600 dark:text-neutral-400 font-medium">
                            {{ $user->branch->name ?? 'All Branches (HQ)' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <x-ui.button size="xs" variant="outline" icon="pencil-square" wire:click="edit({{ $user->id }})">
                                    Edit
                                </x-ui.button>
                                @if(auth()->id() !== $user->id)
                                <x-ui.button size="xs" variant="danger" icon="trash" wire:click="delete({{ $user->id }})" wire:custom-confirm="Are you sure you want to remove {{ $user->name }}?"></x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-neutral-500">
                            <x-ui.empty>
                                <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-16 flex items-center justify-center">
                                    <x-ui.icon name="user-plus" class="size-8 text-neutral-400" />
                                </x-ui.empty.media>
                                <x-ui.empty.contents>
                                    <x-ui.text class="text-lg">No users found.</x-ui.text>
                                    <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400 mt-2">Start by adding a new user to manage the system.</x-ui.text>
                                    <div class="mt-4">
                                        <x-ui.button size="sm" icon="plus" x-on:click="$dispatch('open-modal', { id: 'manage-user' })">
                                            Add User
                                        </x-ui.button>
                                    </div>
                                </x-ui.empty.contents>
                            </x-ui.empty>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center w-full">
            <x-ui.pagination
                wire:model.live="perPage"
                :per-page-options="$perPageOptions"
                :data="$this->users"
            />
        </div>
    </x-ui.card>

    {{-- Add/Edit Modal --}}
    <x-ui.modal id="manage-user" width="4xl" heading="Manage User Account">
        <form wire:submit="save" class="space-y-6 pt-4">

            {{-- Account Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.field required>
                    <x-ui.label>Full Name</x-ui.label>
                    <x-ui.input wire:model="name" placeholder="John Doe" />
                    <x-ui.error name="name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Username</x-ui.label>
                    <x-ui.input wire:model="username" placeholder="johndoe123" autocomplete="new-username" />
                    <x-ui.error name="username" />
                </x-ui.field>

                <x-ui.field :required="!$user_id">
                    <x-ui.label>{{ $user_id ? 'Reset Password (Leave blank to keep current)' : 'Password' }}</x-ui.label>
                    <x-ui.input revealable type="password" wire:model="password" placeholder="••••••••" autocomplete="new-password" />
                    <x-ui.error name="password" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>
                        Assigned Branch
                        @if($user_id && $branch_id)
                        <span class="text-red-500 font-semibold">(Cannot be changed once assigned)</span>
                        @else
                        <span class="text-neutral-500">(Leave empty for HQ/Admin)</span>
                        @endif
                    </x-ui.label>
                    <x-ui-select.styled invalidate wire:model="branch_id" :options="$this->branches" searchable :disabled="$user_id && $branch_id !== null" placeholder="Select Branch..." />
                    <x-ui.error name="branch_id" />
                </x-ui.field>

                <x-ui.field required class="md:col-span-2 border-b border-black/10 dark:border-white/10 pb-6">
                    <x-ui.label>Base Role</x-ui.label>
                    <x-ui-select.styled invalidate wire:model="role" :options="$this->roles" placeholder="Select Role..." />
                    <x-ui.error name="role" />
                </x-ui.field>
            </div>

            {{-- Permissions Fieldset (Your Alpine Snippet adapted for flat property) --}}
            <x-ui.fieldset label="Direct User Permissions" class="flex flex-col gap-4 mb-4">
                <div x-data="{
                        allPermissionValues: @js($this->all_permission_values),
                        groupedPermissions: @js($this->grouped_permissions),

                        get allSelected() {
                            return this.allPermissionValues.length > 0 &&
                                $wire.permissions.length === this.allPermissionValues.length;
                        },

                        toggleAll() {
                            if (this.allSelected) {
                                $wire.permissions = [];
                            } else {
                                $wire.permissions = this.allPermissionValues;
                            }
                        }
                    }">

                    {{-- Select All Action --}}
                    <div class="flex justify-between items-end mb-2 border-b border-neutral-200 dark:border-neutral-800 pb-2">
                        <p class="text-xs text-neutral-500">Fine-tune exactly what this user can access beyond their base role.</p>
                        <button type="button" @click="toggleAll()" x-bind:class="{'text-red-600': allSelected, '!text-primary-600': !allSelected}" class="text-sm font-semibold hover:underline focus:outline-none shrink-0">
                            <span x-text="allSelected ? 'Unselect All' : 'Select All'">Select All</span>
                        </button>
                    </div>

                    {{-- Permissions List --}}
                    <div class="flex flex-col gap-6 mt-4">
                        <template x-for="(permissions, groupName) in groupedPermissions" :key="groupName">
                            <div class="w-full">
                                <h4 class="text-xs font-bold text-neutral-500 dark:text-neutral-300 uppercase tracking-wider mb-3" x-text="groupName"></h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <template x-for="permission in permissions" :key="permission.value">
                                        <div class="flex items-center gap-2 bg-neutral-50 dark:bg-white/5 p-2 rounded border border-black/5 dark:border-white/5">
                                            {{-- Bound to flat $wire.permissions --}}
                                            <input type="checkbox" :id="'perm_' + permission.value" :value="permission.value" wire:model="permissions" class="rounded border-neutral-300 text-primary-600 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                                            <label :for="'perm_' + permission.value" class="text-xs text-neutral-700 dark:text-neutral-300 cursor-pointer select-none leading-tight" x-text="permission.label"></label>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </x-ui.fieldset>

            <div class="pt-4 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 border-t border-black/10 dark:border-white/10">
                <x-ui.button variant="outline" color="neutral" type="button" class="w-full sm:w-auto justify-center" wire:click="resetForm">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" class="w-full sm:w-auto justify-center" wire:loading.attr="disabled" wire:target="save" icon="check-circle">
                    {{ $user_id ? 'Update User' : 'Create User' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- Modal Manage roles --}}
    <livewire:admin.common.manage-role-modal />
</div>
