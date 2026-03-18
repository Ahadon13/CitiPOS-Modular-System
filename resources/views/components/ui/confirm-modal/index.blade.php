<x-ui.modal
    backdrop="dark"
    width="sm"
    position="center"
    heading="Confirmation"
    id="confirmation-modal"
    :close-button="false"
    persistent
    x-init="
        $watch('$store.confirm_modal.open', value => {
            if (value) {
                $data.open();
            } else {
                $data.forceClose();
            }
        });
    "
>

    <p x-text="$store.confirm_modal.message">...</p>

    <x-slot name="footer">
        <div class="flex w-full justify-end space-x-3">
            <x-ui.button
                x-on:click="
                    await $store.confirm_modal.onCancel();
                    $data.forceClose();
                "
                variant="outline"
            >
                Cancel
            </x-ui.button>
            <x-ui.button
                x-on:click="
                    await $store.confirm_modal.onConfirm();
                    $data.forceClose();
                "
                variant="primary"
            >
                Confirm
            </x-ui.button>
        </div>
    </x-slot>

</x-ui.modal>
