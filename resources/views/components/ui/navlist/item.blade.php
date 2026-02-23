@aware([
    'collapsible' => true
])
@props([
    'icon' => null,
    'badge' => null,
    'label' => null,
    'href' => '#',
    'active' => null,
    'size' => 'md', // 1. Added size property (default: md)
])

@php
    // quick reference :
    // [:not(:has([data-collapsed]_&))_&]: means if the sidebar is not collapsed
    // [:has([data-collapsed]_&)_&]: means if the sidebar is collapsed

    $classes = [
        'isolate',
        'flex items-center [:where(&)]:justify-start',
        // When collapsed: center the content
        '[:has([data-collapsed]_&)_&]:justify-center',

        // active link state
        'data-active-link:bg-[--alpha(var(--color-primary)_/15%)]
         data-active-link:!text-[var(--color-primary)]
         data-active-link:[&_[data-slot=icon]]:!text-[var(--color-primary)]',

        // add hover state only if the item isn't already active
        '[&:not([data-active-link])]:hover:bg-[--alpha(var(--color-primary)_/15%)]
        [&:not([data-active-link])]:hover:!text-[var(--color-primary)]
        [&:not([data-active-link])]:hover:[&_[data-slot=icon]]:!text-[var(--color-primary)]',
        // text styles
        'dark:text-neutral-300 text-neutral-800',
        // icon styles
        '[&_[data-slot=icon]]:dark:text-neutral-300
         [&_[data-slot=icon]]:text-neutral-800
         data-[active-link]:text-[var(--color-primary)]',
        // gaps and padding
        'gap-x-2 pl-3 pr-3 py-1.5 rounded-md',
        // When collapsed: remove horizontal padding, keep vertical padding for centering
        '[:has([data-collapsed]_&)_&]:p-2',
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
<a
    href="{{ $href }}"

    @if($isActive)
       data-active-link
    @endif

    data-slot="navlist-item"
    {{ $attributes->class($classes) }}
>
    @if($icon)
        <x-ui.navlist.has-tooltip
            :tooltip="$label"
            :condition="$collapsible"
        >
            <x-ui.icon
                {{-- 3. Apply the dynamic icon size --}}
                :attributes="$iconAttributes->class($iconSizeClass)"
                :name="$icon"
            />
        </x-ui.navlist.has-tooltip>
    @endif

    {{-- 4. Apply the dynamic text size --}}
    <span class="{{ $textSizeClass }} [:has([data-collapsed]_&)_&]:hidden">
        {{ $label }}
    </span>

    @if($badge)
        <x-ui.badge
            :attributes="$badgeAttributes->merge([
                'size' => 'sm'
            ])"
            class="[:has([data-collapsed]_&)_&]:hidden  ml-auto"
        >{{ $badge }}</x-ui.badge>
    @endif
</a>
