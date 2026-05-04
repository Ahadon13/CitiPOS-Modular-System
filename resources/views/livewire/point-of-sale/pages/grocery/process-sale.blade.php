<div class="flex flex-col md:flex-row w-full min-h-full md:h-full" x-data="groceryPosApp(@js($this->activePaymentMethods), @js($this->customerTypesData), @entangle('customerMode').live, @entangle('customer_id').live, @js($this->customers))" @keydown.window="handleKeydown($event)">
    <div class="flex-1 flex flex-col bg-white dark:bg-[#0a1331]/80 border border-black/10 dark:border-white/10 overflow-hidden shadow-sm min-w-0">
        <div class="p-3 h-16 border-b border-black/10 dark:border-white/10 flex flex-row items-center gap-3">
            <x-ui.button color="primary" variant="outline" icon="qr-code" class="shrink-0 hidden sm:inline-flex">
                Barcode Scan
                <span class="ml-2 text-[10px] uppercase font-mono opacity-70 border border-electric-blue/30 rounded px-1.5 py-0.5">F1</span>
            </x-ui.button>
            <div class="flex-1 min-w-[200px]">
                <x-ui.input clearable leftIcon="magnifying-glass" placeholder="Search grocery products... (Ctrl+K)" wire:model.live.debounce.300ms="search" class="w-full bg-neutral-50 dark:bg-[#060A23]" />
            </div>
        </div>

        <div class="px-4 py-2.5 border-b border-black/10 dark:border-white/10 bg-neutral-50/50 dark:bg-white/5 shrink-0">
            <div class="flex items-center gap-2 mb-2">
                <x-ui.icon name="funnel" class="size-4 text-neutral-400 shrink-0" />
                <x-ui.input clearable leftIcon="magnifying-glass" placeholder="Search categories..." wire:model.live.debounce.300ms="categorySearch" class="w-full max-w-xs h-8 text-xs" />
            </div>
            <div class="max-h-24 overflow-y-auto custom-scrollbar flex flex-wrap items-center gap-2 pr-1">
            <button wire:click="setCategory(null)" class="whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm flex items-center gap-1.5 transition-transform active:scale-95 {{ $activeCategory === null ? 'bg-electric-blue text-white' : 'bg-neutral-100 dark:bg-[#060A23] border border-black/5 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-white/10' }}">
                All Products
            </button>
            @foreach($this->categories as $category)
                <button wire:click="setCategory({{ $category->id }})" class="whitespace-nowrap px-3 py-1.5 rounded-full text-xs font-medium flex items-center gap-1.5 transition-colors active:scale-95 shadow-sm {{ $activeCategory === $category->id ? 'bg-electric-blue text-white shadow-sm font-semibold' : 'bg-neutral-100 dark:bg-[#060A23] border border-black/5 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-200 dark:hover:bg-white/10' }}">
                    {{ $category->name }}
                </button>
            @endforeach
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-4 bg-neutral-50/50 dark:bg-[#060A23] custom-scrollbar">
            @if($this->products->isEmpty())
                <div class="flex flex-col items-center justify-center h-full text-neutral-500">
                    <x-ui.icon name="magnifying-glass" class="size-12 mb-3 opacity-20" />
                    <p>No products found matching "{{ $search }}"</p>
                </div>
            @endif

            <div class="flex flex-col gap-2">
                @foreach($this->products as $product)
                    <div
                        x-data="{ product: @js($product), selectedPkgId: @js($product->packagings[0]['id'] ?? null) }"
                        x-on:click="addProductToCart(product, selectedPkgId)"
                        class="cursor-pointer flex items-center justify-between p-3 rounded-xl border border-black/10 dark:border-white/10 bg-white dark:bg-[#0a1331] hover:border-electric-blue dark:hover:border-electric-blue transition-all hover:shadow-sm active:scale-[0.99] {{ ($product->stock_type !== 'special_order' && $product->stock <= 0) || count($product->packagings) === 0 ? 'opacity-60 grayscale pointer-events-none' : '' }}"
                    >
                        <div class="flex items-center gap-3 overflow-hidden">
                            <div class="size-18 rounded-lg bg-neutral-100 dark:bg-white/5 flex items-center justify-center shrink-0">
                                <x-ui.icon name="cube" class="size-12 text-neutral-400" />
                            </div>
                            <div class="truncate">
                                <div class="flex items-center gap-2 leading-tight min-w-0">
                                    <h3 class="text-lg font-bold text-neutral-900 dark:text-white truncate">{{ $product->name }}</h3>
                                    @if($product->barcode)
                                        <span class="text-xs font-mono text-neutral-500 truncate">{{ $product->barcode }}</span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    @if($product->description)
                                        <span class="text-xs text-neutral-500 dark:text-neutral-400 max-w-md truncate">{{ $product->description }}</span>
                                    @endif
                                    @if(count($product->packagings) > 1)
                                        <select x-model="selectedPkgId" @click.stop class="w-44 text-sm py-0.5 px-1.5 rounded border border-black/10 dark:border-white/10 bg-neutral-50 dark:bg-[#060A23] font-medium text-neutral-600 dark:text-neutral-300 focus:ring-0 focus:border-electric-blue">
                                            <template x-for="pkg in product.packagings" :key="pkg.id">
                                                <option :value="pkg.id" x-text="pkg.unit + ' - ₱' + getPackagePrice(pkg).toFixed(2)"></option>
                                            </template>
                                        </select>
                                    @elseif(count($product->packagings) === 0)
                                        <span class="text-sm font-bold text-red-500 bg-red-50 dark:bg-red-500/10 px-1.5 rounded py-0.5">No Packaging</span>
                                    @else
                                        <span class="text-sm font-bold text-neutral-500 bg-neutral-100 dark:bg-white/10 px-1.5 rounded py-0.5" x-text="product.packagings[0].unit + ' - ₱' + getPackagePrice(product.packagings[0]).toFixed(2)"></span>
                                    @endif

                                    @if($product->stock_type === 'special_order')
                                        <span class="px-1.5 py-0.5 rounded text-sm font-bold bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400 border border-sky-200 dark:border-sky-500/20 uppercase tracking-wider">
                                            Special Order
                                        </span>
                                    @elseif($product->stock <= 0)
                                        <span class="px-1.5 py-0.5 rounded text-sm font-bold bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400 border border-red-200 dark:border-red-500/20 uppercase tracking-wider">
                                            Out of Stock
                                        </span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-sm font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                            {{ number_format($product->stock, 2) }} {{ $product->base_unit }} in stock
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <template x-if="getItemQuantity('{{ $product->id }}_' + selectedPkgId) > 0">
                            <div class="flex items-center gap-2 px-2 py-1 bg-electric-blue/10 border border-electric-blue/20 rounded-md shrink-0 ml-2">
                                <span class="text-sm font-bold text-electric-blue" x-text="'In Cart: ' + formatQuantity(getItemQuantity('{{ $product->id }}_' + selectedPkgId))"></span>
                            </div>
                        </template>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                <x-ui.pagination wire:model.live="perPage" :per-page-options="$perPageOptions" :data="$this->products" />
            </div>
        </div>
    </div>

    <div class="w-full md:w-[380px] 2xl:w-[420px] flex flex-col bg-white dark:bg-[#0a1331]/80 border border-black/10 dark:border-white/10 overflow-hidden shadow-sm shrink-0">
        <div class="p-2 border-b border-black/10 dark:border-white/10 flex items-center justify-between bg-neutral-50/50 dark:bg-white/5">
            <div class="flex items-center gap-2">
                <x-ui.icon name="shopping-cart" class="size-5 text-electric-blue" />
                <h2 class="font-bold text-neutral-900 dark:text-white">Current Order</h2>
            </div>
            <div class="flex item-center gap-2">
                <div class="flex justify-center items-center">
                    <span x-show="cart.length > 0" x-cloak class="bg-electric-blue text-white text-xs font-bold px-2 py-0.5 rounded-full" x-text="cart.length + ' items'"></span>
                </div>
                <button @click="clearCart()" class="size-8 flex items-center justify-center rounded-lg text-neutral-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition-colors" title="Clear Cart">
                    <x-ui.icon name="trash" class="size-4" />
                </button>
            </div>
        </div>

        <div class="p-2 border-b border-black/10 dark:border-white/10 space-y-4">
            <div class="flex p-1 bg-neutral-100 dark:bg-[#060A23] border border-black/5 dark:border-white/10 rounded-xl">
                <button wire:click="setCustomerMode('walk_in')" class="flex-1 py-2 text-xs font-medium rounded-lg flex items-center justify-center gap-2 transition-all {{ $customerMode === 'walk_in' ? 'bg-electric-blue text-white shadow-sm' : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5' }}">
                    <x-ui.icon name="user" class="size-3.5" /> Walk-in
                </button>
                <button wire:click="setCustomerMode('customer')" class="flex-1 py-2 text-xs font-medium rounded-lg flex items-center justify-center gap-2 transition-all {{ $customerMode === 'customer' ? 'bg-electric-blue text-white shadow-sm' : 'text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-black/5 dark:hover:bg-white/5' }}">
                    <x-ui.icon name="user-group" class="size-3.5" /> Customer
                </button>
            </div>

            @if($customerMode === 'customer')
                <x-ui-select.styled invalidate wire:model.live="customer_id" :options="$this->customers" searchable placeholder="Search existing customer...">
                    <x-slot:after>
                        <div x-show="search?.length > 0" class="px-2 py-2 border-t border-black/5 dark:border-white/10">
                            <x-ui.button class="w-full justify-center" size="sm" variant="outline" x-on:click="show = false; $dispatch('open-modal', { id: 'customer-form' }); $wire.set('name', search);">
                                <span x-html="`Create new customer: <b>${search}</b>`"></span>
                            </x-ui.button>
                        </div>
                    </x-slot:after>
                </x-ui-select.styled>
            @endif
        </div>

        <div class="flex-1 flex flex-col bg-neutral-50/50 dark:bg-[#060A23] overflow-y-auto custom-scrollbar">
            <template x-if="cart.length === 0">
                <div class="flex flex-col items-center justify-center h-full my-auto">
                    <div class="size-14 shrink-0 rounded-full bg-white dark:bg-white/5 border border-black/15 dark:border-white/15 flex items-center justify-center text-neutral-600 dark:text-neutral-400 mb-4">
                        <x-ui.icon name="shopping-cart" class="size-7" />
                    </div>
                    <h3 class="text-neutral-900 dark:text-white font-bold mb-1">No items yet</h3>
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 text-center mb-8">Select products to begin</p>
                </div>
            </template>

            <template x-if="cart.length > 0">
                <div class="divide-y divide-black/5 dark:divide-white/10">
                    <template x-for="item in cart" :key="item.cartId">
                        <div class="p-2 bg-white dark:bg-[#0a1331] border border-black/5 dark:border-white/10 flex flex-col gap-1.5">
                            <div class="flex justify-between items-start gap-2">
                                <h4 class="text-sm font-bold text-neutral-900 dark:text-white leading-tight" x-text="item.name"></h4>
                                <span class="text-sm font-extrabold text-neutral-900 dark:text-white whitespace-nowrap" x-text="'₱' + (item.price * item.quantity).toFixed(2)"></span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs text-neutral-500 dark:text-neutral-400 font-medium">
                                <span>Unit price: <span class="font-mono font-bold text-neutral-900 dark:text-white" x-text="'₱' + item.price.toFixed(2)"></span></span>
                                <span class="text-neutral-400">/</span>
                                <span x-text="item.unit"></span>
                                <template x-if="discountPercentage > 0">
                                    <span class="px-1.5 py-0.5 rounded bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400 border border-green-200 dark:border-green-500/20 text-[10px] font-bold">
                                        <span x-text="(discountPercentage * 100).toFixed(0) + '% discount applies at checkout'"></span>
                                    </span>
                                </template>
                            </div>

                            <div class="flex items-center justify-end pt-3 mt-1 border-t border-black/5 dark:border-white/10">
                                <div class="flex items-center gap-2">
                                    <div class="flex items-center bg-neutral-100 dark:bg-[#060A23] rounded-lg border border-black/5 dark:border-white/10 p-0.5">
                                        <button @click="decrease(item.cartId)" class="size-7 flex items-center justify-center rounded-md hover:bg-white dark:hover:bg-white/10 text-neutral-600 dark:text-neutral-300 transition-colors">
                                            <x-ui.icon name="minus" class="size-3" />
                                        </button>
                                        <div class="flex items-center px-1">
                                            <input type="number" :step="getQuantityStep(item)" :value="formatQuantity(item.quantity)" @change="updateQuantity(item.cartId, $event)" class="w-14 text-center bg-transparent border-none focus:ring-0 text-sm font-bold p-0 text-neutral-900 dark:text-white [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                            <span class="text-[10px] text-neutral-500 dark:text-neutral-400 pr-1" x-text="item.unit"></span>
                                        </div>
                                        <button @click="increaseQuantity(item.cartId)" class="size-7 flex items-center justify-center rounded-md hover:bg-white dark:hover:bg-white/10 text-neutral-600 dark:text-neutral-300 transition-colors" :disabled="item.quantity >= getMaxQuantityForItem(item)" :class="item.quantity >= getMaxQuantityForItem(item) ? 'opacity-50 cursor-not-allowed' : ''">
                                            <x-ui.icon name="plus" class="size-3" />
                                        </button>
                                    </div>

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
                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="saveCustomer" icon="check">Create Customer</x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal id="checkout-modal" width="4xl" heading="Process Payment">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col h-full space-y-4">
                <div class="flex flex-col flex-1 min-h-0">
                    <p class="text-sm font-bold text-neutral-700 dark:text-neutral-300 mb-2">Order Summary</p>
                    <div class="overflow-y-auto border border-black/10 dark:border-white/10 rounded-lg bg-white dark:bg-black/20 custom-scrollbar p-2 max-h-[40vh] md:max-h-[300px]">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-black/5 dark:divide-white/5">
                                <template x-for="item in cart" :key="item.cartId">
                                    <tr>
                                        <td class="py-2 pr-2 font-medium text-neutral-900 dark:text-white leading-tight">
                                            <span x-text="item.name"></span>
                                        </td>
                                        <td class="py-2 px-2 text-neutral-500 dark:text-neutral-400 text-center whitespace-nowrap">
                                            <span x-text="formatQuantity(item.quantity) + ' ' + item.unit"></span>
                                        </td>
                                        <td class="py-2 pl-2 text-right font-mono font-bold text-neutral-900 dark:text-white whitespace-nowrap">
                                            <span x-text="'₱' + (item.price * item.quantity).toFixed(2)"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <x-ui.field>
                    <x-ui.label>Remarks / Notes (Optional)</x-ui.label>
                    <x-ui.textarea x-model="checkoutState.remarks" rows="2" placeholder="Add any transaction notes here..."></x-ui.textarea>
                </x-ui.field>
            </div>

            <div class="flex flex-col space-y-4">
                <div class="bg-neutral-100 dark:bg-[#060A23] p-4 rounded-xl text-center border border-black/5 dark:border-white/5">
                    <p class="text-sm text-neutral-500 dark:text-neutral-400 font-bold tracking-widest uppercase mb-1">Amount Due</p>
                    <div x-show="discountAmount === 0">
                        <h2 class="text-4xl font-black text-electric-blue font-mono" x-text="'₱' + total.toFixed(2)"></h2>
                    </div>
                    <div x-show="discountAmount > 0" x-cloak class="flex flex-col items-center">
                        <span class="text-lg font-bold text-neutral-400 dark:text-neutral-500 line-through decoration-red-500/50 decoration-2 font-mono" x-text="'₱' + netSales.toFixed(2)"></span>
                        <h2 class="text-4xl font-black text-electric-blue leading-tight font-mono" x-text="'₱' + total.toFixed(2)"></h2>
                        <div class="mt-1 text-[11px] font-bold text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/20 px-3 py-0.5 rounded-full inline-block">
                            Includes <span x-text="(discountPercentage * 100).toFixed(0) + '%'"></span> off (Saved ₱<span x-text="discountAmount.toFixed(2)"></span>)
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div x-show="customerMode === 'walk_in'" x-cloak class="col-span-2">
                        <x-ui.field>
                            <x-ui.label>Apply Walk-in Discount</x-ui.label>
                            <select x-model="walkInDiscountTypeId" class="w-full rounded-md border-gray-300 dark:border-white/10 dark:bg-[#0a1331] shadow-xs text-sm focus:ring-electric-blue focus:border-electric-blue">
                                <option value="">-- No Discount --</option>
                                <template x-for="type in customerTypes" :key="type.id">
                                    <option :value="type.id" x-text="type.name + (type.discount_percentage > 0 ? ' (' + type.discount_percentage + '%)' : '')"></option>
                                </template>
                            </select>
                        </x-ui.field>
                    </div>

                    <div class="col-span-2">
                        <x-ui.field required>
                            <x-ui.label>Payment Method</x-ui.label>
                            <select x-model="checkoutState.payment_method_id" class="w-full rounded-md border-gray-300 dark:border-white/10 dark:bg-[#0a1331] shadow-xs text-sm focus:ring-electric-blue focus:border-electric-blue">
                                <option value="">-- Select Method --</option>
                                <template x-for="method in paymentMethods" :key="method.id">
                                    <option :value="method.id" x-text="method.name"></option>
                                </template>
                            </select>
                        </x-ui.field>
                    </div>

                    <div class="col-span-2" x-show="requiresReference" x-cloak>
                        <x-ui.field required>
                            <x-ui.label>Ref Number</x-ui.label>
                            <x-ui.input x-model="checkoutState.reference_number" placeholder="e.g. 10001234" />
                        </x-ui.field>
                    </div>

                    <div class="col-span-2">
                        <x-ui.field required>
                            <div class="flex justify-between items-center mb-1 mt-1">
                                <x-ui.label class="mb-0">Received</x-ui.label>
                                <button @click="setExactAmount" type="button" class="text-[10px] font-bold text-electric-blue hover:underline bg-electric-blue/10 px-1.5 py-0.5 rounded">EXACT</button>
                            </div>
                            <x-ui.input type="number" step="0.01" x-model="checkoutState.amount_received" placeholder="₱0.00" class="font-mono text-lg font-bold" />
                        </x-ui.field>
                    </div>

                    <div class="col-span-2 flex flex-col justify-end">
                        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-lg p-2 flex flex-col items-center justify-center h-[58px]">
                            <span class="text-[10px] font-bold text-green-700 dark:text-green-400 uppercase tracking-wider leading-none">Change</span>
                            <span class="text-xl font-black text-green-600 dark:text-green-400 font-mono leading-none mt-1" x-text="'₱' + change.toFixed(2)"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4 flex justify-end gap-3 mt-4 border-t border-black/10 dark:border-white/10">
            <x-ui.button type="button" variant="outline" x-on:click="$dispatch('close-modal', { id: 'checkout-modal' })">Cancel</x-ui.button>
            <x-ui.button color="primary" icon="check" @click="submitToBackend($wire)" x-bind:disabled="Number(checkoutState.amount_received) < Number(total.toFixed(2)) || !checkoutState.payment_method_id || (requiresReference && !checkoutState.reference_number)" wire:loading.attr="disabled" wire:target="submitOrder" class="px-6">
                <span wire:loading.remove wire:target="submitOrder">Confirm Payment</span>
                <span wire:loading wire:target="submitOrder">Processing...</span>
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
