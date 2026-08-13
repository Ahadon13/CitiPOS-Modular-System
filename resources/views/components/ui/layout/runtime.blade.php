{{--
    This runtime logic solves the **Alpine.js flicker problem** that happens before state hydration.
    It ensures layout attributes like `data-collapsed` and viewport flags are set **before Alpine initializes**,
    so the UI renders in the correct state instantly without visual jumps or misaligned transitions.

    Every attribute is written in BOTH directions. This script re-runs on every
    wire:navigate page swap, so an attribute that is only ever added would stick
    permanently once set -- which is how an expanded sidebar could silently
    re-collapse on the next navigation.
--}}

<script>
    (function () {
        try {
            const layout = document.querySelector('[data-slot="layout"]')
            if (!layout) return

        //  BLADE DIRECTIVE
        @if($collapsable)
            // Restore collapsed state from localStorage. toggleAttribute (not
            // setAttribute) so an expanded sidebar actively clears the flag.
            layout.toggleAttribute(
                'data-collapsed',
                localStorage.getItem('_x_collapsedSidebar') === 'true'
            )
        @endif
        // END BLADE DIRECTIVE

        // Breakpoints
        const mqMobile = window.matchMedia('(max-width: 767px)')
        const mqTablet = window.matchMedia('(min-width: 768px) and (max-width: 1023px)')

        layout.toggleAttribute('data-in-mobile', mqMobile.matches)
        layout.toggleAttribute('data-in-tablet', mqTablet.matches)
    } catch (e) {
        console.warn('Init layout failed:', e)
    }
})()
</script>
