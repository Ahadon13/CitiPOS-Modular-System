@props([
    'data',
    'perPageOptions' => [10, 25, 50, 100],
    'minimal' => false,
    'perPageName' => 'Quantity',
])

<div {{ $attributes->class('flex items-center w-full gap-4 justify-between') }}>
    @if (! $minimal)
        <div class="flex w-full max-w-36 items-center gap-2 mt-4 shrink-0">
            <x-ui.field>
                <x-ui-select.styled
                    required
                    {{ $attributes->whereStartsWith('wire:model') }}
                    wire:model.live="perPage"
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
