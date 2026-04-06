<div>
    <x-ui.modal id="upload-app" width="xl" heading="Manage Desktop App">

        {{-- Current File Status --}}
        <div class="mb-6 p-4 rounded-xl border border-neutral-200 dark:border-white/10 bg-neutral-50 dark:bg-white/5">
            <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-wider mb-2">Current Version Live</h4>

            @if($this->currentAppInfo['exists'])
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-500/20 text-green-600 dark:text-green-400 rounded-lg">
                    <x-ui.icon name="check-circle" class="size-6" />
                </div>
                <div>
                    <p class="font-bold text-sm text-neutral-900 dark:text-white">citipos-app.exe</p>
                    <p class="text-xs text-neutral-500">Size: {{ $this->currentAppInfo['size'] }} &bull; Uploaded: {{ $this->currentAppInfo['last_modified'] }}</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-100 dark:bg-red-500/20 text-red-600 dark:text-red-400 rounded-lg">
                    <x-ui.icon name="x-circle" class="size-6" />
                </div>
                <div>
                    <p class="font-bold text-sm text-neutral-900 dark:text-white">No App Uploaded</p>
                    <p class="text-xs text-neutral-500">Users cannot download the app yet.</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Upload Form --}}
        <form wire:submit="save" class="space-y-4">

            <x-ui.field required>
                <x-ui.label>Upload New Installer (.exe)</x-ui.label>

                <div class="mt-2 flex justify-center rounded-lg border border-dashed border-neutral-300 dark:border-neutral-700 px-6 py-8">
                    <div class="text-center">
                        <x-ui.icon name="computer-desktop" class="mx-auto size-10 text-neutral-400 mb-2" />
                        <div class="mt-4 flex text-sm leading-6 text-neutral-600 dark:text-neutral-400 justify-center">
                            <label for="file-upload" class="relative cursor-pointer rounded-md bg-white dark:bg-[#0a1331] font-semibold text-blue-600 focus-within:outline-none hover:text-blue-500">
                                <span>Select a file</span>
                                <input id="file-upload" wire:model="app_installer" type="file" accept=".exe" class="sr-only">
                            </label>
                        </div>
                        <p class="text-xs leading-5 text-neutral-500">Only .exe up to 500MB</p>

                        {{-- Show selected file name --}}
                        @if ($app_installer)
                        <p class="mt-2 text-sm font-bold text-green-600 dark:text-green-400">
                            Selected: {{ $app_installer->getClientOriginalName() }}
                        </p>
                        @endif
                    </div>
                </div>
                <x-ui.error name="app_installer" />
            </x-ui.field>

            <div class="flex justify-end pt-4 gap-3 border-t border-black/10 dark:border-white/10">
                <x-ui.button variant="outline" color="neutral" type="button" x-on:click="$dispatch('close-modal', { id: 'upload-app' })">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" wire:loading.attr="disabled" wire:target="save, app_installer" icon="arrow-up-tray">
                    <span wire:loading.remove wire:target="save">Upload & Replace</span>
                    <span wire:loading wire:target="save">Uploading... Please wait.</span>
                </x-ui.button>
            </div>
        </form>

    </x-ui.modal>
</div>
