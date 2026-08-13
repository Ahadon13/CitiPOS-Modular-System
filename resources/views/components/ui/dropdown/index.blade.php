@props([
    'position' => 'bottom-center',
    'teleport' => 'body',
    // Portalled by default.
    //
    // Dropdowns nearly always sit inside a card or a table's horizontal scroll
    // container, and any ancestor with `overflow: hidden|auto` clips an
    // absolutely-positioned panel. Cards need that overflow to keep square
    // table corners inside their rounded ones, so the panel is the thing that
    // has to move: x-teleport renders it under <body>, where no ancestor can
    // clip it, while x-anchor keeps it pinned to its button (and follows it on
    // scroll and resize).
    //
    // Pass :portal="false" for a dropdown that must stay in the component's
    // own DOM position.
    'portal' => true,
    'trap' => false,
    'offset' => 6,
    'checkbox' => false,
    'radio' => false,
    'resetFocus' => false
])

@php
    $isDefaultDropdownVariant = $checkbox || $radio;
    $classes = [
        'isolate',
        // A portalled panel is a direct child of <body>, so it must clear the
        // modal layer (z-index 9999) to stay visible when opened from inside a
        // dialog. A non-portalled panel only competes within its own local
        // stacking context, where z-50 is plenty.
        'z-[10000]' => $portal,
        'z-50' => ! $portal,
        'grid grid-cols-[auto_1fr_auto]' => !$isDefaultDropdownVariant ,
        'grid grid-cols-[auto_auto_1fr_auto]' => $isDefaultDropdownVariant,
        '[:where(&)]:max-w-96 [:where(&)]:min-w-40 text-start',
        'bg-white dark:bg-card border border-black/10 dark:border-white/10',
        '[--dropdown-radius:var(--radius-box)] [--dropdown-padding:--spacing(.75)]
         rounded-(--dropdown-radius) p-(--dropdown-padding) space-y-1',
    ];
@endphp

<div {{ $attributes }}>
    <div
        x-data="{
            open: false,
            resetFocus:@js($resetFocus),
            toggle() {
                if (this.open) {
                    return this.close()
                }

                $focus.getFirst().focus()
                this.open = true
            },
            isOpen(){
                return this.open
            },
            close(focusAfter) {
                if (! this.open) return

                this.open = false;

                focusAfter && this.resetFocus && requestAnimationFrame(() => $focus.getFirst().focus());
            },

            handleFocusInOut(event) {
                const panel = this.$refs.panel
                const button = this.$refs.button
                const target = event.target

                // If the panel or the button contains the focused element, do nothing
                if (panel.contains(target) || button.contains(target)) return;

                // If the focus is outside both the panel and button, check DOM order
                const lastFocusedElement = document.activeElement

                if (this.shouldCloseDropdown(button,panel,lastFocusedElement)) this.close(button);
            },
            shouldCloseDropdown(button, panel, lastFocusedElement) {
                return (!button.contains(lastFocusedElement) && !panel.contains(lastFocusedElement)) &&
                    (lastFocusedElement && (button.compareDocumentPosition(lastFocusedElement) & Node.DOCUMENT_POSITION_FOLLOWING));
            },

        }"
        wire:ignore
        x-on:keydown.escape.prevent.stop="close($refs.button)"
        x-on:focusin.window="handleFocusInOut($event)"
        x-id="['dropdown-button']"
        wire:key="dropdown-{{ uniqid() }}"
        class="relative"
    >
        <!-- Button -->
        <div
            x-ref="button"
            {{ $button->attributes }}
            x-on:keydown.tab.prevent.stop="$focus.focus($focus.within($refs.panel).getFirst())"
            x-on:keydown.down.prevent.stop="$focus.focus($focus.within($refs.panel).getFirst())"
            x-on:keydown.space.stop.prevent="toggle()"
            x-on:keydown.enter.stop.prevent="toggle()"
            x-on:click="toggle()"
            x-bind:aria-expanded="open"
            x-bind:data-open="open"
            x-bind:aria-controls="$id('dropdown-button')"
        >
            {{ $button }}
        </div>

        @if($portal)
            <template x-teleport="{{ $teleport }}" wire:key="dropdown-portal-{{ uniqid() }}">
        @endif

        <div
            x-show="open"

            @if ($trap)
                x-trap="open"
            @endif

            x-ref="panel"
            x-anchor.{{ $position }}.offset.{{ $offset }}="$refs.button;"
            x-on:keydown.down.prevent.stop="$focus.next()"
            x-on:keydown.up.prevent.stop="$focus.prev()"
            x-on:keydown.home.prevent.stop="$focus.first()"
            x-on:keydown.page-up.prevent.stop="$focus.first()"
            x-on:keydown.end.prevent.stop="$focus.last()"
            x-on:keydown.page-down.prevent.stop="$focus.last()"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            x-on:click.away="close($refs.button)"
            x-bind:id="$id('dropdown-button')"
            style="display: none;"
            @if($radio)
                role="radiogroup"
            @else
                role="menu"
            @endif
            {{ $menu->attributes->class(Arr::toCssClasses($classes)) }}
        >
            {{ $menu }}
        </div>

        @if($portal)
            </template>
        @endif
    </div>
</div>
