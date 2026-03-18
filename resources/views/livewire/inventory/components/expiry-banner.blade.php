<div>
    @if($this->expiredCount > 0)
        <div
            x-data="{
                show: false,
                cacheKey: 'dismissed_{{ $module }}_expiry_{{ now()->toDateString() }}',

                init() {
                    if (localStorage.getItem(this.cacheKey) !== 'true') {
                        // Small delay so the slide-down animation triggers smoothly after page load
                        setTimeout(() => { this.show = true }, 100);
                    }
                },

                dismiss() {
                    this.show = false;
                    localStorage.setItem(this.cacheKey, 'true');
                }
            }"
            x-init="init()"
            x-show="show"
            x-cloak
            {{-- Slide Down Animation --}}
            x-transition:enter="transform transition ease-out duration-500"
            x-transition:enter-start="-translate-y-full opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transform transition ease-in duration-300"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="-translate-y-full opacity-0"

            {{-- Changed to 'absolute inset-x-0 z-50 shadow-lg' --}}
            class="absolute inset-x-0 z-99 flex items-center justify-between gap-x-6 px-6 py-2.5 sm:px-3.5 border-b shadow-lg {{ $module === 'pharmacy' ? 'bg-rose-50 border-rose-200 dark:bg-rose-900/90 dark:border-rose-800' : 'bg-orange-50 border-orange-200 dark:bg-orange-900/90 dark:border-orange-800' }}"
        >
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                <p class="text-sm leading-6 {{ $module === 'pharmacy' ? 'text-rose-900 dark:text-rose-200' : 'text-orange-900 dark:text-orange-200' }}">
                    <strong class="font-bold flex items-center gap-2">
                        <x-ui.icon name="exclamation-triangle" class="size-5" />
                        {{ ucfirst($module) }} Alert:
                    </strong>
                    <svg viewBox="0 0 2 2" class="mx-2.5 inline h-0.5 w-0.5 fill-current" aria-hidden="true"><circle cx="1" cy="1" r="1" /></svg>
                    You have <strong>{{ $this->expiredCount }}</strong> expired {{ $module === 'pharmacy' ? 'pharmaceutical' : 'grocery' }} items that must be pulled from the shelves immediately.
                </p>

                <a href="{{ $module === 'pharmacy' ? route('inventory.pharmacy.stocks') : '#' }}"
                   class="flex-none rounded-full px-3.5 py-1 text-sm font-semibold text-white shadow-sm focus-visible:outline-2 focus-visible:outline-offset-2 {{ $module === 'pharmacy' ? 'bg-rose-600 hover:bg-rose-500 focus-visible:outline-rose-900' : 'bg-orange-600 hover:bg-orange-500 focus-visible:outline-orange-900' }}">
                    Review now <span aria-hidden="true">&rarr;</span>
                </a>
            </div>

            <div class="flex flex-1 justify-end">
                <button type="button" @click="dismiss()" class="-m-3 p-3 focus-visible:-outline-offset-4">
                    <span class="sr-only">Dismiss</span>
                    <x-ui.icon name="x-mark" class="size-5 {{ $module === 'pharmacy' ? 'text-rose-900 dark:text-rose-200' : 'text-orange-900 dark:text-orange-200' }}" />
                </button>
            </div>
        </div>
    @endif
</div>
