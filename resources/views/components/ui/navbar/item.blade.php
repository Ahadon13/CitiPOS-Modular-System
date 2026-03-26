@props([
    'icon' => null,
    'badge' => null,
    'label' => null,
    'href' => null,
    'active' => null,
    'size' => 'md',
])

@php
    $classes = [
        'flex items-center justify-center',

        // active link state
        'data-active-link:bg-[--alpha(var(--color-primary)_/5%)]
         data-active-link:!text-[var(--color-primary)]
         data-active-link:[&_[data-slot=icon]]:!text-[var(--color-primary)]',

        // add hover state only if the item isn't already active
        '[&:not([data-active-link])]:hover:bg-[--alpha(var(--color-primary)_/5%)]
         [&:not([data-active-link])]:hover:!text-[var(--color-primary)]
         [&:not([data-active-link])]:hover:[&_[data-slot=icon]]:!text-[var(--color-primary)]',
        'dark:text-neutral-200 text-neutral-600',
        // icon styles
        '[&_[data-slot=icon]]:dark:text-neutral-400 [&_[data-slot=icon]]:text-neutral-600 data-[active-link]:text-[var(--color-primary)]',

        'px-2 gap-x-1 py-1 rounded-box',
        // if there is a badge reduce the right padding for better UI
        '[&:has([data-slot=badge])]:pr-1'
    ];

    $iconAttributes = new \Illuminate\View\ComponentAttributeBag();
    $badgeAttributes = new \Illuminate\View\ComponentAttributeBag();

    foreach ($attributes->getAttributes() as $key => $value) {
        if (str_starts_with($key, 'icon:')) {
            $iconAttributes[substr($key, 5)] = $value;
        } elseif (str_starts_with($key, 'badge:')) {
            $badgeAttributes[substr($key, 6)] = $value;
        }
    }

    // --- SIZING LOGIC ---
    // 2. Map out the corresponding Tailwind classes for text and icons
    $textSizes = [
        'sm' => 'text-sm',
        'md' => 'text-base', // Your original default
        'lg' => 'text-lg',
    ];

    $iconSizes = [
        'sm' => '[:where(&)]:size-5! size-5!',
        'md' => '[:where(&)]:size-6! size-6!', // Your original default
        'lg' => '[:where(&)]:size-7! size-7!',
    ];

    // Fallback to 'md' if an invalid size is passed
    $textSizeClass = $textSizes[$size] ?? $textSizes['md'];
    $iconSizeClass = $iconSizes[$size] ?? $iconSizes['md'];


    // --- UPDATED ACTIVE LOGIC ---

    // 1. Check if the HREF matches the current URL (Default behavior)
    $matchesHref = $href !== '#' && url($href) === url()->current();

    // 2. Check if the custom 'active' prop matches (Custom behavior)
    $matchesCustom = false;

    if ($active !== null) {
        if (is_bool($active)) {
            $matchesCustom = $active;
        } else {
            // Handles String ('inventory.show') or Array (['inventory.show', 'inventory.edit'])
            $matchesCustom = request()->routeIs($active);
        }
    }

    // 3. The link is active if EITHER is true
    $isActive = $matchesHref || $matchesCustom;

@endphp

<x-ui.button.abstract
    :$href
    data-slot="navlist-item"
    {{ $attributes
        ->when($isActive, fn($attrs) => $attrs->merge(['data-active-link' => 'true'] ))
        ->class($classes)
    }}
>
    @if($icon)
        <x-ui.icon
            :attributes="$iconAttributes->class($iconSizeClass)"
            :name="$icon"
        />
    @endif

    <span class="{{ $textSizeClass }}">
        {{ $label }}
    </span>

    @if($badge)
        <x-ui.badge
            :attributes="$badgeAttributes->class('ml-auto')->merge([
                'size' => 'sm'
            ])"
        >
            {{ $badge }}
        </x-ui.badge>
    @endif
</x-ui.button.abstract>
