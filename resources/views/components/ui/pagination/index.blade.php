@props([
    'data',
    'perPageOptions' => [10, 25, 50, 100],
    'minimal' => false,
    'perPageName' => 'Quantity',
])

@php
    // Only fall back to the shared "perPage" property when the caller has not
    // bound its own. A page with two tables must be able to give each its own
    // per-page property, otherwise changing one table's page size silently
    // repaginates the other.
    $boundModel = collect($attributes->getAttributes())
        ->keys()
        ->first(fn (string $key) => str_starts_with($key, 'wire:model'));

    // The wrapper must not carry the model binding through to the div.
    $wrapperAttributes = $attributes->except($boundModel ? [$boundModel] : []);

    // The default is merged onto $attributes itself, deliberately. Blade only
    // recognises an attribute-bag spread inside a component tag when the
    // variable is literally named `$attributes` -- spreading any other variable
    // (or using @if in the attribute list) stops the tag being compiled at all,
    // and it renders as raw text.
    if (! $boundModel) {
        $attributes = $attributes->merge(['wire:model.live' => 'perPage']);
    }
@endphp

<div {{ $wrapperAttributes->class('flex flex-wrap items-center w-full gap-3 sm:gap-4 justify-center sm:justify-between') }}>
    @if (! $minimal)
        <div class="flex w-full max-w-36 items-center gap-2 mt-4 shrink-0">
            <x-ui.field>
                <x-ui-select.styled
                    required
                    {{ $attributes->whereStartsWith('wire:model') }}
                    placeholder="Items per page"
                    :options="$perPageOptions"
                />
            </x-ui.field>
            <x-ui.label>{{ $perPageName }}</x-ui.label>
        </div>
    @endif
    @if ($data instanceof Illuminate\Pagination\LengthAwarePaginator)
        <div class="lg:hidden mt-4">
            {{ $data->links('livewire::simple-tailwind') }}
        </div>
        <div class="hidden lg:block mt-4">
            {{ $data->links('livewire::tailwind') }}
        </div>
    @endif
</div>
