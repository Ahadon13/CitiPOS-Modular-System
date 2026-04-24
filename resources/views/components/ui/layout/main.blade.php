@php
    $classes = [
        '[grid-area:main]',
        'min-w-0',
        'overflow-x-hidden',
        'overflow-y-auto', 
        'min-h-screen max-h-screen', 
        '[&>:has([data-slot=header])]:p-0 [&>:not(:has([data-slot=header]))]:p-2'
    ];    
@endphp

<div
    {{ $attributes->class($classes) }}
    data-slot="main"
>
    {{ $slot }}
</div>
