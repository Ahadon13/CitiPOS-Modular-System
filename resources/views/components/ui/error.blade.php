@props([
    'name' => null,
    'messages' => [],
])

@php
    use Illuminate\Support\Arr;

    $errorMessages = [];

    // 1. From $errors bag
    if ($name && $errors->has($name)) {
        // Use Arr::flatten to safely convert wildcard array-of-arrays into a flat list of strings
        $errorMessages = array_merge($errorMessages, Arr::flatten($errors->get($name)));
    }

    // 2. From manual messages prop
    if (filled($messages)) {
        $errorMessages = array_merge($errorMessages, Arr::flatten(Arr::wrap($messages)));
    }

    $errorMessages = array_filter(array_unique($errorMessages));

    $hasErrors = !empty($errorMessages);

    $classes = [
        '[&>[data-slot=icon]]:!text-red-600 [&>[data-slot=icon]]:dark:!text-red-400',
        'mt-2 text-sm text-red-600 dark:text-red-400',
        'flex items-center gap-2',
        'hidden' => !$hasErrors,
    ];
@endphp

@if ($hasErrors)
    <div
        aria-live="polite"
        role="alert"
        {{ $attributes->class(Arr::toCssClasses($classes)) }}
        data-slot="error"
    >
        <x-ui.icon name="exclamation-circle" class="shrink-0 w-4 h-4" />
        <div class="flex-1">
            @if (count($errorMessages) === 1)
                <span>{{ $errorMessages[0] }}</span>
            @else
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errorMessages as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endif
