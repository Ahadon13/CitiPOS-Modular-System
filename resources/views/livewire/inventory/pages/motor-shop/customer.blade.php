<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Customers</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Manage customer records and percentage discount privileges.</p>
        </div>
        <div class="flex items-center gap-3 justify-end">
            <x-ui.button variant="outline" icon="arrow-path" wire:click="$refresh">
                Refresh
            </x-ui.button>
            <x-ui.button variant="outline" icon="tag" x-on:click="$dispatch('open-modal', { id: 'create-customer-type' })">
                Customer Types
            </x-ui.button>
            <x-ui.button icon="plus" color="blue" x-on:click="$dispatch('open-modal', { id: 'customer-form' }); $this.resetForm()">
                Add Customer
            </x-ui.button>
        </div>
    </div>

    {{-- 4 Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-ui.card hoverless size="full" class="border-l-4 border-l-blue-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <x-ui.icon name="users" class="size-6 text-blue-600! dark:text-blue-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-blue-500 uppercase tracking-wide">Total Customers</p>
                    <h3 class="text-2xl font-bold text-blue-900 dark:text-blue-400">
                        {{ number_format($this->stats['total_customers']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-emerald-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                    <x-ui.icon name="ticket" class="size-6 text-emerald-600! dark:text-emerald-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-emerald-600/80 uppercase tracking-wide">Discounted Profiles</p>
                    <h3 class="text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                        {{ number_format($this->stats['discounted_customers']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-purple-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <x-ui.icon name="tag" class="size-6 text-purple-600! dark:text-purple-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-purple-600/80 uppercase tracking-wide">Customer Types</p>
                    <h3 class="text-2xl font-bold text-purple-700 dark:text-purple-400">
                        {{ number_format($this->stats['total_types']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card hoverless size="full" class="border-l-4 border-l-indigo-500!">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg">
                    <x-ui.icon name="shopping-bag" class="size-6 text-indigo-600! dark:text-indigo-400!" />
                </div>
                <div>
                    <p class="text-xs font-medium text-indigo-600/80 uppercase tracking-wide">With Recorded Sales</p>
                    <h3 class="text-2xl font-bold text-indigo-700 dark:text-indigo-400">
                        {{ number_format($this->stats['customers_with_sales']) }}
                    </h3>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Data Table Section --}}
    <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
        <div class="px-6 py-5 border-b border-black/10 dark:border-white/10 flex flex-col lg:flex-row md:items-center justify-between gap-4">

            <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto flex-1">
                {{-- Search --}}
                <div class="w-full sm:w-72">
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        leftIcon="magnifying-glass"
                        placeholder="Search batch or product..."
                        class="w-full"
                        clearable
                    />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 w-full md:w-auto">
                {{-- Type Dropdown Filter --}}
                <div class="w-full sm:w-64">
                    <x-ui-select.styled
                        invalidate
                        wire:model.live="typeFilter"
                        placeholder="Filter by Customer Type..."
                        :options="$this->availableCustomerTypes"
                        searchable
                        select="label:label|value:value"
                    />
                </div>

                <x-ui.button size="sm" variant="outline" icon="arrow-down-tray" wire:click="exportCustomers" wire:loading.attr="disabled" wire:target="exportCustomers">
                    Export in Excel
                </x-ui.button>
            </div>
        </div>

        <div class="w-full">
            <div class="w-full text-sm text-neutral-300">
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-black/10 dark:border-white/10 dark:bg-[#0a1331] bg-neutral-100/10 text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">
                                <th class="px-6 py-4">Customer Details</th>
                                <th class="px-6 py-4">Type & Discount</th>
                                <th class="px-6 py-4">Identification</th>
                                <th class="px-6 py-4 text-center">Transactions</th>
                                <th class="px-6 py-4 text-center">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-black/10 dark:divide-white/10 bg-neutral-50 dark:bg-[#060A23]">
                            @forelse ($this->customers as $customer)
                                <tr class="hover:bg-white/5 transition-colors group">
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-neutral-900 dark:text-white block">{{ $customer->name }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            @if($customer->contact_number)
                                                <span class="text-xs text-neutral-500 flex items-center gap-1">
                                                    <x-ui.icon name="phone" class="size-3" /> {{ $customer->contact_number }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        @if((float) $customer->customerType?->discount_percentage > 0)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-400">
                                                {{ $customer->customerType->name }} ({{ number_format($customer->customerType->discount_percentage, 0) }}%)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-neutral-100 dark:bg-white/5 px-2 py-1 text-xs font-medium text-neutral-700 dark:text-neutral-300 ring-1 ring-inset ring-neutral-500/20">
                                                {{ $customer->customerType->name ?? 'Standard' }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex flex-col gap-1">
                                            @if($customer->id_card_number)
                                                <span class="text-xs text-neutral-600 dark:text-neutral-400">
                                                    <strong class="text-neutral-500">ID:</strong> {{ $customer->id_card_number }}
                                                </span>
                                            @endif
                                            @if($customer->booklet_number)
                                                <span class="text-xs text-neutral-600 dark:text-neutral-400">
                                                    <strong class="text-neutral-500">Booklet:</strong> {{ $customer->booklet_number }}
                                                </span>
                                            @endif
                                            @if(!$customer->id_card_number && !$customer->booklet_number)
                                                <span class="text-xs text-neutral-400 italic">None provided</span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center font-medium text-neutral-700 dark:text-neutral-300">
                                        {{ $customer->sales_count }}
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2 ">
                                            <x-ui.button
                                                size="xs"
                                                variant="outline"
                                                icon="pencil-square"
                                                color="blue"
                                                wire:click="editCustomer({{ $customer->id }}); $dispatch('open-modal', { id: 'customer-form' })"
                                                title="Edit Profile"
                                            />

                                            <x-ui.button
                                                size="xs"
                                                variant="outline"
                                                icon="trash"
                                                color="red"
                                                wire:click="deleteCustomer({{ $customer->id }})"
                                                wire:custom-confirm="Permanently delete customer {{ $customer->name }}?"
                                                title="Delete Profile"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-24 text-center">
                                        <x-ui.empty>
                                            <x-ui.empty.media class="flex items-center justify-center w-12 h-12 rounded-full bg-neutral-100 dark:bg-card">
                                                <x-ui.icon name="document-text" class="size-6" />
                                            </x-ui.empty.media>

                                            <x-ui.empty.contents>
                                                <x-ui.heading>No customer found</x-ui.heading>
                                                <x-ui.text class="opacity-70">
                                                    We couldn't find any customers matching your criteria. Try adjusting your search or add new customers to see them here.
                                                </x-ui.text>

                                                <x-ui.button icon="plus" size="sm" class="mt-3" x-on:click="$dispatch('open-modal', { id: 'customer-form' }); $this.resetForm()">
                                                    Add Customer
                                                </x-ui.button>
                                            </x-ui.empty.contents>
                                        </x-ui.empty>
                                        </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-black/10 dark:border-white/10 px-4 pb-3 flex justify-center">
                    <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->customers" />
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Add/Edit Customer Modal --}}
    <x-ui.modal id="customer-form" width="lg" heading="{{ $editingCustomerId ? 'Edit Customer' : 'Add New Customer' }}">
        <form wire:submit.prevent="saveCustomer" class="space-y-4">

            <x-ui.field required>
                <x-ui.label>Full Name</x-ui.label>
                <x-ui.input wire:model="name" placeholder="e.g. Juan Dela Cruz" />
                <x-ui.error name="name" />
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Customer Type</x-ui.label>
                <x-ui-select.styled
                    invalidate
                    wire:model="customer_type_id"
                    placeholder="Select Customer Type"
                    :options="$this->availableCustomerTypes"
                    searchable
                    select="label:label|value:value"
                />
                <x-ui.error name="customer_type_id" />
            </x-ui.field>

            <div class="grid grid-cols-2 gap-4">
                <x-ui.field>
                    <x-ui.label>ID Card Number</x-ui.label>
                    <x-ui.input wire:model="id_card_number" placeholder="Optional" />
                    <x-ui.error name="id_card_number" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Booklet Number</x-ui.label>
                    <x-ui.input wire:model="booklet_number" placeholder="Optional" />
                    <x-ui.error name="booklet_number" />
                </x-ui.field>
            </div>

            <x-ui.field>
                <x-ui.label>Contact Number</x-ui.label>
                <x-ui.input wire:model="contact_number" placeholder="e.g. 09123456789" />
                <x-ui.error name="contact_number" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Address</x-ui.label>
                <x-ui.textarea wire:model="address" placeholder="Optional" rows="2" />
                <x-ui.error name="address" />
            </x-ui.field>

            <div class="pt-4 flex justify-end gap-3 mt-4 border-t border-black/10 dark:border-white/10">
                <x-ui.button type="button" variant="outline" x-on:click="$dispatch('close-modal', { id: 'customer-form' })">Cancel</x-ui.button>
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveCustomer" icon="check">
                    {{ $editingCustomerId ? 'Save Changes' : 'Create Customer' }}
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    {{-- Manage Customer Types Modal --}}
    <livewire:inventory.pages.motor-shop.common.create-customer-type-modal />
</div>
