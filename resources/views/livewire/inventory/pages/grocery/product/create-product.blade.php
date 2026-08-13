<div class="max-w-7xl mx-auto space-y-6" x-data="{ packagings: @entangle('form.packagings') }">
    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('inventory.grocery.products') }}">
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
            <p class="text-neutral-500 dark:text-neutral-400">Add a new grocery item with stock, barcode, and packaging prices.</p>
        </div>
         <x-ui.button variant="outline" icon="arrow-left" color="neutral" href="{{ route('inventory.grocery.products') }}">
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
                            placeholder="e.g. Lucky Me Pancit Canton"
                        />
                        <x-ui.error name="form.brand_name" />
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
                    <x-ui.field required x-data="{
                        generateCode() {
                                // Generates a string like 'PRD-X7B9A2'
                                let randomStr = Math.random().toString(36).substring(2, 8).toUpperCase();
                                $wire.set('form.product_code', 'PRD-' + randomStr);
                            }
                        }">
                        <x-ui.label>Product Code</x-ui.label>
                        <div class="flex items-start gap-2">
                            <div class="flex-1">
                                <x-ui.input
                                    wire:model="form.product_code"
                                    placeholder="e.g. PRD-001"
                                />
                            </div>
                            <x-ui.button
                                type="button"
                                variant="outline"
                                icon="arrow-path"
                                x-on:click="generateCode()"
                                class="shrink-0"
                                size="sm"
                                title="Generate Random Code"
                            >
                                Generate
                            </x-ui.button>
                        </div>
                        <x-ui.error name="form.product_code" />
                    </x-ui.field>
                </div>

                 <x-ui.field required class="mt-5">
                    <x-ui.label>Product Category</x-ui.label>
                    <x-ui-select.styled
                        invalidate
                        wire:model="form.category_id"
                        :options="$this->categories"
                        searchable
                        placeholder="Select or create a category (e.g. Noodles)"
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
                    <x-ui.error name="form.category_id" />
                </x-ui.field>

                <x-ui.field class="mt-5">
                    <x-ui.label>Description (Optional)</x-ui.label>
                    <x-ui.textarea
                        label="Description"
                        wire:model="form.description"
                        placeholder="e.g. Chicken flavor, 80g pack"
                    />
                    <x-ui.error name="form.description" />
                </x-ui.field>

                <x-product.image-field
                    :preview="$this->productImagePreviewUrl()"
                    :hint="$this->productImageHint()"
                />

            </x-ui.card>

            <x-ui.card hoverless size="full">
                <x-ui.heading level="h3" size="md" class="mb-4">Inventory</x-ui.heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.field required>
                        <x-ui.label>Stock Type</x-ui.label>
                        <select wire:model="form.stock_type" class="w-full h-10 px-3 rounded-md border border-neutral-200 bg-white text-sm dark:border-white/10 dark:bg-card dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="regular">Regular Stock Product</option>
                            <option value="special_order">Special Order / Order Basis Product</option>
                        </select>
                        <x-ui.error name="form.stock_type" />
                    </x-ui.field>

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
                        <p class="text-xs text-neutral-500 mt-1">When does this initial batch expire?</p>
                        <x-ui.error name="form.expiration_date" />
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        {{-- RIGHT COLUMN: Units & Pricing --}}
        <div class="lg:col-span-1 space-y-6">

            {{-- Base Unit Configuration --}}
            <x-ui.card hoverless size="full" class="border-t-4 border-t-blue-500">
                <div class="mb-4 pb-4 border-b border-neutral-200 dark:border-white/10">
                    <x-ui.heading level="h3" size="md" class="text-blue-600 dark:text-blue-400">1. Base Selling Unit</x-ui.heading>
                    <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                        The smallest unit you sell (e.g., <strong>Piece</strong>, <strong>gram</strong>, or <strong>pack</strong>). Inventory is tracked using this unit.
                    </p>
                </div>

                <div class="space-y-5">
                    {{-- Base Unit Select --}}
                    <x-ui.field required>
                        <x-ui.label>Base Unit Type</x-ui.label>
                        <x-ui-select.styled
                            invalidate
                            wire:model="form.base_unit_id"
                            :options="$this->units"
                            searchable
                        placeholder="Select a unit (e.g. Piece)"
                        />
                        <x-ui.error name="form.base_unit_id" />
                    </x-ui.field>

                    {{-- Conversion --}}
                     <x-ui.field required>
                        <x-ui.label>Conversion Factor</x-ui.label>
                        <x-ui.input
                            placeholder="Usually 1"
                            type="number" step="any"
                            wire:model="form.conversion"
                        />
                        <p class="text-[10px] text-neutral-500 mt-1">Must be 1 for the base unit.</p>
                        <x-ui.error name="form.conversion" />
                    </x-ui.field>

                    {{-- Selling Price --}}
                    <x-ui.field required>
                        <x-ui.label>Selling Price</x-ui.label>
                        <x-ui.input
                            placeholder="e.g. 10.00"
                            type="number" step="any"
                            wire:model="form.selling_price"
                            left-icon="currency-dollar"
                        />
                        <p class="text-[10px] text-neutral-500 mt-1">Customer price per base unit.</p>
                        <x-ui.error name="form.selling_price" />
                    </x-ui.field>

                    {{-- Base Barcode --}}
                    <x-ui.field>
                        <x-ui.label>Barcode (Optional)</x-ui.label>
                        <x-ui.input
                            wire:model="form.base_barcode"
                            icon="qr-code"
                            placeholder="Scan or type barcode"
                        />
                        <x-ui.error name="form.base_barcode" />
                    </x-ui.field>
                </div>
            </x-ui.card>

            {{-- Additional Packaging --}}
            <x-ui.card hoverless size="full" class="border-t-4 border-t-purple-500">
                <div class="mb-4 pb-4 border-b border-neutral-200 dark:border-white/10 flex items-start justify-between">
                    <div>
                        <x-ui.heading level="h3" size="sm" class="text-purple-600 dark:text-purple-400">2. Larger Packs (Optional)</x-ui.heading>
                        <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-2 leading-relaxed">
                            Sell in bulks like <strong>Boxes</strong>? Add them here.
                        </p>
                    </div>
                    <x-ui.button type="button" size="xs" color="purple" variant="outline" x-on:click="packagings.push({ unit_id: '', conversion_factor: '', price: '', barcode: '' })" icon="plus">
                        Add Pack
                    </x-ui.button>
                </div>

                <div class="space-y-4">
                    {{-- Empty State --}}
                    <div x-show="packagings.length === 0" class="text-center py-6 px-4 bg-neutral-50 dark:bg-white/5 rounded-lg border border-dashed border-neutral-300 dark:border-white/10">
                        <x-ui.icon name="squares-plus" class="size-6 mx-auto text-neutral-400 mb-2" />
                        <p class="text-xs text-neutral-500">No larger packagings added.</p>
                        <p class="text-xs text-neutral-400 mt-1">Click "Add Pack" if you sell this product in boxes, bundles, cartons, or sacks.</p>
                    </div>

                    {{-- The Alpine Template Loop --}}
                    <template x-for="(pkg, index) in packagings" :key="index">
                        <div class="p-4 bg-purple-50/50 dark:bg-purple-900/10 rounded-lg relative group border border-purple-100 dark:border-purple-900/30">

                            {{-- Header of Pack --}}
                            <div class="flex items-center justify-between mb-3 border-b border-purple-200 dark:border-purple-800/50 pb-2">
                                <span class="text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider" x-text="`Packaging #${index + 1}`"></span>
                                <button type="button" x-on:click="packagings.splice(index, 1)" class="text-neutral-400 hover:text-red-500 transition-colors" title="Remove this packaging">
                                    <x-ui.icon name="trash" class="size-4" />
                                </button>
                            </div>

                            <div class="space-y-4">

                                <x-ui.field required>
                                    <x-ui.label>Unit Type</x-ui.label>
                                    <x-ui-select.styled
                                        invalidate
                                        x-model="pkg.unit_id"
                                        :options="$this->units"
                                        searchable
                                        placeholder="e.g. Box"
                                    >
                                    </x-ui-select.styled>
                                </x-ui.field>

                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Conversion --}}
                                    <x-ui.field required>
                                        <x-ui.label>Items inside</x-ui.label>
                                        <x-ui.input
                                            placeholder="e.g. 100"
                                            type="number" step="any"
                                             x-model="pkg.conversion_factor"
                                        />
                                    </x-ui.field>

                                    {{-- Selling Price --}}
                                    <x-ui.field required>
                                        <x-ui.label>Pack Price</x-ui.label>
                                        <x-ui.input
                                            placeholder="e.g. 950.00"
                                            type="number" step="any"
                                            x-model="pkg.price"
                                        />
                                    </x-ui.field>
                                </div>
                                <p class="text-[10px] text-neutral-500 -mt-2 mb-2 leading-tight">
                                    Example: If 1 Box has 24 pieces, set "Items inside" to 24.
                                </p>

                                {{-- Pack Barcode --}}
                                <x-ui.field>
                                    <x-ui.label>Pack Barcode (Optional)</x-ui.label>
                                    <x-ui.input
                                        x-model="pkg.barcode"
                                        icon="qr-code"
                                        placeholder="Scan pack barcode"
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

        {{-- Form Actions --}}
        <div class="col-span-1 lg:col-span-3 pt-6 border-t border-neutral-200 dark:border-white/10 flex items-center justify-end gap-3">
            <x-ui.button variant="danger" href="{{ route('inventory.grocery.products') }}">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" size="md" icon="check" color="primary">
                Save Product
            </x-ui.button>
        </div>
    </form>
</div>
