<div class="max-w-7xl mx-auto space-y-6"
    x-data="{
        packagings: @entangle('form.packagings'),
        baseUnitId: @entangle('form.base_unit_id'),
        allUnits: @js($this->units), // Load the master list of units into Alpine

        // Computed property: returns ONLY units that exist in the packagings array
        get availableBaseUnits() {
            let usedUnitIds = this.packagings
                .map(p => p.unit_id)
                .filter(id => id !== '' && id !== null);

            return this.allUnits.filter(u => usedUnitIds.includes(String(u.value)) || usedUnitIds.includes(Number(u.value)));
        },

        init() {
            // Watch the packagings array deeply
            this.$watch('packagings', (newPacks) => {
                if (this.baseUnitId) {
                    // Check if the currently selected base unit still exists in the packagings list
                    let exists = newPacks.some(pkg => pkg.unit_id == this.baseUnitId);

                    if (!exists) {
                        // If they deleted or changed the packaging, clear the base unit selection!
                        this.baseUnitId = null;
                    }
                }

                // Force conversion factor to 1 for whichever unit is selected as the base
                newPacks.forEach(pkg => {
                    if (this.baseUnitId && pkg.unit_id == this.baseUnitId) {
                        pkg.conversion_factor = 1;
                    }
                });
            }, { deep: true });

            // If base unit changes, immediately lock its conversion factor
            this.$watch('baseUnitId', (newId) => {
                this.packagings.forEach(pkg => {
                    if (pkg.unit_id == newId) {
                        pkg.conversion_factor = 1;
                    }
                });
            });
        }
    }">

    <x-ui.breadcrumbs>
        <x-ui.breadcrumbs.item href="{{ route('inventory.grocery.products') }}">
            Products
        </x-ui.breadcrumbs.item>
        <x-ui.breadcrumbs.item active>
            Edit Product: {{ $product->brand_name }}
        </x-ui.breadcrumbs.item>
    </x-ui.breadcrumbs>

    {{-- Header --}}
    <div class="flex items-center gap-5 justify-between">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Edit Product</h1>
            <p class="text-neutral-500 dark:text-neutral-400">Update master data and packaging prices.</p>
        </div>
         <x-ui.button variant="outline" icon="arrow-left" color="neutral" href="{{ route('inventory.grocery.products') }}">
             Back to list
         </x-ui.button>
    </div>

    <form wire:submit.prevent="save" class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- LEFT COLUMN: Basic Details --}}
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card hoverless size="full">
                <x-ui.heading level="h3" size="md" class="mb-4">Product Information</x-ui.heading>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.field required>
                        <x-ui.label>Brand Name</x-ui.label>
                        <x-ui.input wire:model="form.brand_name" placeholder="e.g. Lucky Me Pancit Canton" />
                        <x-ui.error name="form.brand_name" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Supplier</x-ui.label>
                        <x-ui-select.styled
                            invalidate
                            wire:model="form.supplier_id"
                            :options="$this->suppliers"
                            searchable
                            placeholder="Select or create a supplier"
                        >
                            <x-slot:after>
                                <div x-show="search?.length > 0" class="px-2 py-2 border-t border-gray-100 dark:border-white/10">
                                    <x-ui.button class="w-full justify-center" size="sm" variant="outline" x-on:click="show = false; $wire.createSupplier(search)">
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
                    <x-ui.textarea wire:model="form.description" placeholder="e.g. Chicken flavor, 80g pack" rows="3" />
                    <x-ui.error name="form.description" />
                </x-ui.field>

                <x-product.image-field
                    :preview="$this->productImagePreviewUrl($product)"
                    :hint="$this->productImageHint()"
                />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-5 pt-5 border-t border-black/5 dark:border-white/5">

                    {{-- Base Unit derived from Packagings --}}
                    <x-ui.field required>
                        <x-ui.label>Inventory Tracking Unit (Base Unit)</x-ui.label>

                        {{-- Using a native styled select for guaranteed Alpine reactivity on the dynamic array --}}
                        <select x-model="baseUnitId" class="w-full h-10 px-3 rounded-md border border-neutral-200 bg-white text-sm dark:border-white/10 dark:bg-card dark:text-white focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select unit from configured packagings...</option>
                            <template x-for="unit in availableBaseUnits" :key="unit.value">
                                <option :value="unit.value" x-text="unit.label"></option>
                            </template>
                        </select>

                        {{-- Helpful hint if array is empty --}}
                        <p x-show="availableBaseUnits.length === 0" x-cloak class="text-xs text-orange-500 mt-1">
                            Add a packaging on the right to select your base unit.
                        </p>
                        <x-ui.error name="form.base_unit_id" />
                    </x-ui.field>

                        <x-ui.field required>
                            <x-ui.label>Stock Type</x-ui.label>
                            <select wire:model="form.stock_type" class="w-full h-10 px-3 rounded-md border border-neutral-200 bg-white text-sm dark:border-white/10 dark:bg-card dark:text-white focus:border-blue-500 focus:ring-blue-500">
                                <option value="regular">Regular Stock Product</option>
                                <option value="special_order">Special Order / Order Basis Product</option>
                            </select>
                            <x-ui.error name="form.stock_type" />
                        </x-ui.field>

                        <x-ui.field required>
                            <x-ui.label>Low Stock Alert Level</x-ui.label>
                            <x-ui.input type="number" step="any" wire:model="form.reorder_level" placeholder="e.g 50.00" />
                            <x-ui.error name="form.reorder_level" />
                    </x-ui.field>
                </div>
            </x-ui.card>
        </div>

        {{-- RIGHT COLUMN: Unified Packagings --}}
        <div class="lg:col-span-1 space-y-6">

            <x-ui.card hoverless size="full">
                <div class="flex flex-col mb-4">
                    <div class="flex items-center justify-between mb-1">
                        <x-ui.heading level="h3" size="sm">Packagings & Pricing</x-ui.heading>
                        <x-ui.button type="button" size="xs" x-on:click="packagings.push({ unit_id: '', conversion_factor: '', price: '', barcode: '' })" icon="plus">
                            Add Pack
                        </x-ui.button>
                    </div>
                    <p class="text-xs text-neutral-500">Configure prices for your Base Unit and any larger packs (Boxes, Cartons).</p>
                </div>

                <div class="space-y-4">

                    <template x-for="(pkg, index) in packagings" :key="index">
                        <div class="p-4 bg-gray-50 dark:bg-white/5 rounded-lg relative group border"
                             :class="pkg.unit_id == baseUnitId && baseUnitId !== null ? 'border-blue-300 dark:border-blue-800' : 'border-transparent dark:border-white/5'">

                            {{-- Badge if it's the Base Unit --}}
                            <div x-show="pkg.unit_id == baseUnitId && baseUnitId !== null" class="absolute -top-2.5 left-3 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 text-[10px] font-bold px-2 py-0.5 rounded border border-blue-200 dark:border-blue-800">
                                Base Unit
                            </div>

                            <button type="button" x-on:click="packagings.splice(index, 1)" class="absolute top-3 right-3 text-gray-400 hover:text-red-500 transition-colors">
                                <x-ui.icon name="x-mark" class="size-5" />
                            </button>

                            <div class="grid grid-cols-1 gap-4 mt-2">
                                <x-ui.field required>
                                    <x-ui.label>Unit Type</x-ui.label>
                                    <x-ui-select.styled invalidate x-model="pkg.unit_id" :options="$this->units" searchable placeholder="Select Unit" />
                                </x-ui.field>

                                <div class="flex gap-3">
                                    <x-ui.field class="w-1/2!" required>
                                        <x-ui.label>Conversion</x-ui.label>
                                        {{-- Alpine Magic: If this is the base unit, force to 1 and disable! --}}
                                        <x-ui.input
                                            type="number" step="any"
                                            x-model="pkg.conversion_factor"
                                            placeholder="e.g. 10"
                                            x-bind:readonly="pkg.unit_id == baseUnitId && baseUnitId !== null"
                                        />
                                    </x-ui.field>

                                    <x-ui.field class="w-1/2!" required>
                                        <x-ui.label>Selling Price</x-ui.label>
                                        <x-ui.input type="number" step="any" x-model="pkg.price" placeholder="e.g. 50.00" />
                                    </x-ui.field>
                                </div>

                                <x-ui.field>
                                    <x-ui.label>Barcode (Optional)</x-ui.label>
                                    <x-ui.input x-model="pkg.barcode" icon="qr-code" />
                                </x-ui.field>
                            </div>
                        </div>
                    </template>

                    {{-- Alert if array is empty --}}
                    <div x-show="packagings.length === 0" x-cloak class="text-center p-6 border border-dashed rounded-lg border-neutral-300 dark:border-white/20 text-neutral-500 text-sm">
                        You must add at least one packaging to configure pricing and set your Base Unit.
                    </div>
                </div>

                {{-- General Validation Error --}}
                <x-ui.error name="form.base_unit_id" class="mt-4" />
                <x-ui.error name="form.packagings.*" class="mt-4 p-3 bg-red-50 dark:bg-red-500/10 rounded-md border border-red-200 dark:border-red-500/20" />
            </x-ui.card>
        </div>

        <div class="col-span-1 lg:col-span-3 gap-3 flex justify-end">
            <x-ui.button variant="danger" href="{{ route('inventory.grocery.products') }}">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" size="md" icon="check" wire:loading.attr="disabled" wire:target="save">
                Save Changes
            </x-ui.button>
        </div>
    </form>
</div>
