{{--
    ╔═══════════════════════════════════════════════════════════════════════════════╗
    ║                    SIDEBAR-MAIN LAYOUT VARIANT                                ║
    ║                                                                               ║
    ║  A responsive layout system with three breakpoint behaviors:                  ║
    ║                                                                               ║
    ║  MOBILE (< 768px):                                                            ║
    ║     • Overlay sidebar (slides in from left)                                   ║
    ║     • Backdrop overlay when sidebar is open                                   ║
    ║     • Full-width main content                                                 ║
    ║     • Uses sidebarOpen state (not collapsed)                                  ║
    ║                                                                               ║
    ║    TABLET (768px - 1024px):                                                   ║
    ║     • Always collapsed sidebar (visible, narrow)                              ║
    ║     • Sidebar and main content side-by-side                                   ║
    ║     • Touch-friendly: tap sidebar to toggle                                   ║
    ║                                                                               ║
    ║    DESKTOP (>=1024px):                                                        ║
    ║     • Expandable/collapsable sidebar                                          ║
    ║     • State persists via localStorage                                         ║
    ║     • Smooth width transitions                                                ║
    ║                                                                               ║
    ║  Key Features:                                                                ║
    ║  • CSS Grid with CSS custom properties for flexible sizing                    ║
    ║  • Alpine.js for state management                                             ║
    ║  • $persist for localStorage integration                                      ║
    ║  • Data attribute API for clean CSS targeting                                 ║
    ║  • Pre-hydration flicker prevention                                           ║
    ╚═══════════════════════════════════════════════════════════════════════════════╝
--}}

@props([
    'collapsable' => true
])

@php
    $classes = [
        // ═══════════════════════════════════════════════════════════════════════
        // CSS CUSTOM PROPERTIES
        // ═══════════════════════════════════════════════════════════════════════
        '[--sidebar-width:16rem]',                      // Default: 256px expanded
        'data-[collapsed]:[--sidebar-width:4rem]',      // Collapsed: 64px (icon only)
        
        '[--header-height:4rem]',                       // Header: 64px height
        // Optional: Change header height when sidebar collapses
        'data-[collapsed]:[--header-height:4rem]',

        // ═══════════════════════════════════════════════════════════════════════
        // BASE GRID CONFIGURATION
        // ═══════════════════════════════════════════════════════════════════════
        'grid',         
        'items-start',                               
        'h-screen overflow-hidden',                     
        'min-h-screen text-slate-950 dark:text-slate-50', 

        // ═══════════════════════════════════════════════════════════════════════
        // 📱 MOBILE LAYOUT (< 768px)
        // Overlay sidebar that slides in from the left
        // ═══════════════════════════════════════════════════════════════════════
        'grid-cols-1',                                  // Single column (main only)
        'grid-rows-[1fr]',                             // One row taking full height
        "[grid-template-areas:'main']",                // Main content fills all space
        
        // Mobile: Sidebar positioning (overlay mode)
        '[&_[data-slot=sidebar]]:fixed',               
        '[&_[data-slot=sidebar]]:inset-y-0',           
        '[&_[data-slot=sidebar]]:left-0',            
        '[&_[data-slot=sidebar]]:z-50',                
        '[&_[data-slot=sidebar]]:w-[var(--sidebar-width)]', 
        
        // Mobile: Sidebar animation
        '[&_[data-slot=sidebar]]:transition-transform', 
        '[&_[data-slot=sidebar]]:duration-300',         
        '[&_[data-slot=sidebar]]:ease-in-out',          
        '[&_[data-slot=sidebar]]:-translate-x-full',    // Hidden by default (off-screen left)
        'data-[sidebar-open]:[&_[data-slot=sidebar]]:translate-x-0', // Visible when open
        
        // ═══════════════════════════════════════════════════════════════════════
        // 📱 TABLET LAYOUT (768px - 1024px)
        // Always collapsed sidebar, visible alongside main content
        // ═══════════════════════════════════════════════════════════════════════
        'md:data-[collapsed]:grid-cols-[var(--sidebar-width)_1fr]', // Sidebar + main columns
        'md:grid-cols-[var(--sidebar-width)_1fr]',                   // Same for non-collapsed
        "md:[grid-template-areas:'sidebar_main']",                   // Named grid areas
        
        // Tablet: Sidebar positioning (in-flow, not overlay)
        'md:[&_[data-slot=sidebar]]:relative',          // Relative positioning (not fixed)
        'md:[&_[data-slot=sidebar]]:translate-x-0',     // Always visible (no slide animation)
        'md:[&_[data-slot=sidebar]]:z-auto',            // Normal z-index stacking
        'md:data-[collapsed]:[&_[data-slot=sidebar]]:w-[var(--sidebar-width)]', // Collapsed width
        'md:[&_[data-slot=sidebar]]:overflow-visible',  // Show content that overflows
        
        // ═══════════════════════════════════════════════════════════════════════
        // 🖥️ DESKTOP LAYOUT (>= 1024px)
        // Full expandable/collapsable functionality with state persistence
        // ═══════════════════════════════════════════════════════════════════════
        'lg:grid-rows-1',                               // Single row
        'lg:grid-cols-[auto_1fr]',                      // Sidebar auto-sized, main takes rest
        "lg:[grid-template-areas:'sidebar_main']",      
        
        // Desktop: Reset tablet width constraints
        'lg:[&_[data-slot=sidebar]]:w-auto',            
        
        // Desktop: Collapsed state
        'lg:grid-cols-[var(--sidebar-width)_1fr]',                      
        'data-[collapsed]:lg:grid-cols-[var(--sidebar-width)_1fr]',   
        'data-[collapsed]:lg:[grid-template-areas:"sidebar_main"]',     
        'data-[collapsed]:[&_[data-slot=sidebar]]:lg:w-[var(--sidebar-width)]', 
        'data-[collapsed]:[&_[data-slot=sidebar]]:lg:overflow-visible', 
        
    ];
@endphp

{{--
    ┌─────────────────────────────────────────────────────────────────────────────┐
    │ LAYOUT CONTAINER                                                            │
    │                                                                             │
    │ Alpine.js manages all interactive state and behavior                        │
    └─────────────────────────────────────────────────────────────────────────────┘
--}}
<div 
    {{ $attributes->class($classes) }}
        
        {{--
            ═══════════════════════════════════════════════════════════════════════
            ALPINE.JS STATE MANAGEMENT
            ═══════════════════════════════════════════════════════════════════════
            
            State Properties:
            • collapsedSidebar: Persisted in localStorage (_x_collapsedSidebar)
            • sidebarOpen: Mobile-only, controls overlay visibility
            • isMobile: True when viewport < 768px
            • isTablet: True when viewport 768px-1024px
        --}}

        x-data="{
            collapsedSidebar: $persist(false), // Syncs with localStorage automatically
            sidebarOpen: false,                // Mobile overlay state (not persisted)
            isMobile: false,                   
            isTablet: false,                   
            
            {{--
                ───────────────────────────────────────────────────────────────────
                TOGGLE FUNCTION
                
                Behavior changes based on viewport:
                • Mobile: Toggles overlay (sidebarOpen)
                • Tablet/Desktop: Toggles collapse (collapsedSidebar)
                ───────────────────────────────────────────────────────────────────
            --}}

            toggle() {
                if (this.isMobile) {
                    // Mobile: toggle sidebar overlay
                    this.sidebarOpen = !this.sidebarOpen;
                }else {
                    // Desktop: toggle collapse
                    this.collapsedSidebar = !this.collapsedSidebar;
                }
            },
            
            {{--
                ───────────────────────────────────────────────────────────────────
                CLOSE SIDEBAR (Mobile Only)
                
                Used when clicking backdrop or navigation links
                ───────────────────────────────────────────────────────────────────
            --}}
            closeSidebar() {
                if (this.isMobile) {
                    this.sidebarOpen = false;
                }
            },
            
            
            updateBreakpoints() {
                this.isMobile = window.matchMedia('(max-width: 767px)').matches;
                this.isTablet = window.matchMedia('(min-width: 768px) and (max-width: 1023px)').matches;
                
                // Close mobile sidebar when switching breakpoints
                if (!this.isMobile) {
                    this.sidebarOpen = false;
                }
            },
            
            {{--
                ───────────────────────────────────────────────────────────────────
                INITIALIZATION
                
                Runs when Alpine mounts this component:
                1. Force collapse on tablet if page loads on tablet
                2. Watch mobile state to prevent collapsed attribute on mobile
                3. Set initial breakpoint flags
                4. Listen for window resize events
                ───────────────────────────────────────────────────────────────────
            --}}
            init() {
                // Tablet: start collapsed, but ONLY when the user has never
                // chosen for themselves.
                //
                // init() runs again on every wire:navigate page swap, so an
                // unconditional force here would re-collapse the sidebar each
                // time a nav link was clicked -- undoing the user's expand.
                // $persist writes the key as soon as it is touched, so a null
                // key means 'no preference expressed yet'.
                if (this.$root.dataset.inTablet === 'true'
                    && localStorage.getItem('_x_collapsedSidebar') === null) {
                    this.collapsedSidebar = true
                }

                // Mobile: Remove collapse state (uses sidebarOpen instead)
                // This prevents CSS conflicts between overlay and collapse states
                this.$watch('isMobile',(val)=>{
                    if(val){
                        this.collapsedSidebar = false
                    } 
                });

                // Set initial state
                this.updateBreakpoints();
                        
                // Listen for breakpoint changes (window resize)
                const mobileQuery = window.matchMedia('(max-width: 767px)');
                const tabletQuery = window.matchMedia('(min-width: 768px) and (max-width: 1023px)');
                
                mobileQuery.addEventListener('change', () => this.updateBreakpoints());
                tabletQuery.addEventListener('change', () => this.updateBreakpoints());
            }
        }"
        
        {{--
            ═══════════════════════════════════════════════════════════════════════
            DATA ATTRIBUTES API
            
            These attributes are bound to Alpine state and used for CSS targeting.
            This creates a clean separation between state (Alpine) and style (CSS).
            
            ═══════════════════════════════════════════════════════════════════════
        --}}
        x-bind:data-in-mobile="isMobile"
        x-bind:data-in-tablet="isTablet" 
        @if ($collapsable)
            x-bind:data-collapsed="collapsedSidebar"
        @endif
        x-bind:data-sidebar-open="sidebarOpen"
    data-slot="layout"
>
    {{--
        ═══════════════════════════════════════════════════════════════════════════
        LAYOUT CONTENT
        
        Slot accepts sidebar and main content components.
        Grid areas are assigned via data-slot attributes:
        • [data-slot="sidebar"] → grid-area: sidebar
        • [data-slot="main"] → grid-area: main
        ═══════════════════════════════════════════════════════════════════════════
    --}}
    {{ $slot }}
    
    {{--
        ═══════════════════════════════════════════════════════════════════════════
        MOBILE BACKDROP OVERLAY
        
        Appears behind the sidebar when open on mobile.
        Clicking it closes the sidebar.
        
        Features:
        • Only visible on mobile (md:hidden)
        • Smooth fade in/out transitions
        • Semi-transparent black overlay
        • High z-index (40) but below sidebar (50)
        ═══════════════════════════════════════════════════════════════════════════
    --}}
    <div 
        x-show="isMobile && sidebarOpen"
        style="display: none;" 
        x-transition:enter="transition-opacity duration-300"
        x-transition:leave="transition-opacity duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-on:click="closeSidebar()"
        class="fixed inset-0 bg-black/50 z-40 md:hidden"
    ></div>
</div>