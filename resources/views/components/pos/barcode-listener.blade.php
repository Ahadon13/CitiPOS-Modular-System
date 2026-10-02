@props([
    'config' => ['enabled' => false],
])

{{--
    Optional barcode scanning for the POS.

    Renders nothing functional unless the active branch has the scanner
    switched on, so the existing click-driven flow is completely unaffected.

    A resolved scan opens the "Confirm scanned item" dialog
    (<x-pos.scan-confirm-modal />) from the parent cart scope; Enter there adds
    it through addProductToCart() -- the same method the product grid calls --
    so scanning and clicking cannot drift apart.
--}}
@if($config['enabled'] ?? false)
    <div
        x-data="barcodeScanner(@js($config))"
        x-on:barcode-scanned="$wire.scanBarcode($event.detail.code)"
        x-on:barcode-resolved.window="openScanConfirm($event.detail.product, $event.detail.packagingId, $event.detail.code)"
        x-on:barcode-unresolved.window="BarcodeScanner.beep('error')"
        class="shrink-0 hidden sm:inline-flex"
    >
        <x-scanner.status-badge />
    </div>
@else
    <div class="shrink-0 hidden sm:inline-flex">
        <span
            class="inline-flex h-10 items-center gap-2.5 rounded-lg border border-black/10 dark:border-white/10 pl-2.5 pr-3 text-neutral-400"
            title="Barcode scanning is switched off for this branch. An admin can enable it in Settings > Scanners & Printers."
        >
            <span class="size-2.5 shrink-0 rounded-full bg-neutral-300 dark:bg-neutral-600"></span>
            <x-ui.icon name="qr-code" class="size-4 shrink-0 opacity-70" />
            <span class="flex flex-col leading-tight">
                <span class="text-xs font-semibold">Scanner off</span>
                <span class="text-[11px] opacity-75">Off for this branch</span>
            </span>
        </span>
    </div>
@endif
