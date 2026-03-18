<div class="flex items-center gap-2">
    <a data-slot="button"
       class="dark:hover:bg-white/6 text-primary-brand bg-base-200/6 relative inline-flex h-[32px] w-[32px] items-center justify-center gap-2 whitespace-nowrap rounded-[3px] text-sm font-medium hover:bg-neutral-200"
       href="/">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect x="10"
                y="75"
                width="80"
                height="15"
                fill="#2580ff"
                rx="2" />
            <rect x="10"
                y="55"
                width="60"
                height="15"
                fill="#2580ff"
                rx="2" />

            <rect x="10"
                y="25"
                width="30"
                height="20"
                fill="#2580ff"
                rx="2" />
            <path d="M45 25 H85 V55 H70 V35 H45 Z"
                fill="#2580ff"
                fill-rule="evenodd" />
        </svg>
    </a>
    <a class="inline-flex items-center"
       href="{{ route('home') }}"
       wire:navigate>
        <h1 class='text-primary-brand text-lg font-bold leading-8'>CitiPOS</h1>
    </a>
</div>
