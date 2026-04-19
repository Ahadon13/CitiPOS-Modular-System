<div class="space-y-4">

    <form wire:submit="savePrices" class="space-y-4 animate-in fade-in duration-300">

        <div class="mb-2">
            {{-- Optional: If your HasAuth trait exposes the user relation, you can display the branch name here --}}
            <h4 class="text-sm font-bold text-neutral-900 dark:text-white">Set Mandated Prices</h4>
            <p class="text-xs text-neutral-500">Prices set here will only apply to <strong>{{ $this->user->branch->name ?? 'your current location' }}</strong>. Leave the price blank to use the regular retail price.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($customerTypes as $type)
            <div class="flex items-center justify-between p-3 border border-neutral-200 dark:border-white/10 rounded-lg bg-neutral-50 dark:bg-[#060A23]">

                {{-- Partner Name & Info --}}
                <div>
                    <span class="text-sm font-bold text-neutral-900 dark:text-white">{{ $type->name }}</span>
                    @if($type->discount_percentage > 0)
                    <span class="block text-[10px] font-medium text-emerald-600 dark:text-emerald-400 mt-0.5">
                        Has global {{ number_format($type->discount_percentage, 0) }}% discount
                    </span>
                    @endif
                </div>

                {{-- Price Input --}}
                <div class="w-32 shrink-0">
                    <x-ui.input wire:model="prices.{{ $type->id }}" type="number" step="any" min="0" placeholder="Price (₱)" left-icon="currency-dollar" />
                </div>

            </div>
            @endforeach
        </div>

        <div class="flex justify-end pt-4 border-t border-neutral-200 dark:border-white/10 mt-4">
            <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="savePrices" icon="check-circle" color="primary">
                <span wire:loading.remove wire:target="savePrices">Save Price Book</span>
                <span wire:loading wire:target="savePrices">Saving...</span>
            </x-ui.button>
        </div>

    </form>

</div>
