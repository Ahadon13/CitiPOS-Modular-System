<div>
    @if($this->expiredCount > 0)
    @php
        $isPharmacyAlert = $module === 'pharmacy';
        $moduleLabel = match ($module) {
            'pharmacy' => 'Pharmacy',
            'grocery' => 'Grocery',
            'motor-shop' => 'Motor Shop',
            default => ucfirst($module),
        };
        $expiredItemLabel = match ($module) {
            'pharmacy' => 'pharmaceutical',
            'grocery' => 'grocery',
            'motor-shop' => 'motor part',
            default => 'inventory',
        };
        $reviewRoute = match ($module) {
            'pharmacy' => route('inventory.pharmacy.stocks'),
            'grocery' => route('inventory.grocery.stocks'),
            'motor-shop' => route('inventory.motor-shop.stocks'),
            default => '#',
        };
    @endphp
    <div x-data="{
                show: false,

                init() {
                    setTimeout(() => { this.show = true }, 150);
                },

                dismiss() {
                    this.show = false;
                }
            }" x-init="init()" x-show="show" x-cloak x-transition:enter="transform transition ease-out duration-500" x-transition:enter-start="-translate-y-full opacity-0 scale-95" x-transition:enter-end="translate-y-0 opacity-100 scale-100" x-transition:leave="transform transition ease-in duration-300" x-transition:leave-start="translate-y-0 opacity-100 scale-100" x-transition:leave-end="-translate-y-full opacity-0 scale-95" class="absolute inset-x-0 top-0 z-99 px-3 pt-3 sm:px-4 lg:px-6">
        <div class="relative overflow-hidden rounded-2xl border shadow-xl backdrop-blur-md
                {{ $isPharmacyAlert
                    ? 'border-rose-200/70 bg-linear-to-r from-rose-50 via-white to-rose-100 dark:border-rose-800/60 dark:from-rose-950/90 dark:via-rose-900/80 dark:to-rose-950/90'
                    : 'border-orange-200/70 bg-linear-to-r from-orange-50 via-white to-orange-100 dark:border-orange-800/60 dark:from-orange-950/90 dark:via-orange-900/80 dark:to-orange-950/90'
                }}">

            <div class="relative flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <div class="flex items-start gap-3 sm:gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full shadow-md
                            {{ $isPharmacyAlert
                                ? 'bg-rose-100 text-rose-600 dark:bg-rose-900/60 dark:text-rose-300'
                                : 'bg-orange-100 text-orange-600 dark:bg-orange-900/60 dark:text-orange-300'
                            }}">
                        <x-ui.icon name="exclamation-triangle" class="size-6" />
                    </div>

                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold tracking-wide uppercase
                                    {{ $isPharmacyAlert
                                        ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-300'
                                        : 'bg-orange-100 text-orange-700 dark:bg-orange-900/50 dark:text-orange-300'
                                    }}">
                                {{ $moduleLabel }} Alert
                            </span>

                            <span class="text-sm font-semibold
                                    {{ $isPharmacyAlert
                                        ? 'text-rose-700 dark:text-rose-300'
                                        : 'text-orange-700 dark:text-orange-300'
                                    }}">
                                {{ $this->expiredCount }} expired item{{ $this->expiredCount > 1 ? 's' : '' }}
                            </span>
                        </div>

                        <p class="mt-2 text-sm leading-6 sm:text-[15px]
                                {{ $isPharmacyAlert
                                    ? 'text-rose-900 dark:text-rose-100'
                                    : 'text-orange-900 dark:text-orange-100'
                                }}">
                            You have
                            <strong>{{ $this->expiredCount }}</strong>
                            expired
                            {{ $expiredItemLabel }}
                            item{{ $this->expiredCount > 1 ? 's' : '' }}
                            that should be pulled from the shelves immediately.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <a href="{{ $reviewRoute }}" class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-semibold text-white shadow-md transition duration-200
                           {{ $isPharmacyAlert
                                ? 'bg-rose-600 hover:bg-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-400 dark:bg-rose-500 dark:hover:bg-rose-400'
                                : 'bg-orange-600 hover:bg-orange-500 focus:outline-none focus:ring-2 focus:ring-orange-400 dark:bg-orange-500 dark:hover:bg-orange-400'
                           }}">
                        Review now
                        <span class="ml-2" aria-hidden="true">→</span>
                    </a>

                    <button type="button" @click="dismiss()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl transition
                            {{ $isPharmacyAlert
                                ? 'text-rose-500 hover:bg-rose-100 hover:text-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/50 dark:hover:text-rose-100'
                                : 'text-orange-500 hover:bg-orange-100 hover:text-orange-700 dark:text-orange-300 dark:hover:bg-orange-900/50 dark:hover:text-orange-100'
                            }}">
                        <span class="sr-only">Dismiss</span>
                        <x-ui.icon name="x-mark" class="size-5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
