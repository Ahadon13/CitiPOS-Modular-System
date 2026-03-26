<div class="max-w-7xl mx-auto space-y-6 p-5">
    <div class="flex items-center justify-between gap-6">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Branches</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">Manage locations and monitor performance</p>
        </div>
        <x-ui.button size="sm" icon="plus">
            Add Branch
        </x-ui.button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($this->branches as $branch)
            <x-ui.card hoverless href="{{ route('admin.branches.view', ['branch' => $branch]) }}" class="group relative overflow-hidden p-6 transition-all hover:shadow-lg hover:-translate-y-1">


                <div class="absolute top-0 right-0 -mr-16 -mt-16 h-32 w-32 rounded-full bg-blue-50/50 dark:bg-blue-900/10 transition-transform group-hover:scale-150"></div>

                <div class="relative space-y-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-50 dark:bg-blue-950/30 rounded-xl">
                                <x-ui.icon name="building-storefront" class="size-7 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-neutral-900 dark:text-white group-hover:text-blue-600 transition-colors">
                                    {{ $branch->name }}
                                </h3>
                                <p class="text-sm text-neutral-500 dark:text-neutral-400 line-clamp-1">
                                    {{ $branch->address ?? 'No address provided' }}
                                </p>
                            </div>
                        </div>
                        @if($branch->is_active)
                            <x-ui.badge color="emerald" size="sm">
                                Active
                            </x-ui.badge>
                        @else
                            <x-ui.badge color="red" size="sm">
                                Inactive
                            </x-ui.badge>
                        @endif
                    </div>

                    <hr class="border-neutral-100 dark:border-neutral-800" />

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <p class="text-[10px] uppercase tracking-wider text-neutral-400 font-bold">Total Profit</p>
                            <p class="text-lg font-bold text-neutral-900 dark:text-white">
                                ₱{{ number_format($branch->profit->getAmount() / 100, 2) }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] uppercase tracking-wider text-neutral-400 font-bold">Transactions</p>
                            <p class="text-lg font-bold text-neutral-900 dark:text-white">
                                {{ number_format($branch->transaction_count) }}
                            </p>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center text-sm font-semibold text-blue-600 dark:text-blue-400">
                        <span>View Inventory Dashboard</span>
                        <x-ui.icon name="arrow-right" class="ml-2 w-4 h-4 transition-transform group-hover:translate-x-1" />
                    </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>
</div>
