<div class="max-w-7xl mx-auto space-y-6">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Settings</h1>
        <p class="text-neutral-500 dark:text-neutral-400">Manage your account, configurations, and branch preferences.</p>
    </div>

    {{-- System Configurations (Master Data) --}}
    <x-ui.card hoverless size="full">
        <x-ui.heading level="h3" size="md" class="mb-1">System Configurations</x-ui.heading>
        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Manage the pharmacy's master data used in dropdowns and tables.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- Opens the Supplier Modal we made earlier --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-supplier' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="truck" class="size-6" />
                </div>
                <span class="font-bold text-neutral-900 dark:text-white">Suppliers</span>
            </button>

            {{-- Opens the Customer Type Modal we made earlier --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-customer-type' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="tag" class="size-6" />
                </div>
                <span class="font-bold text-neutral-900 dark:text-white">Customer Types</span>
            </button>

            {{-- Placeholder for a Units Modal --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-unit' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="scale" class="size-6" />
                </div>
                <span class="font-bold text-neutral-900 dark:text-white">Measurement Units</span>
            </button>

            {{-- Opens the Payment Methods Modal --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-payment-method' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="credit-card" class="size-6" />
                </div>
                <span class="font-bold text-neutral-900 dark:text-white">Payment Methods</span>
            </button>
        </div>
    </x-ui.card>

    {{-- Account Settings --}}
    <x-ui.card hoverless size="full">
        <x-ui.heading level="h3" size="md" class="mb-4">Account Profile</x-ui.heading>

        <form wire:submit.prevent="updateProfile" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.field required>
                    <x-ui.label>Full Name</x-ui.label>
                    <x-ui.input wire:model="name" />
                    <x-ui.error name="name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Username</x-ui.label>
                    <x-ui.input type="text" wire:model="username" />
                    <x-ui.error name="username" />
                </x-ui.field>
            </div>

            <div class="flex justify-end pt-2">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateProfile">
                    Save Profile
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Security / Password --}}
    <x-ui.card hoverless size="full">
        <x-ui.heading level="h3" size="md" class="mb-4">Change Password</x-ui.heading>

        <form wire:submit.prevent="updatePassword" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                 <x-ui.field required class=" col-span-1">
                     <x-ui.label>Current Password</x-ui.label>
                     <x-ui.input type="password" wire:model="current_password" />
                     <x-ui.error name="current_password" />
                 </x-ui.field>
                <x-ui.field required>
                    <x-ui.label>New Password</x-ui.label>
                    <x-ui.input type="password" wire:model="password" />
                    <x-ui.error name="password" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Confirm New Password</x-ui.label>
                    <x-ui.input type="password" wire:model="password_confirmation" />
                </x-ui.field>
            </div>

            <div class="flex justify-end pt-2">
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                    Update Password
                </x-ui.button>
            </div>
        </form>
    </x-ui.card>

    {{-- Branch Transfer Request --}}
    <x-ui.card hoverless size="full" class="border-orange-500/50">
        <div class="flex items-start gap-4">
            <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg text-orange-600 dark:text-orange-400 mt-1">
                <x-ui.icon name="building-storefront" class="size-7" />
            </div>
            <div class="flex-1">
                <x-ui.heading level="h3" size="md" class="mb-1 text-orange-600 dark:text-orange-400">Branch Transfer Request</x-ui.heading>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-4">
                    Current Branch: <strong class="text-neutral-900 dark:text-white">{{ auth()->user()->branch->name ?? 'None' }}</strong>
                </p>

                @if($this->pendingTransferRequest)
                    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 p-4 rounded-lg flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-orange-800 dark:text-orange-300">Transfer Request Pending</p>
                            <p class="text-xs text-orange-600 dark:text-orange-400">Requested to join: <strong>{{ $this->pendingTransferRequest->inquirable->name }}</strong></p>
                        </div>
                        <span class="inline-flex items-center rounded-md bg-orange-100 px-2 py-1 text-xs font-medium text-orange-700 ring-1 ring-inset ring-orange-600/20">
                            Awaiting Admin Verification
                        </span>
                    </div>
                @else
                    <form wire:submit.prevent="submitTransferRequest" class="space-y-4">
                        <x-ui.field required>
                            <x-ui.label>Target Branch</x-ui.label>
                            <x-ui-select.styled
                                invalidate
                                wire:model="target_branch_id"
                                :options="$this->availableBranches"
                                searchable
                                placeholder="Select new branch..."
                            />
                            <x-ui.error name="target_branch_id" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Reason / Remarks (Optional)</x-ui.label>
                            <x-ui.textarea wire:model="transfer_remarks" rows="2" placeholder="Why are you requesting a transfer?" />
                            <x-ui.error name="transfer_remarks" />
                        </x-ui.field>

                        <div class="flex justify-end pt-2">
                            <x-ui.button type="submit" color="orange" wire:loading.attr="disabled" wire:target="submitTransferRequest">
                                Submit Request
                            </x-ui.button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </x-ui.card>

    {{--
        Include your Configuration Modals exactly here at the bottom!
        Because they are independent Livewire components, they function perfectly
        even when nested in this settings view.
    --}}
    <livewire:inventory.pages.pharmacy.common.create-supplier-modal />
    <livewire:inventory.pages.pharmacy.common.create-customer-type-modal />
    <livewire:inventory.pages.pharmacy.common.create-unit-modal />
    <livewire:inventory.pages.pharmacy.common.create-payment-method-modal />
</div>
