<div class="max-w-7xl mx-auto space-y-6" x-data="{ packagings: @entangle('form.packagings') }">
    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('inventory.pharmacy.products') }}" wire:navigate>
            Products
        </x-ui.breadcrumbs.item>
        <x-ui.breadcrumbs.item active>
            Create New Product
        </x-ui.breadcrumbs.item>
    </x-ui.breadcrumbs>

    {{-- Header --}}
    <div class="flex items-center gap-5 justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Create New Product</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Add a new medicine or pharmacy item.</p>
        </div>
         <x-ui.button variant="outline" icon="arrow-left" color="neutral" href="{{ route('inventory.pharmacy.products') }}" wire:navigate>
             Back to list
         </x-ui.button>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT COLUMN: Basic Details --}}
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card hoverless size="full">
                <x-ui.heading level="h3" size="md" class="mb-4">Product Information</x-ui.heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Brand Name --}}
                    <x-ui.field required>
                        <x-ui.label>Brand Name</x-ui.label>
                        <x-ui.input
                            label="Brand Name"
                            wire:model="form.brand_name"
                            placeholder="e.g. Biogesic"
                        />
                        <x-ui.error name="form.brand_name" />
                    </x-ui.field>

                    {{-- Generic Name --}}
                    <x-ui.field required>
                        <x-ui.label>Generic Name</x-ui.label>
                        <x-ui.input
                            label="Generic Name"
                            wire:model="form.generic_name"
                            placeholder="e.g. Paracetamol"
                        />
                        <x-ui.error name="form.generic_name" />
                    </x-ui.field>

                    {{-- Supplier --}}
                    <x-ui.field required>
                        <x-ui.label>Supplier</x-ui.label>
                        <x-ui-select.styled
                            invalidate
                            wire:model="form.supplier_id"
                            :options="$this->suppliers"
                            searchable
                            placeholder="Select or create a supplier"
                        >
                            {{-- Slot for the "Create" button at the bottom of the dropdown --}}
                            <x-slot:after>
                                <div
                                    x-show="search?.length > 0"
                                    class="px-2 py-2 border-t border-gray-100 dark:border-white/10"
                                >
                                    <x-ui.button
                                        class="w-full justify-center"
                                        size="sm"
                                        variant="outline"
                                        {{-- 1. Hide the dropdown, 2. Call Livewire method with the search term --}}
                                        x-on:click="show = false; $wire.createSupplier(search)"
                                    >
                                        <span x-html="`Create new supplier: <b>${search}</b>`"></span>
                                    </x-ui.button>
                                </div>
                            </x-slot:after>
                        </x-ui-select.styled>
                        <x-ui.error name="form.supplier_id" />
                    </x-ui.field>

                    {{-- Product Code --}}
                    <x-ui.field required>
                        <x-ui.label>Product Code</x-ui.label>
                        <x-ui.input
                            label="Product Code"
                            wire:model="form.product_code"
                            placeholder="e.g. PRD-001"
                        />
                        <x-ui.error name="form.product_code" />
                    </x-ui.field>
                </div>

                 <x-ui.field required class="mt-5">
                    <x-ui.label>Product Category</x-ui.label>
                    <x-ui-select.styled
                        invalidate
                        wire:model="form.product_category_id"
                        :options="$this->categories"
                        searchable
                        placeholder="Select or create a Category (e.g. Pain Relief)"
                    >
                    {{-- Slot for the "Create" button at the bottom of the dropdown --}}
                    <x-slot:after>
                        <div
                            x-show="search?.length > 0"
                            class="px-2 py-2 border-t border-gray-100 dark:border-white/10"
                        >
                            <x-ui.button
                                class="w-full justify-center"
                                size="sm"
                                variant="outline"
                                {{-- 1. Hide the dropdown, 2. Call Livewire method with the search term --}}
                                x-on:click="show = false; $wire.createCategory(search)"
                            >
                                <span x-html="`Create new category: <b>${search}</b>`"></span>
                            </x-ui.button>
                        </div>
                    </x-slot:after>
                    </x-ui-select.styled>
                    <x-ui.error name="form.product_category_id" />
                </x-ui.field>

                <x-ui.field class="mt-5">
                    <x-ui.label>Description (Optional)</x-ui.label>
                    <x-ui.textarea
                        label="Description"
                        wire:model="form.description"
                        placeholder="e.g. Pain reliever"
                    />
                    <x-ui.error name="form.description" />
                </x-ui.field>

                <div class="mt-5 flex items-center gap-6">
                    <x-ui.checkbox wire:model="form.requires_prescription" label="Requires Prescription" />
                </div>
            </x-ui.card>

            <x-ui.card hoverless size="full">
                <x-ui.heading level="h3" size="md" class="mb-4">Inventory</x-ui.heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Branch --}}
                    <x-ui.field>
                        <x-ui.label>Branch (Default)</x-ui.label>
                        <x-ui.input
                            readonly
                            disabled
                            label="Branch"
                            placeholder="e.g. Main Branch"
                            :value="$this->user->branch->name"
                        />
                    </x-ui.field>

                     {{-- Batch number --}}
                     <x-ui.field>
                        <x-ui.label>Batch Number (Optional)</x-ui.label>
                        <x-ui.input
                            label="Batch Number"
                            wire:model="form.batch_number"
                            placeholder="e.g. B12345"
                        />
                        <x-ui.error name="form.batch_number" />
                    </x-ui.field>

                    {{-- Reorder Level --}}
                    <x-ui.field required>
                        <x-ui.label>Low Stock Alert Level</x-ui.label>
                        <x-ui.input
                            label="Low Stock Alert Level"
                            type="number" step="any"
                            placeholder="e.g 50.00"
                            wire:model="form.reorder_level"
                        />
                        <x-ui.error name="form.reorder_level" />
                    </x-ui.field>

                    {{-- Stock/Quantity on hand --}}
                    <x-ui.field required>
                        <x-ui.label>Stock/Quantity on hand</x-ui.label>
                        <x-ui.input
                            label="Low Stock Alert Level"
                            type="number" step="any"
                            placeholder="e.g 50.00"
                            wire:model="form.quantity_on_hand"
                        />
                        <x-ui.error name="form.quantity_on_hand" />
                    </x-ui.field>

                    {{-- Cost of product --}}
                     <x-ui.field required>
                        <x-ui.label>Cost Price of base unit</x-ui.label>
                        <x-ui.input
                            label="Cost Price (Per Piece)"
                            type="number" step="any"
                            wire:model="form.cost_price"
                            placeholder="e.g 50.00"
                        />
                        <x-ui.error name="form.cost_price" />
                    </x-ui.field>

                    {{-- Expiration Date --}}
                    <x-ui.field required>
                        <x-ui.label>Expiration Date</x-ui.label>
                        <x-ui-date
                            invalidate
                            wire:model="form.expiration_date"
                            :min-date="now()" format="MMMM DD, YYYY"
                        />
                        <x-ui.error name="form.expiration_date" />
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        {{-- RIGHT COLUMN: Units & Pricing --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- Base Unit Configuration --}}
            <x-ui.card hoverless size="full">
                <x-ui.heading level="h3" size="md" class=" text-blue-600">Base Unit (Smallest)</x-ui.heading>
                <p class="text-xs text-gray-500 mb-4">This is how you track stock (e.g., Piece/Tablet).</p>

                <div class="space-y-4">
                    {{-- Base Unit Select --}}
                    <x-ui.field required>
                        <x-ui.label>Base Unit Type</x-ui.label>
                        <x-ui-select.styled
                            invalidate
                            wire:model="form.base_unit_id"
                            :options="$this->units"
                            searchable
                            placeholder="Select or create a Unit (e.g. Piece)"
                        />
                        <x-ui.error name="form.base_unit_id" />
                    </x-ui.field>

                    {{-- Conversion --}}
                     <x-ui.field required>
                        <x-ui.label>Conversion</x-ui.label>
                        <x-ui.input
                            label="Conversion"
                            placeholder="e.g. 10 (if 1 Box = 10 Pieces)"
                            type="number" step="any"
                            wire:model="form.conversion"
                        />
                        <x-ui.error name="form.conversion" />
                    </x-ui.field>

                    {{-- Selling Price --}}
                    <x-ui.field required>
                        <x-ui.label>Selling Price</x-ui.label>
                        <x-ui.input
                            label="Selling Price (Per Piece)"
                            placeholder="e.g. 50.00"
                            type="number" step="any"
                            wire:model="form.selling_price"
                        />
                        <x-ui.error name="form.selling_price" />
                    </x-ui.field>

                    {{-- Base Barcode --}}
                    <x-ui.field>
                        <x-ui.label>Base Barcode (Optional)</x-ui.label>
                        <x-ui.input
                            label="Barcode (Optional)"
                            wire:model="form.base_barcode"
                            icon="qr-code"
                        />
                        <x-ui.error name="form.base_barcode" />
                    </x-ui.field>
                </div>
            </x-ui.card>

            {{-- Additional Packaging (Real-time with Alpine.js & Sheaf Components) --}}
            <x-ui.card hoverless size="full">
                <div class="flex items-center justify-between mb-4">
                    <x-ui.heading level="h3" size="sm">Larger Packs</x-ui.heading>
                    <x-ui.button type="button" size="xs" x-on:click="packagings.push({ unit_id: '', conversion_factor: '', price: '', barcode: '' })" icon="plus">
                        Add
                    </x-ui.button>
                </div>

                <div class="space-y-6">
                    {{-- The Alpine Template Loop --}}
                    <template x-for="(pkg, index) in packagings" :key="index">
                        <div class="p-4 bg-gray-50 dark:bg-white/5 rounded-lg relative group border border-transparent dark:border-white/5">

                            {{-- Remove Button --}}
                            <button type="button" x-on:click="packagings.splice(index, 1)" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition-colors">
                                <x-ui.icon name="x-mark" class="size-5" />
                            </button>

                            <div class="grid grid-cols-1 gap-4 mt-4">

                                <x-ui.field required>
                                    <x-ui.label>Base Unit Type</x-ui.label>
                                    <x-ui-select.styled
                                        invalidate
                                        x-model="pkg.unit_id"
                                        :options="$this->units"
                                        searchable
                                        placeholder="Select or create a Unit (e.g. Piece)"
                                    >
                                    </x-ui-select.styled>
                                </x-ui.field>

                                <div class="flex gap-3">
                                    {{-- Conversion --}}
                                    <x-ui.field class="w-1/2!" required>
                                        <x-ui.label>Conversion</x-ui.label>
                                        <x-ui.input
                                            label="Conversion"
                                            placeholder="e.g. 10 (if 1 Box = 10 Pieces)"
                                            type="number" step="any"
                                             x-model="pkg.conversion_factor"
                                        />
                                    </x-ui.field>

                                    {{-- Selling Price --}}
                                    <x-ui.field class="w-1/2!" required>
                                        <x-ui.label>Selling Price</x-ui.label>
                                        <x-ui.input
                                            label="Selling Price (Per Piece)"
                                            placeholder="e.g. 50.00"
                                            type="number" step="any"
                                            x-model="pkg.price"
                                        />
                                    </x-ui.field>
                                </div>

                                {{-- Base Barcode --}}
                                <x-ui.field>
                                    <x-ui.label>Base Barcode (Optional)</x-ui.label>
                                    <x-ui.input
                                        label="Barcode (Optional)"
                                        x-model="pkg.barcode"
                                        icon="qr-code"
                                    />
                                </x-ui.field>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- General Error Message for Packagings --}}
                <x-ui.error
                    name="form.packagings.*"
                    class="mt-4 p-3 bg-red-50 dark:bg-red-500/10 rounded-md border border-red-200 dark:border-red-500/20"
                />
            </x-ui.card>
        </div>

        <div class="col-span-1 lg:col-span-3 gap-3 flex justify-end">
            <x-ui.button type="submit" size="md" icon="check">
                Create Product
            </x-ui.button>
            <x-ui.button variant="danger" href="{{ route('inventory.pharmacy.products') }}" wire:navigate>
                Cancel
            </x-ui.button>
        </div>
    </form>
</div>
