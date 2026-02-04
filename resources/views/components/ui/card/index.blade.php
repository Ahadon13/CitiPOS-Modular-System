@props(['size' => 'md', 'as' => 'div', 'href' => null, 'hoverless' => false, 'clear' => false])

@php
    $type = match(true) {
        $as === 'div' && !$href => 'div',
        $as === 'a' || $href => 'a',
        default => 'button'
    };

    $variantClasses = match ($size) {
        'xs' => 'max-w-xs',
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' =>  'max-w-2xl',
        '3xl' =>  'max-w-3xl',
        '4xl' =>  'max-w-4xl',
        '5xl' =>  'max-w-5xl',
        '6xl' =>  'max-w-6xl',
        '7xl' =>  'max-w-7xl',
        'full' => 'max-w-full',
    };

    $classes = [
        'w-full bg-white dark:bg-card border border-black/10 dark:border-white/10 hover:bg-zinc-100 dark:hover:bg-accent',
        '[:where(&)]:p-4 [:where(&)]:rounded-lg',
        'dark:hover:bg-card! hover:bg-white!' => $hoverless,
        '!bg-transparent hover:!bg-transparent !shadow-none !border-none !p-0 !dark:hover:bg-transparent' => $clear,
        $variantClasses
    ];

@endphp

@switch($type)
    @case('div')
        <div {{ $attributes->class(Arr::toCssClasses($classes)) }}>
            {{ $slot }}
        </div>
        @break

    @case('a')
        <a wire:navigate.hover href="{{ $href }}" {{ $attributes->class(Arr::toCssClasses($classes)) }}>
            {{ $slot }}
        </a>
        @break

    @default
        <div {{ $attributes->class(Arr::toCssClasses($classes)) }}>
            {{ $slot }}
        </div>
@endswitch
