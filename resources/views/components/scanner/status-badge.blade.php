{{--
    Scanner connection status, shared by the POS and inventory pages.

    Must sit inside an x-data="barcodeScanner(...)" element. What it shows
    depends on the scanner model's connection mode:
      - USB COM: Connected / Unplugged / Pair scanner / In use elsewhere ...
      - Keyboard: "Scanner on" / "Connection unknown" -- browsers cannot see
        keyboards, so the page does not pretend to know.
    When the status needs the user (pair, retry), the badge is the button.

    Two short lines in an input-height pill, so it sits in any toolbar row
    without pushing other controls off the edge. The full explanation is in
    the tooltip.
--}}
<button
    type="button"
    x-on:click="statusAction()"
    :aria-disabled="!statusInfo().action"
    :title="statusTitle()"
    :class="[statusClass(), statusInfo().action ? 'cursor-pointer hover:brightness-95 dark:hover:brightness-110 focus-visible:ring-2 focus-visible:ring-electric-blue/40' : 'cursor-default']"
    {{ $attributes->merge(['class' => 'group inline-flex h-10 max-w-[15rem] items-center gap-2.5 rounded-lg border pl-2.5 pr-3 text-left transition focus:outline-none']) }}
>
    {{-- Status dot; pulses while the scanner is reachable. --}}
    <span class="relative flex size-2.5 shrink-0">
        <span x-show="statusInfo().live" x-cloak class="absolute inline-flex size-full animate-ping rounded-full opacity-60" :class="statusDotClass()"></span>
        <span class="relative inline-flex size-2.5 rounded-full" :class="statusDotClass()"></span>
    </span>

    <x-ui.icon name="qr-code" class="size-4 shrink-0 opacity-70" />

    <span class="flex min-w-0 flex-col leading-tight">
        <span class="truncate text-xs font-semibold" x-text="statusInfo().label"></span>
        <span
            x-show="statusDetail()"
            x-cloak
            class="truncate text-[11px] opacity-75"
            :class="lastScan && (status === 'keyboard' || status === 'connected') ? 'font-mono' : ''"
            x-text="statusDetail()"
        ></span>
    </span>
</button>
