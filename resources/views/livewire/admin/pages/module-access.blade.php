<div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 px-3 py-4 sm:p-5">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-neutral-900 dark:text-white">Module Access</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Assign which business modules each role can enter. Branch access is still assigned per user.
            </p>
        </div>
    </div>

    <form wire:submit="save">
        <x-ui.card hoverless size="full" class="p-0 overflow-hidden">
            <div class="overflow-x-auto custom-scrollbar">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-neutral-50 dark:bg-[#0a1331] border-b border-black/10 dark:border-white/10 text-xs uppercase text-neutral-500">
                        <tr>
                            <th class="px-4 py-3">Role</th>
                            @foreach($this->modules as $module)
                                <th class="px-4 py-3 text-center">{{ $module['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5 dark:divide-white/5 bg-white dark:bg-[#060A23]">
                        @foreach($this->roles as $role)
                            <tr class="hover:bg-neutral-50 dark:hover:bg-white/5">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-neutral-900 dark:text-white">
                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                    </div>
                                    @if($role->name === \App\Enums\Role::Admin->value)
                                        <div class="text-xs text-neutral-500">Admin pages stay available by role; module access controls inventory and POS only.</div>
                                    @endif
                                </td>
                                @foreach($this->modules as $module)
                                    <td class="px-4 py-3 text-center">
                                        <label class="inline-flex items-center justify-center rounded-lg border border-black/10 dark:border-white/10 bg-neutral-50 dark:bg-white/5 px-3 py-2">
                                            <input
                                                type="checkbox"
                                                wire:model="access.{{ $role->id }}"
                                                value="{{ $module['value'] }}"
                                                class="rounded border-neutral-300 text-primary-600 focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                                            >
                                            <span class="sr-only">{{ $module['label'] }}</span>
                                        </label>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-black/10 dark:border-white/10 px-4 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-neutral-500">
                    Super Admin bypasses all module restrictions and is not listed here.
                </p>
                <x-ui.button type="submit" icon="check-circle" class="w-full sm:w-auto justify-center" wire:loading.attr="disabled" wire:target="save">
                    Save Access
                </x-ui.button>
            </div>
        </x-ui.card>
    </form>
</div>
