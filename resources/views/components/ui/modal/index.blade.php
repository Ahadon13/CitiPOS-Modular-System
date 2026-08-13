@props([
    'alignment' => 'start',
    'ariaLabelledby' => null,
    'autofocus' => true,
    'closeButton' => true,
    'closeByClickingAway' => true,
    'closeByEscaping' => true,
    'openEventName' => 'open-modal',
    'closeEventName' => 'close-modal',
    'forceCloseEventName' => 'force-close-modal',
    'description' => null,
    'displayClasses' => 'flex flex-col',
    'footer' => null,
    'header' => null,
    'heading' => null,
    'icon' => null,
    'iconVariant' => 'primary',
    'id' => null,
    'slideover' => false,
    'stickyFooter' => false,
    'stickyHeader' => false,
    'trigger' => null,
    'visible' => true,
    'width' => 'sm',
    'backdrop' => 'blur', // blur, dark, transparent
    'persistent' => false, // prevents closing by clicking away or escape
    'animation' => null, // scale, slide, fade
    'position' => 'center', // center, top, bottom
])

@php
    $hasDescription = filled($description);
    $hasFooter = filled($footer);
    $hasHeading = filled($heading);
    $hasIcon = filled($icon);
    $hasSlot = !empty(trim($slot ?? ''));

    // Set default animation based on slideover state
    $animation = $animation ?? ($slideover ? 'slide' : 'scale');


    $modalId = $id ?? 'modal-' . uniqid();

    // Width classes mapping
    $widthClass = match($width){
        'xs' => 'max-w-xs',
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
        'full' => 'max-w-full',
        'screen-sm' => 'max-w-screen-sm',
        'screen-md' => 'max-w-screen-md',
        'screen-lg' => 'max-w-screen-lg',
        'screen-xl' => 'max-w-screen-xl',
        'screen-2xl' => 'max-w-screen-2xl',
        'screen' => 'fixed inset-0',
        default => $width
    };


    // Alignment classes
    $alignmentClass = match($alignment){
        'start' => 'text-left',
        'center' => 'text-center',
        'end' => 'text-right',
        'left' => 'text-left',
        'right' => 'text-right',
        default => $alignment
    };


    // Icon color classes
    $iconColorClass = match($iconVariant){
        'primary' => 'bg-white text-primary-fg dark:bg-white/5',
        'secondary' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-900/20 dark:text-neutral-400',
        'success' => 'bg-green-100 text-green-600 dark:bg-green-900/20 dark:text-green-400',
        'warning' => 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900/20 dark:text-yellow-400',
        'danger' => 'bg-red-100 text-red-600 dark:bg-red-900/20 dark:text-red-400',
        'info' => 'bg-cyan-100 text-cyan-600 dark:bg-cyan-900/20 dark:text-cyan-400',
        default => 'bg-white text-primary dark:bg-white/5', // fallback to primary
    };
@endphp

<div
    x-data="{
        isOpen: false,

        // Configuration
        persistent: @js($persistent),
        closeByClickingAway: @js($closeByClickingAway),
        closeByEscaping: @js($closeByEscaping),
        autofocus: @js($autofocus),

        // Event names
        closeEventName: @js($closeEventName),
        openEventName: @js($openEventName),

        // Modal ID
        modalId: @js($modalId),

        init() {
            // Set up global event listeners
            this.setupEventListeners();

            // Handle autofocus
            this.$watch('isOpen', (value) => {
                if (value && this.autofocus) {
                    this.$nextTick(() => {
                        const firstFocusable = this.$refs.modalContent?.querySelector('input, button, select, textarea');
                        if (firstFocusable) {
                            firstFocusable.focus();
                        }
                    });
                }

                document.body.style.overflow = value ? 'hidden' : '';

                // Dispatch custom events
                if (value) {
                    this.$dispatch('modal-opened', { id: this.modalId });
                } else {
                    this.$dispatch('modal-closed', { id: this.modalId });
                }
            });
        },

        setupEventListeners() {
            // Listen for specific modal events
            window.addEventListener(this.closeEventName, (e) => {
                if (e.detail?.id === this.modalId) {
                    this.close();
                }
            });

            window.addEventListener(this.openEventName, (e) => {
                if (e.detail?.id === this.modalId) {
                    this.open();
                }
            });

             window.addEventListener(this.forceCloseEventName, (e) => {
                if (e.detail?.id === this.modalId) {
                    this.forceClose();
                }
            });
        },

        open() {
            this.isOpen = true;
        },

        close() {
            if (this.persistent) return;
            // clean the global $modal store (mandantory even for isolated modals)
            $modal.close(this.modalId);
            this.isOpen = false;
        },

        forceClose() {
            // clean the global $modal store (mandantory even for isolated modals)
            $modal.forceClose(this.modalId);
            this.isOpen = false;
        },

        handleBackdropClick(event) {
            if (this.closeByClickingAway && !this.persistent && event.target === event.currentTarget) {
                // Ensure we're not selecting text
                if (document.activeElement?.selectionStart === undefined &&
                    document.activeElement?.selectionEnd === undefined) {
                    this.close();
                }
            }
        },

        handleEscapeKey(event) {
            if (event.key === 'Escape' && this.closeByEscaping && !this.persistent) {
                this.close();
            }
        },

        // Public API methods
        toggle() {
            this.isOpen ? this.close() : this.open();
        },

        destroy() {
            this.close();
            document.body.style.overflow = '';
        },
    }"
    x-id="['modal']"
    x-cloak
    x-bind:data-modal-open="isOpen"
    x-bind:data-modal-id="modalId"
    @if($ariaLabelledby)
        aria-labelledby="{{ $ariaLabelledby }}"
    @elseif($hasHeading)
        x-bind:aria-labelledby="$id('modal') + '-heading'"
    @endif

    {{ $attributes->class([
        'modal-component',
        $displayClasses,
        'hidden' => !$visible
    ]) }}
    x-on:keydown.window="handleEscapeKey($event)"
>
    {{-- Trigger Element --}}
    @if($trigger)
        <div
            x-on:click="open()"
            {{ $trigger->attributes->class("modal-trigger cursor-pointer") }}
        >
            {{ $trigger }}
        </div>
    @endif

    {{-- Modal Overlay and Content --}}
    <template x-teleport="body">
        <div
            x-show="isOpen"
            class="fixed inset-0  overflow-y-auto"
            aria-modal="true"
            role="dialog"
            style="display: none;z-index:9999"
        >
            {{-- Backdrop --}}
            <x-ui.modal.backdrop/>

            {{-- Modal Container --}}
            <div
                data-slot="modal-container"
                @if ($slideover)
                    data-slideover="true"
                @endif
                x-on:click="handleBackdropClick($event)"
                @class([
                    'relative flex min-h-full z-50 items-center justify-center',
                    'p-4' => (!$slideover && $width !== 'screen'),
                    'items-start pt-16' => $width !== 'screen' && ($position === 'top' && !$slideover),
                    'items-end pb-16' => ($position === 'bottom' && !$slideover),
                    ' overflow-x-hidden' => $slideover,
                ])
            >
                {{-- Modal Content --}}
                <div
                    x-ref="modalContent"
                    data-slot="modal-contents"
                    x-show="isOpen"
                    @if($animation === 'scale')
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95"
                        x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 scale-100"
                        x-transition:leave-end="opacity-0 scale-95"
                    @elseif($animation === 'slide')
                        x-transition:enter-start="translate-x-full rtl:-translate-x-full"
                        x-transition:enter-end="translate-x-0"
                        x-transition:leave-start="translate-x-0"
                        x-transition:leave-end="translate-x-full rtl:-translate-x-full"
                    @else
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                    @endif

                    @class([
                        // No max-height: the panel grows to whatever its content
                        // needs, and the overlay above (fixed inset-0 with
                        // overflow-y-auto) scrolls the whole dialog -- header,
                        // body and footer together -- once it is taller than the
                        // viewport.
                        'relative flex w-full flex-col bg-white shadow-xl ring-1 ring-neutral-900/5 bg-white dark:bg-card dark:ring-white/10',

                        $widthClass,
                        'rounded-box' => !$slideover && $width !== 'screen',
                        // Slideovers and full-screen modals are pinned to the
                        // viewport height, so they are the only variants whose
                        // body has to scroll inside the panel.
                        'h-[100vh] overflow-hidden' => $slideover || $width === 'screen',
                        'ml-auto ' => $slideover,
                    ])
                    style="display: none;"
                >
                    {{-- close by swap feature --}}
                    <x-ui.modal.grab-handle />
                    {{-- Header --}}
                    @if($hasHeading  || $closeButton)
                    <div
                        @class([
                            // shrink-0: when the body is tall enough to cap the
                            // panel, flex would otherwise compress the header too.
                            'modal-header flex shrink-0 items-start',
                            'p-3' => in_array($width, ['xs','sm','md','lg','xl','2xl','3xl','4xl','screen-md','screen-sm','screen-lg','screen-xl','screen-2xl']),
                            'p-4' => in_array($width, ['5xl','6xl','7xl','full']),
                            'p-6' => $width === 'screen',
                            'border-neutral-100 dark:border-neutral-800' => $hasSlot || $hasFooter,
                            'sticky top-0 z-40 bg-white dark:bg-card rounded-t-box' => $stickyHeader,
                            'border-b' => $hasHeading,
                            $alignmentClass,
                        ])
                    >

                        @if($hasIcon)
                            <div class="mr-4 flex-shrink-0">
                                <div
                                    @class([
                                        'rounded-full p-2',
                                        $iconColorClass,
                                    ])
                                >
                                    <x-ui.icon :name="$icon"/>
                                </div>
                            </div>
                        @endif
                        @if($hasHeading || $hasDescription )
                            <div class="flex-1 min-w-0">
                                @if($hasHeading)
                                    <h2
                                        x-bind:id="$id('modal') + '-heading'"
                                        class="text-lg font-semibold text-neutral-900 dark:text-white"
                                    >
                                        {{ $heading }}
                                    </h2>
                                @endif

                                @if($hasDescription)
                                    <p class="mt-1 text-sm text-neutral-600 dark:text-neutral-400">
                                        {{ $description }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        {{-- Close Button --}}
                        @if($closeButton)
                            <x-ui.button
                                x-on:click="$data.close();"
                                variant="none"
                                {{-- prevent the icon to be aware of our current icon --}}
                                :icon="null"
                                size="sm"
                                class="rounded-field bg-black/5 dark:bg-white/5"
                                icon-after="x-mark"
                            />
                        @endif
                    </div>
                    @endif

                    {{-- ACTUAL SLOT CONTENTS --}}
                    @if($hasSlot)
                        <div
                            @class([
                                // Deliberately not scrollable: the body renders at
                                // its full natural height and the overlay scrolls
                                // the entire modal instead.
                                'modal-content px-6 py-4 text-neutral-900 dark:text-neutral-50',
                                // ...except in the viewport-height variants, where
                                // the panel cannot grow, so the body must scroll.
                                'flex-auto min-h-0 overflow-y-auto' => $slideover || $width === 'screen',
                            ])
                        >
                            {{ $slot }}
                        </div>
                    @endif

                    {{-- FOOTER --}}
                    @if($hasFooter)
                        <div
                            @class([
                                // shrink-0 for the same reason as the header: the
                                // action buttons must stay at full height.
                                'modal-footer shrink-0 px-6 py-4',
                                'border-t border-neutral-200 dark:border-neutral-700',
                                'sticky bottom-0 z-10 bg-white dark:bg-neutral-900' => $stickyFooter,
                                'flex flex-wrap gap-3',
                            ])
                        >
                            {{ $footer }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
