<div class="flex flex-col md:flex-row w-full h-full" x-data="posApp(@js($this->activePaymentMethods))" @keydown.window="handleKeydown($event)">

    {{-- ========================================== --}}
    {{-- LEFT COLUMN: PRODUCT SELECTION (LIST ONLY) --}}
    {{-- ========================================== --}}
    <div class="flex-1 flex flex-col bg-white dark:bg-[#0a1331]/80 border border-black/10 dark:border-white/10 overflow-hidden shadow-sm min-w-0">

        {{-- Top Search & Action Bar --}}
        <div class="p-3 h-16 border-b border-black/10 dark:border-white/10 flex flex-row items-center gap-3">
            <x-ui.button color="primary" variant="outline" icon="qr-code" class="shrink-0 hidden sm:inline-flex">
                Barcode Scan
                <span class="ml-2 text-[10px] uppercase font-mono opacity-70 border border-electric-blue/30 rounded px-1.5 py-0.5">F1</span>
            </x-ui.button>
            <div class="flex-1 min-w-[200px]">
                <x-ui.input
                    clearable
                    leftIcon="magnifying-glass"
                    placeholder="Search products... (Ctrl+K)"
                    wire:model.live.debounce.300ms="search"
                    class="w-full bg-neutral-50 dark:bg-[#060A23]"
                />
            </div>
        </div>

        {{-- Categories Filter Bar --}}
        <div class="px-4 py-2.5 border-b border-black/10 dark:border-white/10 flex flex-wrap items-center gap-2 bg-neutral-50/50 dark:bg-white/5 shrink-0">
            <x-ui.icon name="funnel" class="size-4 text-neutral-400 shrink-0 mr-1" />

            {{-- 'All Products' Button --}}
            <button wire:click="setCategory(null)" class="whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm flex items-center gap-1.5 transition-transform active:scale-95 {{ $activeCategory === null ? 'bg-electric-blue text-white' : 'bg-neutral-100 dark:bg-[#060A23] border border-black/5 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-white/10' }}">
                All Products
            </button>

            {{-- Dynamic Category Loop --}}
            @foreach($this->categories as $category)
            <button wire:click="setCategory({{ $category->id }})" class="whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-1.5 transition-colors active:scale-95 shadow-sm {{ $activeCategory === $category->id ? 'bg-electric-blue text-white shadow-sm font-semibold' : 'bg-neutral-100 dark:bg-[#060A23] border border-black/5 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-white/10' }}">
                {{ $category->name }}
            </button>
            @endforeach
        </div>

        {{-- Product List Area --}}
        <div class="flex-1 overflow-y-auto p-4 bg-neutral-50/50 dark:bg-[#060A23] custom-scrollbar">
            @if($this->products->isEmpty())
            <div class="flex flex-col items-center justify-center h-full text-neutral-500">
                <x-ui.icon name="magnifying-glass" class="size-12 mb-3 opacity-20" />
                <p>No products found matching "{{ $search }}"</p>
            </div>
            @endif

            <div class="flex flex-col gap-2">
                @foreach($this->products as $product)
                <div x-data="{ selectedPkgId: {{ $product->packagings[0]['id'] ?? 'null' }} }" @click="increase({{ $product->id }}, '{{ addslashes($product->name) }}', '{{ addslashes($product->generic_name) }}', {{ $product->stock }}, {{ json_encode($product->packagings) }}, selectedPkgId)" class="cursor-pointer flex items-center justify-between p-3 rounded-xl border border-black/10 dark:border-white/10 bg-white dark:bg-[#0a1331] hover:border-electric-blue dark:hover:border-electric-blue transition-all hover:shadow-sm active:scale-[0.99] {{ $product->stock <= 0 ? 'opacity-60 grayscale pointer-events-none' : '' }}">
                    {{-- Left: Details --}}
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="size-10 rounded-lg bg-neutral-100 dark:bg-white/5 flex items-center justify-center shrink-0">
                            <x-ui.icon name="cube" class="size-5 text-neutral-400" />
                        </div>
                        <div class="truncate">
                            <div class="flex items-center flex-row gap-1 leading-tight min-w-0">
                                <h3 class="text-sm font-bold text-neutral-900 dark:text-white truncate group-hover:text-electric-blue transition-colors">{{ $product->name }}</h3>
                                - <p class="text-xs text-neutral-500 dark:text-neutral-400 truncate">{{ $product->generic_name }}</p>
                            </div>

                            <div class="flex items-center gap-2 mt-1">
                                {{-- PACKAGING SELECTOR --}}
                                @if(count($product->packagings) > 1)
                                <select x-model="selectedPkgId" @click.stop class="text-[10px] py-0.5 px-1.5 rounded border border-black/10 dark:border-white/10 bg-neutral-50 dark:bg-[#060A23] font-medium text-neutral-600 dark:text-neutral-300 focus:ring-0 focus:border-electric-blue">
                                    @foreach($product->packagings as $pkg)
                                    <option value="{{ $pkg['id'] }}">{{ $pkg['unit'] }} (₱{{ number_format($pkg['price'], 2) }})</option>
                                    @endforeach
                                </select>
                                @else
                                <span class="text-[10px] font-bold text-neutral-500 bg-neutral-100 dark:bg-white/10 px-1.5 rounded py-0.5">{{ $product->packagings[0]['unit'] ?? 'Unit' }}</span>
                                @endif

                                @if($product->stock <= 0) <span class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Out of Stock</span>
                                    @else
                                    <span class="text-[10px] text-neutral-500 dark:text-neutral-400 font-mono">{{ $product->stock }} max base</span>
                                    @endif
                            </div>
                        </div>
                    </div>

                    {{-- Right: Base Display --}}
                    <div class="flex items-center gap-4 shrink-0 pl-2">
                        <template x-if="getItemQuantity('{{ $product->id }}_' + selectedPkgId) > 0">
                            <div class="flex items-center gap-2 px-2 py-1 bg-electric-blue/10 border border-electric-blue/20 rounded-md">
                                <span class="text-xs font-bold text-electric-blue" x-text="'In Cart: ' + getItemQuantity('{{ $product->id }}_' + selectedPkgId)"></span>
                            </div>
                        </template>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mt-4">
                <x-ui.pagination
                    wire:model.live="perPage"
                    :per-page-options="$perPageOptions"
                    :data="$this->products"
                />
            </div>
        </div>
    </div>

    {{-- ========================================== --}}
    {{-- RIGHT COLUMN: CART & CHECKOUT --}}
    {{-- ========================================== --}}
    <div class="w-full md:w-[380px] 2xl:w-[420px] flex flex-col bg-white dark:bg-[#0a1331]/80 border border-black/10 dark:border-white/10 overflow-hidden shadow-sm shrink-0">

        {{-- Header --}}
        <div class="p-2 border-b border-black/10 dark:border-white/10 flex items-center justify-between bg-neutral-50/50 dark:bg-white/5">
            <div class="flex items-center gap-2">
                <x-ui.icon name="shopping-cart" class="size-5 text-electric-blue" />
                <h2 class="font-bold text-neutral-900 dark:text-white">Current Order</h2>
            </div>
            <div class="flex item-center gap-2">
                {{-- Item count --}}
                <div class="flex justify-center items-center">
                    <span x-show="cart.length > 0" x-cloak class="bg-electric-blue text-white text-xs font-bold px-2 py-0.5 rounded-full" x-text="cart.length + ' items'"></span>
                </div>
                {{-- Button For clearing the cart --}}
                <button @click="clearCart()" class="size-8 flex items-center justify-center rounded-lg text-neutral-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Clear Cart">
                    <x-ui.icon name="trash" class="size-4" />
                </button>
            </div>
        </div>

        {{-- Toggles --}}
        <div class="p-2 border-b border-black/10 dark:border-white/10 space-y-4">
            <div class="flex p-1 bg-neutral-100 dark:bg-[#060A23] border border-black/5 dark:border-white/10 rounded-xl">
                <button wire:click="setCustomerMode('walk_in')" class="flex-1 py-2 text-xs font-medium rounded-lg flex items-center justify-center gap-2 transition-all {{ $customerMode === 'walk_in' ? 'bg-electric-blue text-white shadow-sm' : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5' }}">
                    <x-ui.icon name="user" class="size-3.5" /> Walk-in
                </button>
                <button wire:click="setCustomerMode('customer')" class="flex-1 py-2 text-xs font-medium rounded-lg flex items-center justify-center gap-2 transition-all {{ $customerMode === 'customer' ? 'bg-electric-blue text-white shadow-sm' : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5' }}">
                    <x-ui.icon name="user-group" class="size-3.5" /> Customer
                </button>
            </div>
            {{-- Conditional Customer Dropdown --}}
            @if($customerMode === 'customer')
                <div class="animate-in fade-in slide-in-from-top-1 duration-200">
                    <x-ui-select.styled
                        invalidate
                        wire:model="customer_id"
                        :options="$this->customers"
                        searchable
                        placeholder="Search existing customer..."
                    >
                        <x-slot:after>
                            <div
                                x-show="search?.length > 0"
                                class="px-2 py-2 border-t border-black/5 dark:border-white/10"
                            >
                                <x-ui.button
                                    class="w-full justify-center"
                                    size="sm"
                                    variant="outline"
                                    {{-- Close dropdown, open modal, and pre-fill the name field with their search term --}}
                                    x-on:click="show = false; $dispatch('open-modal', { id: 'customer-form' }); $wire.set('name', search);"
                                >
                                    <span x-html="`Create new customer: <b>${search}</b>`"></span>
                                </x-ui.button>
                            </div>
                        </x-slot:after>
                    </x-ui-select.styled>
                </div>
            @endif
        </div>

        {{-- Cart Items Area --}}
        <div class="flex-1 flex flex-col bg-neutral-50/50 dark:bg-[#060A23] overflow-y-auto custom-scrollbar">

            {{-- EMPTY STATE --}}
            <template x-if="cart.length === 0">
                <div class="flex flex-col items-center justify-center h-full my-auto">
                    <div class="size-14 shrink-0 rounded-full bg-white dark:bg-white/5 border border-black/15 dark:border-white/15 flex items-center justify-center text-neutral-600 dark:text-neutral-400 mb-4">
                        <x-ui.icon name="shopping-cart" class="size-7" />
                    </div>
                    <h3 class="text-neutral-900 dark:text-white font-bold mb-1">No items yet</h3>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 text-center mb-8">Select products to begin</p>
                    <div class="w-full space-y-2.5 max-w-[280px]">
                        <div class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-[#0a1331] rounded-md border border-black/15 dark:border-white/15 text-sm text-neutral-600 dark:text-neutral-400">
                            <x-ui.icon name="qr-code" class="size-5 text-electric-blue" />
                            <span class="flex-1 font-medium">Scan Barcode</span>
                            <span class="font-mono text-xs opacity-60 bg-neutral-100 dark:bg-white/5 px-1.5 rounded">F1</span>
                        </div>
                        <div class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-[#0a1331] rounded-md border border-black/15 dark:border-white/15 text-sm text-neutral-600 dark:text-neutral-400">
                            <x-ui.icon name="magnifying-glass" class="size-5 text-amber-500" />
                            <span class="flex-1 font-medium">Search Item</span>
                            <span class="font-mono text-xs opacity-60 bg-neutral-100 dark:bg-white/5 px-1.5 rounded">Ctrl+K</span>
                        </div>
                        <div class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-[#0a1331] rounded-md border border-black/15 dark:border-white/15 text-sm text-neutral-600 dark:text-neutral-400">
                            <x-ui.icon name="fire" class="size-5 text-rose-500" />
                            <span class="flex-1 font-medium">Quick Add</span>
                            <span class="font-mono text-xs opacity-60 bg-neutral-100 dark:bg-white/5 px-1.5 rounded">Select Product</span>
                        </div>
                    </div>
                </div>
            </template>

            {{-- ACTIVE CART ITEMS --}}
            <template x-if="cart.length > 0">
                <div class="divide-y divide-black/5 dark:divide-white/10">
                    <template x-for="item in cart" :key="item.cartId">
                        {{-- Card Container --}}
                        <div class="p-2 bg-white dark:bg-[#0a1331] border border-black/5 dark:border-white/10 flex flex-col gap-1.5">

                            {{-- Row 1: Title and Total Price --}}
                            <div class="flex justify-between items-start">
                                <div class="flex items-center flex-row gap-1 leading-tight min-w-0">
                                    <h4 class="text-sm font-bold text-neutral-900 dark:text-white leading-tight" x-text="item.name"></h4>
                                    -
                                    <h4 class="text-xs font-bold text-neutral-900 dark:text-white leading-tight" x-text="item.generic_name"></h4>
                                </div>
                                <span class="text-sm font-extrabold text-neutral-900 dark:text-white whitespace-nowrap" x-text="'₱' + (item.price * item.quantity).toFixed(2)"></span>
                            </div>

                            {{-- Row 2: Base Price --}}
                            <div class="text-xs text-neutral-500 dark:text-neutral-400 font-medium" x-text="'₱' + item.price.toFixed(2)"></div>

                            {{-- Row 3: Actions & Controls --}}
                            <div class="flex items-center justify-between pt-3 mt-1 border-t border-black/5 dark:border-white/10">

                                {{-- Left Actions (Price / Discount) --}}
                                {{-- <div class="flex items-center gap-3">
                                    <button @click="changePrice(item.id)" class="text-xs font-medium flex items-center gap-1.5 text-neutral-500 dark:text-neutral-400 hover:text-electric-blue dark:hover:text-electric-blue transition-colors">
                                        <x-ui.icon name="pencil" class="size-3.5" /> Price
                                    </button>
                                    <button @click="applyDiscount(item.id)" class="text-xs font-medium flex items-center gap-1.5 text-neutral-500 dark:text-neutral-400 hover:text-electric-blue dark:hover:text-electric-blue transition-colors">
                                        <x-ui.icon name="tag" class="size-3.5" /> Discount
                                    </button>
                                </div> --}}

                                {{-- Right Controls (Quantity & Trash) --}}
                                <div class="flex items-center gap-2">

                                    {{-- Editable Quantity Selector --}}
                                    <div class="flex items-center bg-neutral-100 dark:bg-[#060A23] rounded-lg border border-black/5 dark:border-white/10 p-0.5">
                                        <button @click="decrease(item.cartId)" class="size-7 flex items-center justify-center rounded-md hover:bg-white dark:hover:bg-white/10 text-neutral-600 dark:text-neutral-300 transition-colors">
                                            <x-ui.icon name="minus" class="size-3" />
                                        </button>

                                        <div class="flex items-center px-1">
                                            <input type="number"
                                                :value="item.quantity"
                                                @change="updateQuantity(item.cartId, $event, item.maxStock, item.name, item.generic_name, item.price, item.unit)"
                                                class="w-8 text-center bg-transparent border-none focus:ring-0 text-sm font-bold p-0 text-neutral-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            <span class="text-[10px] text-neutral-500 dark:text-neutral-400 pr-1" x-text="item.unit"></span>
                                        </div>

                                        <button @click="increase(item.cartId, item.name, item.generic_name, item.price, item.maxStock, item.unit)" class="size-7 flex items-center justify-center rounded-md hover:bg-white dark:hover:bg-white/10 text-neutral-600 dark:text-neutral-300 transition-colors" :disabled="item.quantity >= item.maxStock" :class="item.quantity >= item.maxStock ? 'opacity-50 cursor-not-allowed' : ''">
                                            <x-ui.icon name="plus" class="size-3" />
                                        </button>
                                    </div>

                                    {{-- Delete Button --}}
                                    <button @click="removeItem(item.cartId)" class="size-8 flex items-center justify-center rounded-lg text-neutral-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Remove Item">
                                        <x-ui.icon name="trash" class="size-4" />
                                    </button>

                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        {{-- Totals & Checkout Actions --}}
        <div class="p-2 border-t border-black/10 dark:border-white/10 bg-white dark:bg-[#0a1331]/90">
            <div class="space-y-1 mb-3">
                <div class="flex items-center justify-between text-sm text-neutral-500 dark:text-neutral-400">
                    <span>Net Sales:</span>
                    <span class="font-mono font-medium" x-text="'₱' + netSales.toFixed(2)"></span>
                </div>
                <div class="flex items-center justify-between text-xl font-black text-neutral-900 dark:text-white pt-1">
                    <span>Total:</span>
                    <span class="font-mono text-electric-blue dark:text-electric-blue" x-text="'₱' + total.toFixed(2)"></span>
                </div>
            </div>

            <button @click="triggerCheckout()" :disabled="cart.length === 0" :class="cart.length === 0 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-electric-blue active:scale-[0.98] shadow-lg shadow-electric-blue/20'" class="w-full mt-2 py-3 rounded-md bg-electric-blue text-white font-bold text-lg flex items-center justify-center gap-2 transition-all">
                <x-ui.icon name="credit-card" class="size-6" />
                Checkout
                <span class="ml-2 text-xs font-mono font-medium opacity-80 border border-white/30 rounded-md px-2 py-0.5">F4</span>
            </button>
        </div>
    </div>

    {{-- ========================================== --}}
    {{--             CUSTOMER MODAL                 --}}
    {{-- ========================================== --}}
    <x-ui.modal id="customer-form" width="lg" heading="Add New Customer">
        <form wire:submit.prevent="saveCustomer" class="space-y-4">

            <x-ui.field required>
                <x-ui.label>Full Name</x-ui.label>
                <x-ui.input wire:model="name" placeholder="e.g. Juan Dela Cruz" />
                <x-ui.error name="name" />
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Customer Type</x-ui.label>
                <x-ui-select.styled invalidate wire:model="customer_type_id" placeholder="Select Customer Type" :options="$this->availableCustomerTypes ?? []" searchable select="label:label|value:value" />
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
                    Create Customer
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
    {{-- ========================================== --}}
    {{-- CHECKOUT MODAL                  --}}
    {{-- ========================================== --}}
    <x-ui.modal id="checkout-modal" width="md" heading="Process Payment">
        <div class="space-y-4">

            {{-- Big Total --}}
            <div class="bg-neutral-100 dark:bg-[#060A23] p-4 rounded-xl text-center border border-black/5 dark:border-white/5">
                <p class="text-sm text-neutral-500 dark:text-neutral-400 font-bold tracking-widest uppercase mb-1">Amount Due</p>
                <h2 class="text-4xl font-black text-electric-blue" x-text="'₱' + total.toFixed(2)"></h2>
            </div>

            {{-- Payment Method Selection --}}
            <x-ui.field required>
                <x-ui.label>Payment Method</x-ui.label>
                <select x-model="checkoutState.payment_method_id" class="w-full rounded-md border-gray-300 dark:border-white/10 dark:bg-[#0a1331] shadow-sm text-sm focus:ring-electric-blue focus:border-electric-blue">
                    <option value="">-- Select Payment Method --</option>
                    <template x-for="method in paymentMethods" :key="method.id">
                        <option :value="method.id" x-text="method.name"></option>
                    </template>
                </select>
            </x-ui.field>

            {{-- Conditional Reference Number --}}
            <div x-show="requiresReference" x-cloak x-collapse>
                <x-ui.field required>
                    <x-ui.label>Reference Number</x-ui.label>
                    <x-ui.input x-model="checkoutState.reference_number" placeholder="e.g. 100012345678" />
                </x-ui.field>
            </div>

            {{-- Amount Received --}}
            <x-ui.field required>
                <div class="flex justify-between items-center mb-1">
                    <x-ui.label class="mb-0">Amount Received</x-ui.label>
                    <button @click="setExactAmount" type="button" class="text-xs font-bold text-electric-blue hover:underline bg-electric-blue/10 px-2 py-0.5 rounded">
                        Exact Amount
                    </button>
                </div>
                <x-ui.input type="number" step="0.01" x-model="checkoutState.amount_received" placeholder="₱0.00" class="font-mono text-lg" />
            </x-ui.field>

            {{-- Change Display --}}
            <div class="flex justify-between items-center pt-2">
                <span class="text-lg font-bold text-neutral-700 dark:text-neutral-300">Change:</span>
                <span class="text-2xl font-black text-green-600 dark:text-green-400 font-mono" x-text="'₱' + change.toFixed(2)"></span>
            </div>

            {{-- Remarks --}}
            <x-ui.field>
                <x-ui.label>Remarks / Notes (Optional)</x-ui.label>
                <x-ui.textarea x-model="checkoutState.remarks" rows="2" placeholder="Add any transaction notes here..."></x-ui.textarea>
            </x-ui.field>

            <div class="pt-4 flex justify-end gap-3 mt-4 border-t border-black/10 dark:border-white/10">
                <x-ui.button type="button" variant="outline" x-on:click="$dispatch('close-modal', { id: 'checkout-modal' })">Cancel</x-ui.button>
                <x-ui.button color="primary" icon="check" @click="submitToBackend($wire)" x-bind:disabled="checkoutState.amount_received < total || !checkoutState.payment_method_id || (requiresReference && !checkoutState.reference_number)">
                    Confirm Payment
                </x-ui.button>
            </div>
        </div>
    </x-ui.modal>
</div>
