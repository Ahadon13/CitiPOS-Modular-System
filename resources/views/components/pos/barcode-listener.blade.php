@props([
    'config' => ['enabled' => false],
])

{{--
    Optional barcode scanning for the POS.

    Renders nothing functional unless the active branch has the scanner
    switched on, so the existing click-driven flow is completely unaffected.

    A resolved scan is handed straight to addProductToCart() from the parent
    posApp scope -- the same method the product grid calls -- so scanning and
    clicking cannot drift apart.
--}}
@if($config['enabled'] ?? false)
    <div
        x-data="barcodeScanner(@js($config))"
        x-on:barcode-scanned="$wire.scanBarcode($event.detail.code)"
        x-on:barcode-resolved.window="addProductToCart($event.detail.product, $event.detail.packagingId)"
        class="shrink-0 hidden sm:inline-flex"
    >
        <span
            class="inline-flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400"
            title="A USB scanner can be used on this counter. Scan any product barcode to add it to the cart."
        >
            <x-ui.icon name="qr-code" class="size-4" />
            <span x-text="lastScan ? 'Scanned ' + lastScan : 'Scanner ready'"></span>
        </span>
    </div>
@else
    <div class="shrink-0 hidden sm:inline-flex">
        <span
            class="inline-flex items-center gap-2 rounded-lg border border-black/10 dark:border-white/10 px-3 py-2 text-xs font-medium text-neutral-400"
            title="Barcode scanning is switched off for this branch. An admin can enable it in Branch settings."
        >
            <x-ui.icon name="qr-code" class="size-4" />
            Scanner off
        </span>
    </div>
@endif
