@props([
    'url' => null,
    'alt' => '',
    // sm: table thumbnail, md: list row, lg: detail panel, full: fills its box
    'size' => 'sm',
    // Lazy by default: these render in long tables and product grids where most
    // are below the fold.
    'lazy' => true,
    'icon' => 'cube',
])

@php
    $boxClasses = match ($size) {
        'sm' => 'size-10 rounded-md',
        'md' => 'size-14 rounded-lg',
        'lg' => 'size-24 rounded-xl',
        'full' => 'w-full aspect-square rounded-xl',
        default => 'size-10 rounded-md',
    };

    $iconClasses = match ($size) {
        'sm' => 'size-5',
        'md' => 'size-6',
        'lg' => 'size-10',
        'full' => 'size-10',
        default => 'size-5',
    };
@endphp

{{--
    Square "box" product image with an icon fallback.

    The icon always renders underneath and the image sits on top, so a product
    with no image -- or one whose file has gone missing from disk -- shows the
    icon instead of a broken-image glyph. No JavaScript, which matters because
    this renders once per row in long tables.
--}}
<div
    {{ $attributes->class([
        'relative shrink-0 overflow-hidden bg-neutral-100 dark:bg-white/5 border border-black/5 dark:border-white/10',
        'flex items-center justify-center',
        $boxClasses,
    ]) }}
>
    <x-ui.icon :name="$icon" @class(['text-neutral-400', $iconClasses]) />

    @if ($url)
        <img
            src="{{ $url }}"
            alt="{{ $alt }}"
            @if ($lazy) loading="lazy" decoding="async" @endif
            class="absolute inset-0 size-full object-cover"
        />
    @endif
</div>
