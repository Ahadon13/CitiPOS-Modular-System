@props([
    'config' => ['enabled' => false],
    // Livewire method that receives the scanned code.
    'action' => 'routeScannedCode',
])

{{--
    Optional scan-to-search for an inventory list.

    A scan anywhere on the page is handed to the component (by default
    routeScannedCode: the lookup window takes it when open, otherwise it lands
    in the search filter). Dialogs that claim scans -- e.g. Adjust Stock --
    take priority while they are open. With the scanner disabled this renders
    nothing at all.
--}}
@if($config['enabled'] ?? false)
    <div
        x-data="barcodeScanner(@js($config))"
        x-on:barcode-scanned="$wire.{{ $action }}($event.detail.code)"
        x-on:barcode-unresolved.window="BarcodeScanner.beep('error')"
        {{ $attributes->merge(['class' => 'inline-flex']) }}
    >
        <x-scanner.status-badge />
    </div>
@endif
