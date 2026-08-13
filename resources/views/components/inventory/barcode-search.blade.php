@props([
    'config' => ['enabled' => false],
    'target' => 'search',
])

{{--
    Optional scan-to-search for an inventory list.

    A scan anywhere on the page drops the code into the existing search filter,
    so the product it identifies is the only row left. Nothing else about the
    page changes, and with the scanner disabled this renders nothing at all.
--}}
@if($config['enabled'] ?? false)
    <div
        x-data="barcodeScanner(@js($config))"
        {{-- Routed server-side: the lookup window takes the scan when open,
             otherwise it lands in the search filter. --}}
        x-on:barcode-scanned="$wire.routeScannedCode($event.detail.code)"
        {{ $attributes->merge(['class' => 'inline-flex']) }}
    >
        <span
            class="inline-flex items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400 whitespace-nowrap"
            title="Scan a product barcode to filter this list."
        >
            <x-ui.icon name="qr-code" class="size-4" />
            <span x-text="lastScan ? 'Scanned ' + lastScan : 'Scan to search'"></span>
        </span>
    </div>
@endif
