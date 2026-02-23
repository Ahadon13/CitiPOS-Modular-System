<div class="relative z-10 w-full overflow-hidden">
    <!-- Top Border Pattern -->
    <div class="grid grid-cols-5">
        <div class="border-neutral-600/30 border !border-l-0 !border-r-0 !border-t-0 border-dashed py-6"
            style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
        </div>
        <div class="border-neutral-600/30 bg-background z-40 col-span-3 border !border-t-0 border-dashed py-10">
        </div>
        <div class="border-neutral-600/30 border !border-l-0 !border-r-0 !border-t-0 border-dashed py-6"
            style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
        </div>
    </div>

    <!-- Main Title Section -->
    <div class="grid grid-cols-5">
        <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex"
            style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
        </div>

        <!-- Title Content -->
        <div
            class=" border-neutral-600/30 before:border-neutral-600/30 before: relative col-span-5 border border-t-0 border-dashed py-10 before:absolute before:-left-16 before:-top-16 before:h-32 before:w-32 before:rounded-full before:border before:border-dashed before:content-[''] lg:col-span-3 dark:before:border-white/15">

            <h1 class="text-base-100 mx-auto px-4 text-3xl font-bold tracking-tight text-center sm:text-5xl md:max-w-lg md:px-0">
                Welcome Back. <br> Ready to Start Your Day?
            </h1>
        </div>

        <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex"
            style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
        </div>
    </div>

    <!-- Subtitle Section -->
    <div class="grid grid-cols-5">
        <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex"
            style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
        </div>

        <!-- Subtitle Content -->
        <div
            class=" border-neutral-600/30  relative col-span-5 border border-t-0 border-dashed py-10 before:absolute before:-right-10 before:-top-10 before:z-40 before:h-20 before:w-20 before:rotate-45 before:border before:border-dashed before:content-[''] lg:col-span-3 before:border-neutral-600/30 dark:before:border-white/15 before:bg-background">
            <x-ui.text class="opacity-50 mx-auto max-w-2xl px-4 text-center sm:text-lg md:px-0">
                Choose your workspace to continue managing sales and inventory with ease.
            </x-ui.text>
        </div>

        <div class=" border-neutral-600/30 hidden border !border-l-0 !border-r-0 border-t-0 border-dashed py-6 lg:flex"
            style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5">

        <div class="hidden md:block border-neutral-600/30 border !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
        </div>

        <div class="col-span-1 relative group cursor-pointer border-neutral-600/30 border border-t-0 border-dashed p-10 hover:bg-electric-blue/10 transition-colors">
            <a href="{{ route('pos.dashboard') }}" wire:navigate class="flex flex-col items-center text-center h-full w-full">
                <div class="absolute -bottom-[1px] -right-[1px] w-4 h-4 border-r border-b border-electric-blue"></div>
                <div class="absolute -top-[1px] -right-[1px] w-4 h-4 border-r border-t border-electric-blue"></div>
                <div class="absolute -bottom-[1px] -left-[1px] w-4 h-4 border-l border-b border-electric-blue"></div>
                <div class="absolute -top-[1px] -left-[1px] w-4 h-4 border-l border-t border-electric-blue"></div>

                <div class="mb-4 text-electric-blue dark:text-deep-space bg-gray-200 p-3 rounded-full group-hover:scale-110 group-hover:border-electric-blue group-hover:border transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>

                <h3 class="text-xl font-bold text-electric-blue dark:text-white">POS Terminal</h3>
                <span class="text-sm text-gray-500 mt-2">Process Sales</span>
            </a>
        </div>

        <div class="flex border-neutral-600/30 border border-t-0 border-l-0 border-r-0 border-dashed items-center justify-center">
            <div class="w-[1px] h-full border-r border-dashed border-neutral-600/30"></div>
        </div>

        <div class="col-span-1 relative group cursor-pointer border-neutral-600/30 border border-t-0 border-dashed p-10 hover:bg-electric-blue/10 transition-colors">
            <a href="{{ route('inventory.index') }}" wire:navigate class="flex flex-col items-center text-center h-full w-full">
                <div class="absolute -bottom-[1px] -right-[1px] w-4 h-4 border-r border-b border-electric-blue"></div>
                <div class="absolute -top-[1px] -right-[1px] w-4 h-4 border-r border-t border-electric-blue"></div>
                <div class="absolute -bottom-[1px] -left-[1px] w-4 h-4 border-l border-b border-electric-blue"></div>
                <div class="absolute -top-[1px] -left-[1px] w-4 h-4 border-l border-t border-electric-blue"></div>

                <div class="mb-4 text-electric-blue dark:text-deep-space bg-gray-200 p-3 rounded-full group-hover:scale-110 group-hover:border-electric-blue group-hover:border transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>

                <h3 class="text-xl font-bold text-electric-blue dark:text-white">Inventory</h3>
                <span class="text-sm text-gray-500 mt-2">Manage Stock</span>
            </a>
        </div>

        <div class="hidden md:block border-neutral-600/30 border !border-l-0 !border-r-0 border-t-0 border-dashed py-6" style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
        </div>
    </div>

    <!-- Bottom Border Pattern -->
    <div class="grid grid-cols-5">
        <div class=" border-neutral-600/30 border !border-b-0 !border-l-0 !border-r-0 border-t-0 border-dashed py-6"
            style="mask: linear-gradient(to right, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to right, transparent 0%, black 80%, black 100%);">
        </div>

        <div class=" lg:py-20 border-neutral-600/30 hidden border !border-b-0 border-r-0 border-t-0 border-dashed py-6 md:flex"
            style="mask: linear-gradient(to top, transparent 0%, black 80%, black 100%);">
        </div>

        <div class=" border-neutral-600/30 col-span-3 border !border-b-0 border-r-0 border-t-0 border-dashed md:col-span-1"
            style="mask: linear-gradient(to top, transparent 0%, black 80%, black 100%);">
        </div>

        <div class=" border-neutral-600/30 hidden border !border-b-0 border-t-0 border-dashed md:flex"
            style="mask: linear-gradient(to top, transparent 0%, black 80%, black 100%);">
        </div>

        <div class=" border-neutral-600/30 border !border-b-0 !border-l-0 !border-r-0 border-t-0 border-dashed py-6"
            style="mask: linear-gradient(to left, transparent 0%, black 80%, black 100%); -webkit-mask: linear-gradient(to left, transparent 0%, black 80%, black 100%);">
        </div>
    </div>
</div>
