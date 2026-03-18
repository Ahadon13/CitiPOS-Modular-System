import {
    Livewire,
    Alpine,
} from "../../../vendor/livewire/livewire/dist/livewire.esm";

document.addEventListener("alpine:init", () => {
    Alpine.store("confirm_modal", {
        open: false,
        title: "",
        message: "",
        onConfirm: () => {},
        onCancel: () => {},
    });
});

window.confirmModal = (title = "confirm", message) => {
    return new Promise((resolve, _) => {
        const store = Alpine.store("confirm_modal");
        store.title = title;
        store.message = message;

        // 1. Create the keyboard event listener
        const handleKeydown = (e) => {
            if (store.open) {
                if (e.key === "Enter") {
                    e.preventDefault();
                    store.onConfirm();
                } else if (e.key === "Backspace") {
                    e.preventDefault();
                    store.onCancel();
                }
            }
        };

        // 2. Attach the listener to the document
        document.addEventListener("keydown", handleKeydown);

        // 3. Create a cleanup function to remove the listener and close the modal
        const cleanup = () => {
            document.removeEventListener("keydown", handleKeydown);
            store.open = false;
        };

        // 4. Update the confirm/cancel actions to trigger resolution AND cleanup
        store.onConfirm = () => {
            resolve(true);
            cleanup();
        };

        store.onCancel = () => {
            resolve(false);
            cleanup();
        };

        // Open the modal
        store.open = true;
    });
};

Livewire.directive("custom-confirm", async ({ el, directive, _, cleanup }) => {
    let content = directive.expression;

    let onClick = async (e) => {
        e.preventDefault();
        e.stopImmediatePropagation();

        if (await confirmModal("Confirm", content)) {
            el.removeEventListener("click", onClick, {
                capture: true,
            });

            // call default event like: wire:click, submit ....
            el.click();

            // add this event
            el.addEventListener("click", onClick, {
                capture: true,
            });
        }
    };

    el.addEventListener("click", onClick, { capture: true });

    cleanup(() => {
        el.removeEventListener("click", onClick, { capture: true });
    });
});
