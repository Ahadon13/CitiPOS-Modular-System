{{-- We set the overall background to Deep Space here --}}
<div class="w-full bg-[#050609] min-h-screen font-sans antialiased text-neutral-200">

    {{-- ========================================== --}}
    {{-- HEADER (New)                --}}
    {{-- ========================================== --}}
    <header class="sticky top-0 z-50 bg-[#050609]/90 backdrop-blur-sm border-b border-dashed border-neutral-600/30">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center justify-between">
            {{-- Logo with Electric Blue accent --}}
            <div class="flex items-center gap-2">
                <x-ui.brand href="/" logoClass="size-12!" logo="{{ asset('favicon.svg') }}" />
                <span class="text-2xl font-black text-white">Citi<span class="text-blue-400">POS</span></span>
            </div>

            <div class="flex items-center gap-3">
                {{-- Desktop App Download Button --}}
                <x-ui.button href="https://github.com/Ahadon13/POSAndInventory/releases/download/v1.0.0/citipos-app.exe" download variant="outline" class="hidden sm:flex border-neutral-600 text-neutral-300 hover:text-white hover:bg-white/5" icon-after="arrow-down-tray">
                    Get Desktop App
                </x-ui.button>

                {{-- Electric Blue CTA button --}}
                @guest
                <x-ui.button href="{{ route('login') }}" class="bg-blue-600! hover:bg-blue-500! text-white!" icon-after="arrow-right">
                    Sign In
                </x-ui.button>
                @endguest
                @auth
                <x-ui.button href="{{ route('home') }}" class="bg-blue-600! hover:bg-blue-500! text-white!" icon-after="arrow-right">
                    Dashboard
                </x-ui.button>
                @endauth
            </div>
        </div>
    </header>


    {{-- ========================================== --}}
    {{-- HERO SECTION (Updated)        --}}
    {{-- ========================================== --}}
    {{-- This section keeps your structure, updated for acquire rather than dashboard --}}
    <div class="relative z-10 w-full overflow-hidden">
        <div class="grid grid-cols-5">
            <div class="border-neutral-600/30 border !border-l-0 !border-r-0 !border-t-0 border-dashed py-6" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
            </div>
            {{-- Unified color from Deep Space --}}
            <div class="border-neutral-600/30 bg-[#050609] z-40 col-span-3 border !border-t-0 border-dashed py-10 lg:py-20">
            </div>
            <div class="border-neutral-600/30 border !border-l-0 !border-r-0 !border-t-0 border-dashed py-6" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
            </div>
        </div>

        <div class="grid grid-cols-5">
            <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
            </div>

            <div class=" border-neutral-600/30 before:border-neutral-600/30 before: relative col-span-5 border border-t-0 border-dashed py-10 before:absolute before:-left-16 before:-top-16 before:h-32 before:w-32 before:rounded-full before:border before:border-dashed before:content-[''] lg:col-span-3 dark:before:border-neutral-600/30">

                {{-- H1 updated: White text with Electric Blue highlights --}}
                <h1 class="text-white mx-auto px-4 text-4xl sm:text-6xl font-black tracking-tight text-center md:max-w-xl md:px-0">
                    Unified POS & Inventory.<br>
                    <span class="text-blue-400">Total Clarity. Perfect Control.</span>
                </h1>
            </div>

            <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
            </div>
        </div>

        <div class="grid grid-cols-5">
            <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
            </div>

            <div class=" border-neutral-600/30 relative col-span-5 border border-t-0 border-dashed py-10 before:absolute before:-right-10 before:-top-10 before:z-40 before:h-20 before:w-20 before:rotate-45 before:border before:border-dashed before:content-[''] lg:col-span-3 before:border-neutral-600/30 dark:before:border-neutral-600/30 before:bg-[#050609]">

                {{-- text color to high-contrast white --}}
                <x-ui.text class="text-white opacity-80 mx-auto max-w-2xl px-4 text-center sm:text-lg md:px-0 leading-relaxed">
                    CitiPOS brings clarity and control to your daily pharmacy operations. It’s innovative, robust, and built to scale your business into the future.
                </x-ui.text>
            </div>

            <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
            </div>
        </div>

        <div class="grid grid-cols-5">
            <div class=" border-neutral-600/30 border !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
            </div>

            <div class=" border-neutral-600/30 hidden border border-r-0 border-t-0 border-dashed py-6 lg:flex">
            </div>

            <div class="border-neutral-600/30 before:border-neutral-600/30 flex flex-col sm:flex-row items-center justify-center gap-4 relative col-span-3 border border-t-0 border-dashed py-6 before:absolute before:-bottom-5 before:-right-6 before:z-10 before:h-10 before:w-12 before:rounded-none before:border before:border-dashed before:content-[''] lg:col-span-1 dark:before:border-neutral-600/30 before:bg-[#050609]">

                {{-- Download Desktop App CTA --}}
                <x-ui.button href="https://github.com/Ahadon13/POSAndInventory/releases/download/v1.0.0/citipos-app.exe" download variant="outline" iconAfter="arrow-down-tray" class="w-full sm:w-auto border-neutral-600! text-white! hover:bg-white/5! px-8! py-3!">
                    Download App
                </x-ui.button>

                {{-- Electric Blue CTA --}}
                <x-ui.button href="{{ route('home') }}" iconAfter="arrow-right" class="w-full sm:w-auto bg-blue-600! hover:bg-blue-500! text-white! px-8! py-3!">
                    Access System
                </x-ui.button>
            </div>

            <div class=" border-neutral-600/30 hidden border border-l-0 border-t-0 border-dashed py-6 lg:flex">
            </div>

            <div class=" border-neutral-600/30 border !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
            </div>
        </div>

        <div class="grid grid-cols-5">
            <div class=" border-neutral-600/30 border !border-b-0 !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
            </div>

            <div class=" lg:py-15 border-neutral-600/30 hidden border !border-b-0 border-r-0 border-t-0 border-dashed py-6 md:flex" style="mask: linear-gradient(to top, transparent 0%, black 80%, black 100%);">
            </div>

            {{-- Continue the grid line down --}}
            <div class=" border-neutral-600/30 col-span-3 border !border-b-0 border-r-0 border-t-0 border-dashed md:col-span-1" style="mask: linear-gradient(to top, transparent 0%, black 80%, black 100%);">
            </div>

            <div class=" border-neutral-600/30 hidden border !border-b-0 border-t-0 border-dashed md:flex" style="mask: linear-gradient(to top, transparent 0%, black 80%, black 100%);">
            </div>

            <div class=" border-neutral-600/30 border !border-b-0 !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
            </div>
        </div>
    </div>


    {{-- ========================================== --}}
    {{-- FEATURES SECTION (New)          --}}
    {{-- ========================================== --}}
    <section id="features" class="relative z-10 w-full overflow-hidden">
        {{-- Section Header grid row --}}
        <div class="grid grid-cols-5">
            <div class="border-neutral-600/30 border border-dashed border-r-0 border-b-0"></div>
            <div class="border-neutral-600/30 col-span-3 border border-dashed border-b-0 py-16 px-6">
                <span class="text-blue-400 text-sm font-bold uppercase tracking-widest block text-center mb-2">Platform</span>
                <h2 class="text-white text-4xl sm:text-5xl font-black text-center tracking-tight">Everything is Connected.</h2>
            </div>
            <div class="border-neutral-600/30 border border-dashed border-l-0 border-b-0"></div>
        </div>

        {{-- Features Grid Row --}}
        <div class="grid grid-cols-5">
            {{-- Left pattern border --}}
            <div class="border-neutral-600/30 hidden lg:block border !border-b-0 !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
            </div>

            {{-- Feature Content (Spans 5 on mobile, 3 on desktop) --}}
            <div class="col-span-5 lg:col-span-3 border-neutral-600/30 border border-dashed p-6 sm:p-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    {{-- Feature 1: POS --}}
                    <div class="p-6 bg-neutral-900 border border-neutral-600/30 rounded-2xl hover:border-blue-500/50 transition-all group hover:shadow-2xl hover:shadow-blue-900/10">
                        <div class="p-3 bg-[#050609] rounded-xl inline-block border border-dashed border-neutral-600/30 mb-5 group-hover:scale-110 transition-transform">
                            <x-ui.icon name="shopping-cart" class="size-7 text-blue-400" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Fast Point of Sale</h3>
                        <p class="text-sm text-neutral-400 leading-relaxed">Streamlined sales ledger optimized for speed. Process sales instantly and print receipts on demand.</p>
                    </div>

                    {{-- Feature 2: Inventory --}}
                    <div class="p-6 bg-neutral-900 border border-neutral-600/30 rounded-2xl hover:border-blue-500/50 transition-all group hover:shadow-2xl hover:shadow-blue-900/10">
                        <div class="p-3 bg-[#050609] rounded-xl inline-block border border-dashed border-neutral-600/30 mb-5 group-hover:scale-110 transition-transform">
                            <x-ui.icon name="cube" class="size-7 text-blue-400" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Smart Inventory</h3>
                        <p class="text-sm text-neutral-400 leading-relaxed">Complete stock ledger tracking batches, expirations, and costs in real-time. Know your shelve value.</p>
                    </div>

                    {{-- Feature 3: Analytics --}}
                    <div class="p-6 bg-neutral-900 border border-neutral-600/30 rounded-2xl hover:border-blue-500/50 transition-all group hover:shadow-2xl hover:shadow-blue-900/10">
                        <div class="p-3 bg-[#050609] rounded-xl inline-block border border-dashed border-neutral-600/30 mb-5 group-hover:scale-110 transition-transform">
                            <x-ui.icon name="chart-pie" class="size-7 text-blue-400" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Business Insights</h3>
                        <p class="text-sm text-neutral-400 leading-relaxed">Integrated reports combining Sales, COGS, and Expenses to deliver accurate Net Profit calculations.</p>
                    </div>

                    {{-- Feature 4: Staff --}}
                    <div class="p-6 bg-neutral-900 border border-neutral-600/30 rounded-2xl hover:border-blue-500/50 transition-all group hover:shadow-2xl hover:shadow-blue-900/10">
                        <div class="p-3 bg-[#050609] rounded-xl inline-block border border-dashed border-neutral-600/30 mb-5 group-hover:scale-110 transition-transform">
                            <x-ui.icon name="user-group" class="size-7 text-blue-400" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Staff Performance</h3>
                        <p class="text-sm text-neutral-400 leading-relaxed">Monitor staff activity by tracking transactions processed and revenue generated per user.</p>
                    </div>
                </div>
            </div>

            {{-- Right pattern border --}}
            <div class="border-neutral-600/30 hidden lg:block border !border-b-0 !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
            </div>
        </div>
    </section>


    {{-- ========================================== --}}
    {{-- FOOTER CTA (New)            --}}
    {{-- ========================================== --}}
    <footer class="relative z-10 w-full overflow-hidden mt-16 pb-12 border-t border-dashed border-neutral-600/30 bg-neutral-900">
        <div class="grid grid-cols-5">
            {{-- pattern --}}
            <div class="border-neutral-600/30 hidden md:block border !border-l-0 !border-r-0 !border-t-0 border-dashed py-6" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
            </div>

            {{-- Content --}}
            <div class="border-neutral-600/30 col-span-5 md:col-span-3 border-l border-r border-dashed py-16 px-6 text-center">
                <span class="text-2xl font-black text-white mb-4 block">Citi<span class="text-blue-400">POS</span></span>
                <p class="text-neutral-400 text-sm max-w-lg mx-auto leading-relaxed mb-8">CitiPOS is the unified solution that scales with your ambition. Bring advanced efficiency to your operations starting today.</p>
                <x-ui.button href="#" class="bg-white! text-neutral-900! hover:bg-neutral-200! px-8!">
                    Access the System
                </x-ui.button>
            </div>

            {{-- pattern --}}
            <div class="border-neutral-600/30 hidden md:block border !border-l-0 !border-r-0 !border-t-0 border-dashed py-6" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
            </div>
        </div>

        <div class="max-w-7xl mx-auto text-center px-4 pt-8 text-xs text-neutral-600">
            &copy; {{ date('Y') }} CitiPOS Pharmacy Systems. All rights reserved. Deep Space / Electric Blue Edition.
        </div>
    </footer>
</div>
