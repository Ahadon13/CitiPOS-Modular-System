@props([
    'sticky' => true,
    'brand' => null
])

@php
    $classes = [
        '[grid-area:header] bg-white dark:bg-card z-40 p-2 my-auto min-h-[var(--header-height)] border-b flex flex-wrap items-center gap-y-2 dark:border-b-white/10 border-neutral-800/10',
        'sticky top-0' => $sticky,
    ];
@endphp

<div
    {{ $attributes->class($classes) }}
    data-slot="header"
>
    @if (filled($brand))
        <div
            {{
                $brand->attributes->class(
                    "flex items-center flex-shrink-0 justify-between items-center flex-shrink-0"
                )
            }}
            data-slot="header-brand"
        >
            {{ $brand }}
        </div>
    @endif

    {{ $slot }}
</div>
