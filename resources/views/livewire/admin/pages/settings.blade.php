<div class="max-w-7xl mx-auto space-y-6 p-5">

    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">System Settings</h1>
        <p class="text-neutral-500 dark:text-neutral-400">Manage your account profile and global system configurations.</p>
    </div>

    {{-- System Configurations (Master Data) --}}
    <x-ui.card hoverless size="full">
        <x-ui.heading level="h3" size="md" class="mb-1">Global Configurations</x-ui.heading>
        <p class="text-sm text-neutral-500 dark:text-neutral-400 mb-5">Manage the master data used across all branches and modules.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">

            {{-- Suppliers --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-supplier' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="truck" class="size-6" />
                </div>
                <span class="font-bold text-sm text-neutral-900 dark:text-white">Suppliers</span>
            </button>

            {{-- Product Categories --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'manage-product-category' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="rectangle-stack" class="size-6" />
                </div>
                <span class="font-bold text-sm text-neutral-900 dark:text-white">Categories</span>
            </button>

            {{-- Measurement Units --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-unit' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="scale" class="size-6" />
                </div>
                <span class="font-bold text-sm text-neutral-900 dark:text-white">Units</span>
            </button>

            {{-- Customer Types --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-customer-type' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="tag" class="size-6" />
                </div>
                <span class="font-bold text-sm text-neutral-900 dark:text-white">Customer Types</span>
            </button>

            {{-- Payment Methods --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'create-payment-method' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="credit-card" class="size-6" />
                </div>
                <span class="font-bold text-sm text-neutral-900 dark:text-white">Payment Methods</span>
            </button>

            {{-- Desktop App Uploader --}}
            <button type="button" x-on:click="$dispatch('open-modal', { id: 'upload-app' })" class="flex flex-col items-center justify-center gap-2 p-5 rounded-lg border border-neutral-200 dark:border-white/10 hover:bg-neutral-50 dark:hover:bg-white/5 transition-colors group">
                <div class="p-3 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-full group-hover:scale-110 transition-transform">
                    <x-ui.icon name="cloud-arrow-up" class="size-6" />
                </div>
                <span class="font-bold text-sm text-neutral-900 dark:text-white">Desktop App</span>
            </button>

        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Account Settings --}}
        <x-ui.card hoverless size="full" class="h-full">
            <x-ui.heading level="h3" size="md" class="mb-4">Account Profile</x-ui.heading>

            <form wire:submit.prevent="updateProfile" class="space-y-4">
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

                <div class="flex justify-end pt-2 mt-auto">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updateProfile">
                        Save Profile
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>

        {{-- Security / Password --}}
        <x-ui.card hoverless size="full" class="h-full">
            <x-ui.heading level="h3" size="md" class="mb-4">Change Password</x-ui.heading>

            <form wire:submit.prevent="updatePassword" class="space-y-4">
                <x-ui.field required>
                    <x-ui.label>Current Password</x-ui.label>
                    <x-ui.input type="password" wire:model="current_password" />
                    <x-ui.error name="current_password" />
                </x-ui.field>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                <div class="flex justify-end pt-2 mt-auto">
                    <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="updatePassword">
                        Update Password
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>

    {{-- System Configuration Modals --}}
    <livewire:inventory.pages.pharmacy.common.create-supplier-modal />
    <livewire:inventory.pages.pharmacy.common.create-customer-type-modal />
    <livewire:inventory.pages.pharmacy.common.create-unit-modal />
    <livewire:inventory.pages.pharmacy.common.create-payment-method-modal />
    {{-- <livewire:admin.common.manage-product-category-modal /> --}}
    <livewire:admin.common.upload-app-modal />
</div>
