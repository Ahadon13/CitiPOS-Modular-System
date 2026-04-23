<div class="min-h-screen bg-background px-3 py-8 sm:px-6 sm:py-12">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-4xl font-bold tracking-tight text-neutral-900 dark:text-white">
                    Select Branch
                </h1>
                <p class="text-sm text-neutral-500 dark:text-neutral-400 mt-1">
                    {{ auth()->user()->name }} / {{ auth()->user()->role }}
                </p>
            </div>

            @if($this->canAccessAdmin)
                <x-ui.button href="{{ route('admin.hub') }}" icon="squares-2x2" variant="outline" class="w-full sm:w-auto justify-center">
                    Admin Hub
                </x-ui.button>
            @endif
        </div>

        @if($this->branches->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($this->branches as $branch)
                    @php($module = $branch->productCategory->name ?? null)
                    <x-ui.card hoverless size="full" class="p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <h2 class="text-lg font-bold text-neutral-900 dark:text-white break-words">
                                    {{ $branch->name }}
                                </h2>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 line-clamp-2">
                                    {{ $branch->address ?? 'No address provided' }}
                                </p>
                            </div>
                            <span class="shrink-0 rounded-md bg-blue-50 px-2 py-1 text-xs font-bold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                                {{ $this->moduleLabel($module) }}
                            </span>
                        </div>

                        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <x-ui.button
                                size="sm"
                                icon="rectangle-group"
                                class="w-full justify-center"
                                wire:click="chooseBranch({{ $branch->id }}, 'inventory')"
                                wire:loading.attr="disabled"
                            >
                                Inventory
                            </x-ui.button>
                            <x-ui.button
                                size="sm"
                                icon="shopping-cart"
                                variant="outline"
                                class="w-full justify-center"
                                wire:click="chooseBranch({{ $branch->id }}, 'pos')"
                                wire:loading.attr="disabled"
                            >
                                POS
                            </x-ui.button>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        @else
            <x-ui.empty class="py-16">
                <x-ui.empty.media class="bg-neutral-100 dark:bg-white/5 rounded-full size-16 flex items-center justify-center">
                    <x-ui.icon name="building-storefront" class="size-8 text-neutral-400" />
                </x-ui.empty.media>
                <x-ui.empty.contents>
                    <x-ui.heading>No assigned branches</x-ui.heading>
                    <x-ui.text>Your role and branch access do not currently match any active module branch.</x-ui.text>
                </x-ui.empty.contents>
            </x-ui.empty>
        @endif
    </div>
</div>
