@props([
    'collapsable' => true,
    'variant' => 'sidebar-main',
])

@php
    $basePath = 'ui.layout.variant';
    $variantPath = match($variant) {
        'sidebar-main', 'header-sidebar', 'header-only' => "{$basePath}.{$variant}", // Added header-only here!
        default => "{$basePath}.sidebar-main",
    };
@endphp

{{-- use the appropriate layout based on the variant --}}
<x-dynamic-component
    :component="$variantPath"
    :collapsable="$collapsable"
>
    {{ $slot }}
</x-dynamic-component>

{{-- solves alpine limitations 🙂 --}}
@if($variant !== 'header-only')
    <x-ui.layout.runtime
        :$collapsable
    />
@endif
