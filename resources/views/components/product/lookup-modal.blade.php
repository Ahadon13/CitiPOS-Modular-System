@props([
    'id' => 'product-lookup-modal',
    'heading' => 'Product Lookup',
    'description' => null,
    'product' => null,
    'error' => null,
    'scannerEnabled' => false,
    // Route name for the edit button; omit to hide it (POS has no edit action).
    'editRoute' => null,
    'shortcut' => null,
])

@php
    use App\Support\MoneyHelper;
@endphp

{{--
    Read-only product details: stock, every packaging, its regular price and any
    partner prices.

    Nothing in here writes. On the POS that is deliberate -- a cashier can look a
    price up with a cart half-built and the transaction is untouched, because the
    only Livewire calls are lookups.
--}}
<x-ui.modal :id="$id" width="2xl" :heading="$heading" :description="$description" sticky-header>
    <div class="space-y-5">
        {{-- Scan / type --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-ui.input
                    wire:model.defer="lookupQuery"
                    wire:keydown.enter="lookupBySearch"
                    leftIcon="magnifying-glass"
                    placeholder="Scan a barcode or type a name / product code..."
                    data-barcode-input
                    autofocus
                    class="w-full"
                />
            </div>
            <div class="flex gap-2">
                <x-ui.button wire:click="lookupBySearch" wire:loading.attr="disabled" wire:target="lookupBySearch" icon="magnifying-glass">
                    <span wire:loading.remove wire:target="lookupBySearch">Look up</span>
                    <span wire:loading wire:target="lookupBySearch">...</span>
                </x-ui.button>
                @if ($product || $error)
                    <x-ui.button variant="outline" icon="x-mark" wire:click="clearLookup" title="Clear" />
                @endif
            </div>
        </div>

        @if ($scannerEnabled)
            <p class="text-xs text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                <x-ui.icon name="qr-code" class="size-4" />
                Scanner is active for this branch &mdash; just scan while this window is open.
            </p>
        @endif

        @if ($error)
            <div class="rounded-lg border border-amber-500/30 bg-amber-50 dark:bg-amber-900/20 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
                {{ $error }}
            </div>
        @endif

        @if ($product)
            {{-- Header: image + identity --}}
            <div class="flex flex-col sm:flex-row gap-4">
                <x-product.image
                    :url="$product['image_url']"
                    :alt="$product['name']"
                    size="lg"
                    :lazy="false"
                />

                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-neutral-900 dark:text-white">{{ $product['name'] }}</h3>

                    @if ($product['generic_name'])
                        <p class="text-sm text-neutral-500">{{ $product['generic_name'] }}</p>
                    @endif

                    <div class="flex flex-wrap items-center gap-2 mt-2">
                        <span class="text-[10px] font-mono uppercase tracking-wider px-2 py-0.5 rounded bg-neutral-100 dark:bg-white/10 text-neutral-600 dark:text-neutral-300">
                            {{ $product['product_code'] }}
                        </span>

                        @if ($product['dosage'] || $product['form'])
                            <span class="text-xs text-neutral-500">{{ trim(($product['dosage'] ?? '').' '.($product['form'] ?? '')) }}</span>
                        @endif

                        @if ($product['category'])
                            <span class="text-xs text-neutral-500">&middot; {{ $product['category'] }}</span>
                        @endif

                        @if ($product['requires_prescription'])
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                                Rx Required
                            </span>
                        @endif

                        @if (! $product['is_active'])
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-neutral-200 text-neutral-600 dark:bg-white/10 dark:text-neutral-400">
                                Disabled
                            </span>
                        @endif
                    </div>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-xs text-neutral-500 uppercase tracking-wider font-bold">On hand</span>
                        <span @class([
                            'text-lg font-black',
                            'text-emerald-600 dark:text-emerald-400' => $product['stock'] > 0,
                            'text-rose-600 dark:text-rose-400' => $product['stock'] <= 0,
                        ])>
                            {{ rtrim(rtrim(number_format($product['stock'], 2), '0'), '.') }}
                        </span>
                        <span class="text-xs text-neutral-500">{{ $product['base_unit'] }}</span>

                        @if ($product['stock_type'] === 'special_order')
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">
                                Special Order
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Prices per packaging --}}
            <div>
                <h4 class="text-sm font-bold text-neutral-900 dark:text-white mb-2">Prices</h4>

                <div class="rounded-xl border border-black/10 dark:border-white/10 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 dark:bg-[#0a1331] text-xs uppercase text-neutral-500 border-b border-black/10 dark:border-white/10">
                            <tr>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3">Barcode</th>
                                <th class="px-4 py-3 text-right">Regular Price</th>
                                <th class="px-4 py-3">Partner Prices</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-black/10 dark:divide-white/10">
                            @forelse ($product['packagings'] as $packaging)
                                <tr @class(['bg-blue-50/60 dark:bg-blue-900/20' => $packaging['scanned']])
                                >
                                    <td class="px-4 py-3">
                                        <span class="font-bold text-neutral-900 dark:text-white">{{ $packaging['unit'] }}</span>
                                        @if ($packaging['conversion_factor'] > 1)
                                            <span class="text-xs text-neutral-500 block">
                                                = {{ rtrim(rtrim(number_format($packaging['conversion_factor'], 2), '0'), '.') }} {{ $product['base_unit'] }}
                                            </span>
                                        @endif
                                        @if ($packaging['scanned'])
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Scanned</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-neutral-500">{{ $packaging['barcode'] ?: '—' }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-neutral-900 dark:text-white">
                                        {{ MoneyHelper::formatCents($packaging['regular_price']) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (count($packaging['partner_prices']) > 0)
                                            <div class="space-y-1">
                                                @foreach ($packaging['partner_prices'] as $partner)
                                                    <div class="flex items-center justify-between gap-3">
                                                        <span class="text-xs font-semibold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                            {{ $partner['partner'] }}
                                                        </span>
                                                        <span class="font-bold text-blue-600 dark:text-blue-400">
                                                            {{ MoneyHelper::formatCents($partner['price']) }}
                                                        </span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-neutral-400">No partner price</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-neutral-500">
                                        This product has no sellable packaging yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif (! $error)
            <div class="py-10">
                <x-ui.empty>
                    <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-12 flex items-center justify-center">
                        <x-ui.icon name="qr-code" class="size-6 text-neutral-400" />
                    </x-ui.empty.media>
                    <x-ui.empty.contents>
                        <x-ui.heading>Scan or search a product</x-ui.heading>
                        <x-ui.text>Its stock, regular price and any partner prices will show here.</x-ui.text>
                    </x-ui.empty.contents>
                </x-ui.empty>
            </div>
        @endif
    </div>

    <x-slot:footer>
        <div class="flex w-full flex-col sm:flex-row items-center justify-between gap-3">
            <p class="text-xs text-neutral-500">
                @if ($shortcut)
                    Press <x-ui.kbd>{{ $shortcut }}</x-ui.kbd> to open this any time.
                @endif
            </p>
            <div class="flex gap-2 w-full sm:w-auto">
                @if ($editRoute && $product)
                    <x-ui.button
                        variant="outline"
                        icon="pencil-square"
                        class="w-full sm:w-auto justify-center"
                        href="{{ route($editRoute, ['product' => $product['id']]) }}"
                        wire:navigate
                    >
                        Edit Product
                    </x-ui.button>
                @endif
                <x-ui.button
                    variant="outline"
                    class="w-full sm:w-auto justify-center"
                    x-on:click="$dispatch('close-modal', { id: '{{ $id }}' })"
                >
                    Close
                </x-ui.button>
            </div>
        </div>
    </x-slot:footer>
</x-ui.modal>
