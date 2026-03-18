@aware([
    'searchable' => false,
])

@props([
    'checkIcon' => 'check'
])

<x-ui.popup
    x-show="open"
    x-on:click.away="close()"
    x-on:keydown.escape="close()"
    x-anchor.offset.3="$refs.selectTrigger"
>
    @if ($searchable)
        <div
            @class([
                'grid items-center justify-center grid-cols-[20px_1fr] px-2 mb-1', // give the icon 20 px and leave the input take the rest
                '[&>[data-slot=icon]+[data-slot=search-control]]:pl-6', // because there is an icon give it 6 padding   
                'w-full border-b border-neutral-200 dark:border-neutral-700',
            ])    
        >
            <x-ui.icon 
                name="magnifying-glass"
                class="col-span-1 col-start-1 row-start-1 !text-neutral-500 dark:!text-neutral-400 !size-5"
            />

            <input 
                x-model="search"
                x-on:input.stop="isTyping = true"
                x-on:keydown.down.prevent.stop="handleKeydown($event)"
                x-on:keydown.up.prevent.stop="handleKeydown($event)"
                x-on:keydown.enter.prevent.stop="handleKeydown($event)"
                x-bind:aria-activedescendant="activeIndex !== null ? 'option-' + activeIndex : null"
                type="text"
                x-ref='searchControl'
                data-slot="search-control"
                placeholder="search..."
                @class([
                    'bg-transparent placeholder:text-neutral-500 dark:placeholder:text-neutral-400 dark:text-neutral-50 text-neutral-900 ',
                    'ring-0 ring-offset-0 outline-none focus:ring-0 border-0',
                    'col-span-4 col-start-1 row-start-1',
                ])
            >
        </div>
    @endif
    
    <ul 
        role="listbox"
        x-on:keydown.enter.prevent.stop="select($focus.focused().dataset.value)"
        x-on:keydown.up.prevent.stop="$focus.wrap().prev()"
        x-on:keydown.down.prevent.stop="$focus.wrap().next()"
        class="grid grid-cols-[auto_auto_1fr] gap-y-1 overflow-y-auto max-h-60"
    >
        {{ $slot }}
    </ul>
    <template x-if="isSearchable && isTyping && !hasFilteredResults">
        <x-ui.text class="h-14 flex items-center justify-center">
            no results found
        </x-ui.text>
    </template>
</x-ui.popup>
