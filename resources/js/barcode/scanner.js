/**
 * Barcode scanner input service.
 *
 * A USB barcode scanner in its default mode is a keyboard: it "types" the code
 * and presses Enter. The browser cannot see which device sent those keystrokes,
 * so identifying a scan means recognising the *timing* -- a scanner emits
 * characters an order of magnitude faster than a person can type.
 *
 * The service is driver-based on purpose. WedgeDriver is what works with any
 * scanner today; a HidDriver using navigator.hid can be registered later
 * without the POS code changing, because both drivers speak the same
 * onScan(code) callback.
 */

/**
 * Recognises scanner input among ordinary keystrokes.
 *
 * Deliberately passive: it never calls preventDefault on normal typing, and it
 * ignores input aimed at a text field unless that field opted in. If this
 * driver malfunctions the worst case is that nothing happens -- the cashier
 * clicks the product like they do today.
 */
class WedgeDriver {
    constructor({ minLength = 6, thresholdMs = 50, onScan }) {
        this.minLength = minLength;
        this.thresholdMs = thresholdMs;
        this.onScan = onScan;

        this.buffer = "";
        this.lastKeyTime = 0;
        this.handler = this.handleKeydown.bind(this);
    }

    start() {
        window.addEventListener("keydown", this.handler, true);
    }

    stop() {
        window.removeEventListener("keydown", this.handler, true);
        this.buffer = "";
    }

    /**
     * Typing into a field should stay typing. The one exception is a field
     * that asks for scans via data-barcode-input, e.g. an inventory search box.
     */
    shouldIgnoreTarget(target) {
        if (!target) return false;
        if (target.dataset && "barcodeInput" in target.dataset) return false;

        const tag = (target.tagName || "").toLowerCase();

        return (
            tag === "textarea" ||
            target.isContentEditable === true ||
            (tag === "input" && !["checkbox", "radio", "button", "submit"].includes(target.type))
        );
    }

    handleKeydown(event) {
        if (event.ctrlKey || event.altKey || event.metaKey) return;

        const now = Date.now();
        const gap = now - this.lastKeyTime;
        this.lastKeyTime = now;

        // A slow keystroke means a human started over.
        if (gap > this.thresholdMs) {
            this.buffer = "";
        }

        if (event.key === "Enter") {
            const code = this.buffer;
            this.buffer = "";

            if (code.length >= this.minLength) {
                // Only now do we claim the event, so a genuine Enter press in a
                // form is never swallowed.
                event.preventDefault();
                event.stopPropagation();
                this.onScan(code);
            }

            return;
        }

        // Scanners emit single printable characters.
        if (event.key.length !== 1) return;
        if (this.shouldIgnoreTarget(event.target) && this.buffer === "") return;

        this.buffer += event.key;
    }
}

/**
 * The registry a future WebHID driver plugs into.
 *
 * A HID driver would need HTTPS, an explicit per-device permission prompt, and
 * a scanner that supports raw HID mode -- so it can only ever be opt-in on top
 * of the wedge default, never a replacement for it.
 */
const drivers = {
    wedge: WedgeDriver,
};

export function registerBarcodeDriver(name, driver) {
    drivers[name] = driver;
}

/**
 * Alpine data object for a scanner-aware screen.
 *
 * Usage in Blade:
 *   <div x-data="barcodeScanner(@js($this->scannerConfig))"> ... </div>
 */
export function barcodeScanner(config = {}) {
    return {
        scannerEnabled: Boolean(config.enabled),
        driverName: config.driver || "wedge",
        driver: null,
        lastScan: null,
        lastScanAt: 0,

        init() {
            if (!this.scannerEnabled) return;

            const Driver = drivers[this.driverName] || WedgeDriver;

            this.driver = new Driver({
                minLength: config.min_length ?? 6,
                thresholdMs: config.threshold_ms ?? 50,
                onScan: (code) => this.handleScan(code),
            });

            this.driver.start();

            // Livewire swaps pages without a reload, so release the global
            // listener when this element goes away.
            this.$el.addEventListener("livewire:navigating", () => this.driver?.stop());
        },

        destroy() {
            this.driver?.stop();
        },

        handleScan(code) {
            // Scanners can double-fire; ignore an identical code within 400ms.
            const now = Date.now();
            if (code === this.lastScan && now - this.lastScanAt < 400) return;

            this.lastScan = code;
            this.lastScanAt = now;

            this.$dispatch("barcode-scanned", { code });
        },
    };
}

export default barcodeScanner;
