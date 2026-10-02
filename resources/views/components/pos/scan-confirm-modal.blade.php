{{--
    "Confirm scanned item" dialog, shared by every POS module.

    Pure Alpine, driven by scanConfirmMixin() in the parent cart scope -- no
    server round trip between the scan and Enter, so confirming is instant.

    Keyboard: Enter = add to cart, Esc = cancel, Up/Down or +/- = quantity,
    digits = type a quantity. Scanning the same barcode again adds one more.
--}}
<div
    x-show="scanConfirm.open"
    x-cloak
    x-transition.opacity.duration.100ms
    class="fixed inset-0 z-[200] flex items-start sm:items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="scan-confirm-title"
    x-on:click.self="closeScanConfirm()"
>
    <div
        class="w-full max-w-md rounded-xl bg-white dark:bg-[#0a1331] border border-black/10 dark:border-white/10 shadow-2xl overflow-hidden"
        x-show="scanConfirm.open"
        x-transition.scale.95.duration.100ms
    >
        <template x-if="scanConfirm.product">
            <div>
                {{-- Header --}}
                <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-black/10 dark:border-white/10 bg-emerald-50/60 dark:bg-emerald-900/10">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-ui.icon name="qr-code" class="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <h2 id="scan-confirm-title" class="text-sm font-bold text-neutral-900 dark:text-white">Confirm scanned item</h2>
                    </div>
                    <span class="font-mono text-[11px] text-neutral-500 truncate" x-text="scanConfirm.code"></span>
                </div>

                <div class="p-5 space-y-4">
                    {{-- Product --}}
                    <div class="flex gap-3">
                        <div class="size-16 shrink-0 rounded-lg border border-black/10 dark:border-white/10 bg-neutral-50 dark:bg-white/5 overflow-hidden flex items-center justify-center">
                            <template x-if="scanConfirm.product.image_url">
                                <img :src="scanConfirm.product.image_url" alt="" class="size-full object-cover">
                            </template>
                            <template x-if="!scanConfirm.product.image_url">
                                <x-ui.icon name="cube" class="size-7 text-neutral-300 dark:text-neutral-600" />
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-extrabold leading-tight text-neutral-900 dark:text-white" x-text="scanConfirm.product.name"></p>
                            <p class="text-xs text-neutral-500 truncate" x-show="scanConfirm.product.generic_name" x-text="scanConfirm.product.generic_name"></p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                <span
                                    x-show="scanConfirmIsSpecialOrder()"
                                    class="text-[10px] font-bold uppercase tracking-wide rounded px-1.5 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"
                                >Special order</span>
                                <span
                                    x-show="scanConfirm.product.required_prescription"
                                    class="text-[10px] font-bold uppercase tracking-wide rounded px-1.5 py-0.5 bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400"
                                >Rx required</span>
                            </div>
                        </div>
                    </div>

                    {{-- Unit (only when the product sells in more than one) --}}
                    <div x-show="scanConfirm.product.packagings.length > 1">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-neutral-500 mb-1.5">Unit</p>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="pkg in scanConfirm.product.packagings" :key="pkg.id">
                                <button
                                    type="button"
                                    tabindex="-1"
                                    x-on:click="selectScanConfirmPackaging(pkg.id)"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors"
                                    :class="pkg.id == scanConfirm.packagingId
                                        ? 'border-electric-blue bg-electric-blue/10 text-electric-blue'
                                        : 'border-black/10 dark:border-white/10 text-neutral-600 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-white/5'"
                                    x-text="pkg.unit + ' · ₱' + getPackagePrice(pkg).toFixed(2)"
                                ></button>
                            </template>
                        </div>
                    </div>

                    {{-- Price & stock --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg border border-black/10 dark:border-white/10 px-3 py-2">
                            <p class="text-[11px] uppercase tracking-wide text-neutral-500">
                                Price
                                <span x-show="typeof getPackagePriceSource === 'function' && getPackagePriceSource(scanConfirmPackaging()) === 'Partnership'" class="text-emerald-600 dark:text-emerald-400 normal-case font-semibold">· Partner</span>
                            </p>
                            <p class="font-mono text-base font-bold text-neutral-900 dark:text-white">
                                <span x-text="'₱' + scanConfirmPrice().toFixed(2)"></span>
                                <span class="text-xs font-normal text-neutral-500" x-text="'/ ' + (scanConfirmPackaging()?.unit ?? '')"></span>
                            </p>
                        </div>
                        <div class="rounded-lg border px-3 py-2" :class="scanConfirmMax() > 0 ? 'border-black/10 dark:border-white/10' : 'border-rose-500/40 bg-rose-50 dark:bg-rose-900/20'">
                            <p class="text-[11px] uppercase tracking-wide text-neutral-500">Can still add</p>
                            <p class="font-mono text-base font-bold" :class="scanConfirmMax() > 0 ? 'text-neutral-900 dark:text-white' : 'text-rose-600 dark:text-rose-400'">
                                <span x-text="scanConfirmIsSpecialOrder() ? 'Order in' : (scanConfirmMax() > 0 ? scanConfirmMax() : 'Out of stock')"></span>
                            </p>
                            <p class="text-[11px] text-neutral-500" x-show="scanConfirmInCart() > 0" x-text="scanConfirmInCart() + ' already in cart'"></p>
                        </div>
                    </div>

                    {{-- Quantity --}}
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-2">
                            <button type="button" tabindex="-1" x-on:click="changeScanConfirmQty(-1)" class="size-9 rounded-lg border border-black/10 dark:border-white/10 flex items-center justify-center hover:bg-neutral-50 dark:hover:bg-white/5" aria-label="Decrease quantity">
                                <x-ui.icon name="minus" class="size-4" />
                            </button>
                            <input
                                id="scan-confirm-qty"
                                type="number"
                                inputmode="decimal"
                                :step="scanConfirmStep()"
                                min="0"
                                x-model="scanConfirm.quantity"
                                x-on:blur="normalizeScanConfirmQty()"
                                class="w-20 h-9 rounded-lg border border-black/10 dark:border-white/10 bg-white dark:bg-[#060A23] text-center font-mono font-bold text-neutral-900 dark:text-white focus:ring-2 focus:ring-electric-blue/40 focus:border-electric-blue"
                                aria-label="Quantity"
                            >
                            <button type="button" tabindex="-1" x-on:click="changeScanConfirmQty(1)" class="size-9 rounded-lg border border-black/10 dark:border-white/10 flex items-center justify-center hover:bg-neutral-50 dark:hover:bg-white/5" aria-label="Increase quantity">
                                <x-ui.icon name="plus" class="size-4" />
                            </button>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] uppercase tracking-wide text-neutral-500">Line total</p>
                            <p class="font-mono text-lg font-extrabold text-electric-blue" x-text="'₱' + (scanConfirmPrice() * (parseFloat(scanConfirm.quantity) || 0)).toFixed(2)"></p>
                        </div>
                    </div>

                    {{-- Refused scan / problems --}}
                    <p
                        x-show="scanConfirm.warning"
                        x-text="scanConfirm.warning"
                        class="rounded-lg border border-amber-500/30 bg-amber-50 dark:bg-amber-900/20 px-3 py-2 text-xs text-amber-800 dark:text-amber-300"
                    ></p>
                    <p
                        x-show="!scanConfirmCanAdd() && scanConfirmMax() <= 0"
                        class="rounded-lg border border-rose-500/30 bg-rose-50 dark:bg-rose-900/20 px-3 py-2 text-xs text-rose-700 dark:text-rose-300"
                    >No stock left for this unit, so it cannot be added. Press Esc to cancel.</p>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between gap-3 px-5 py-3 border-t border-black/10 dark:border-white/10 bg-neutral-50/60 dark:bg-white/5">
                    <p class="hidden sm:block text-[11px] text-neutral-500">Scan again for +1</p>
                    <div class="flex items-center gap-2 ml-auto">
                        <button type="button" tabindex="-1" x-on:click="closeScanConfirm()" class="rounded-lg border border-black/10 dark:border-white/10 px-3 py-2 text-sm font-semibold text-neutral-700 dark:text-neutral-200 hover:bg-white dark:hover:bg-white/5">
                            Cancel <span class="ml-1 text-[10px] font-mono opacity-60">Esc</span>
                        </button>
                        <button
                            type="button"
                            tabindex="-1"
                            x-on:click="confirmScan()"
                            :disabled="!scanConfirmCanAdd()"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Add to cart <span class="ml-1 text-[10px] font-mono opacity-80">Enter</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
