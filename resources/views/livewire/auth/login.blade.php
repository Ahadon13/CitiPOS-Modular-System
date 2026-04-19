<div class="p-5 md:p-8 lg:p-10 shadow-2xl shadow-primary-900/5 dark:shadow-none border border-neutral-300/60 dark:border-white/10 bg-white/80 dark:bg-[#0a1331]/90 backdrop-blur-xl rounded-2xl md:rounded-4xl">

    <div class="mb-8 space-y-2">
        <h2 class="text-3xl font-bold text-[#0a1331] dark:text-white">Sign in</h2>
        <p class="text-neutral-500 dark:text-neutral-400">Enter your username and password to continue.</p>
    </div>

    <form wire:submit.prevent="login" class="space-y-6">

        <div class="space-y-5">
            <x-ui.field>
                <x-ui.label class="font-medium text-[#0a1331] dark:text-neutral-200">Username</x-ui.label>
                <x-ui.input
                    wire:model="form.username"
                    placeholder="Enter your username"
                    leftIcon="user"
                    class="rounded-xl bg-white dark:bg-[#060A23]"
                />
                <x-ui.error name="form.username" />
            </x-ui.field>

            <div class="space-y-2">
                <x-ui.field>
                    <x-ui.label class="font-medium text-[#0a1331] dark:text-neutral-200">Password</x-ui.label>
                    <x-ui.input
                        wire:model="form.password"
                        type="password"
                        placeholder="Enter your password"
                        leftIcon="lock-closed"
                        revealable
                        class="rounded-xl bg-white dark:bg-[#060A23]"
                    />
                    <x-ui.error name="form.password" />
                </x-ui.field>

                {{-- <div class="flex justify-end">
                    <a href="#" class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                        Forgot password?
                    </a>
                </div> --}}
            </div>
        </div>

        <div class="pt-2">
            <x-ui.button
                class="w-full flex items-center justify-center gap-2 rounded-xl py-3 text-white shadow-md shadow-primary-500/30 transition-all hover:shadow-primary-500/50 active:scale-[0.98]"
                color="primary"
                type="submit"
                iconAfter="arrow-right"
            >
                Sign In
            </x-ui.button>
        </div>
{{--
        <div class="pt-6 border-t border-neutral-100 dark:border-white/10 text-center">
            <p class="text-sm text-neutral-600 dark:text-neutral-400">
                Don't have an account?
                <a href="#" class="font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">Create one now</a>
            </p>
        </div> --}}
    </form>
</div>
