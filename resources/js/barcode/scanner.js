/**
 * Barcode scanner input service.
 *
 * Two ways a scanner can talk to the page, chosen per scanner model in
 * Settings > Scanners & Printers:
 *
 *  - Keyboard (USB HID, the factory default): the scanner "types" the code and
 *    presses a suffix key. Scans are recognised by *timing* -- a scanner types
 *    an order of magnitude faster than a person. Browsers deliberately hide
 *    keyboards from web pages, so in this mode the page cannot know whether
 *    the scanner is plugged in; it says so instead of guessing.
 *  - USB COM (serial): the scanner is a serial port read through Web Serial
 *    (Chrome / Edge, https or localhost). The page knows exactly when it is
 *    plugged in or out.
 *
 * Every scan passes the branch's "allowed barcode types" check (see types.js)
 * before anything acts on it.
 *
 * One driver runs per page. Screens and dialogs "claim" scans: the most recent
 * claim wins, so a scan always goes to whatever the user is looking at.
 */

import {
    classifyBarcode,
    describeBarcode,
    isAllowedBarcode,
} from "./types";

const SUFFIX_KEYS = {
    enter: ["Enter"],
    tab: ["Tab"],
    none: [],
};

const PAIRED_KEY = "citipos.scanner.serialPaired";

function isEditable(el) {
    if (!el) return false;
    if (el.isContentEditable === true) return true;

    const tag = (el.tagName || "").toLowerCase();

    if (tag === "textarea") return true;

    return (
        tag === "input" &&
        !["checkbox", "radio", "button", "submit", "reset", "file", "range", "color"].includes(el.type)
    );
}

/**
 * Write a value into a field the way a user would, so Alpine x-model and
 * Livewire wire:model both see the change.
 */
function setFieldValue(el, value) {
    if (!el || el.isContentEditable) return;

    el.value = value;
    el.dispatchEvent(new Event("input", { bubbles: true }));
}

function rememberPaired(value) {
    try {
        if (value) localStorage.setItem(PAIRED_KEY, "1");
        else localStorage.removeItem(PAIRED_KEY);
    } catch {
        // Storage unavailable: the badge just says "Pair scanner" instead of "unplugged".
    }
}

function wasPaired() {
    try {
        return localStorage.getItem(PAIRED_KEY) === "1";
    } catch {
        return false;
    }
}

function hexId(value) {
    const id = parseInt(String(value ?? ""), 16);

    return Number.isNaN(id) ? null : id;
}

function formatHexId(value) {
    return value == null ? "" : value.toString(16).toUpperCase().padStart(4, "0");
}

/**
 * Keyboard-mode driver: recognises scanner input among ordinary keystrokes.
 *
 * Works wherever focus is. When a scan lands in a text field, the characters
 * it typed are taken back out, so scanning never leaves a barcode in the
 * search box or a quantity field. Fields marked data-barcode-input are the
 * exception: they are meant to receive the code, and end up holding exactly it.
 */
class WedgeDriver {
    constructor({ minLength = 6, thresholdMs = 50, suffix = "enter", accept, deliver }) {
        this.minLength = minLength;
        this.thresholdMs = thresholdMs;
        this.suffixKeys = SUFFIX_KEYS[suffix] ?? SUFFIX_KEYS.enter;
        this.detectByPause = this.suffixKeys.length === 0;
        this.accept = accept;
        this.deliver = deliver;

        this.handler = this.handleKeydown.bind(this);
        this.reset();
        this.lastKeyTime = 0;
        this.lastBurstAt = 0;
    }

    start(setStatus) {
        window.addEventListener("keydown", this.handler, true);
        setStatus("keyboard");
    }

    stop() {
        window.removeEventListener("keydown", this.handler, true);
        clearTimeout(this.pauseTimer);
        this.reset();
    }

    reset() {
        this.buffer = "";
        this.field = null;
        this.fieldValue = null;
        clearTimeout(this.pauseTimer);
    }

    handleKeydown(event) {
        if (event.ctrlKey || event.altKey || event.metaKey) return;

        const now = performance.now();
        const gap = now - this.lastKeyTime;
        this.lastKeyTime = now;

        // A slow keystroke means a human is typing: start over.
        if (gap > this.thresholdMs) {
            this.reset();
        }

        if (this.suffixKeys.includes(event.key)) {
            if (this.buffer.length >= this.minLength) {
                // Only now is the key claimed, so a genuine Enter or Tab press
                // in a form is never swallowed.
                event.preventDefault();
                event.stopImmediatePropagation();
                this.complete();
            } else {
                this.reset();
            }

            return;
        }

        // Scanners emit single printable characters.
        if (event.key.length !== 1) return;

        if (this.buffer === "") {
            // Possible start of a scan. Remember the field and what it held, so
            // the typed characters can be taken back out if this is a scan.
            this.field = isEditable(event.target) ? event.target : null;
            this.fieldValue = this.field ? this.field.value : null;
        } else {
            // A second character this fast is machine input: keep it away
            // from page shortcuts (C = calculator, etc.). Text still types,
            // and is cleaned up when the scan completes.
            this.lastBurstAt = now;
            event.stopImmediatePropagation();
        }

        this.buffer += event.key;

        if (this.detectByPause) {
            clearTimeout(this.pauseTimer);
            this.pauseTimer = setTimeout(() => {
                if (this.buffer.length >= this.minLength) this.complete();
                else this.reset();
            }, this.thresholdMs * 3);
        }
    }

    complete() {
        const raw = this.buffer.trim();
        const field = this.field;
        const before = this.fieldValue;

        this.reset();

        if (raw.length < this.minLength) return;

        const verdict = this.accept(raw);

        if (field) {
            if (verdict.ok && field.dataset && "barcodeInput" in field.dataset) {
                setFieldValue(field, verdict.code);
            } else if (before !== null) {
                setFieldValue(field, before);
            }
        }

        this.deliver(verdict);
    }
}

/**
 * USB COM driver: reads the scanner as a serial port through Web Serial.
 *
 * The port permission is granted once per PC ("Pair scanner"); after that the
 * page reconnects by itself whenever the scanner is plugged in, on every page.
 */
class SerialDriver {
    constructor({ minLength = 6, thresholdMs = 50, suffix = "enter", baudRate = 9600, vendorId = "", productId = "", accept, deliver }) {
        this.minLength = minLength;
        this.idleMs = Math.max(60, thresholdMs * 3);
        this.suffix = suffix;
        this.baudRate = Number(baudRate) || 9600;
        this.vendorId = hexId(vendorId);
        this.productId = hexId(productId);
        this.accept = accept;
        this.deliver = deliver;

        this.port = null;
        this.reader = null;
        this.buffer = "";
        this.stopped = false;
        this.lastBurstAt = 0;

        this.onConnect = (event) => this.handleConnect(event.target);
        this.onDisconnect = (event) => this.handleDisconnect(event.target);
    }

    static supported() {
        return typeof navigator !== "undefined" && "serial" in navigator;
    }

    start(setStatus) {
        this.setStatus = setStatus;

        if (!SerialDriver.supported()) {
            setStatus(typeof window !== "undefined" && window.isSecureContext === false ? "insecure" : "unsupported");

            return;
        }

        navigator.serial.addEventListener("connect", this.onConnect);
        navigator.serial.addEventListener("disconnect", this.onDisconnect);

        this.openGranted();
    }

    async stop() {
        this.stopped = true;
        clearTimeout(this.idleTimer);

        if (SerialDriver.supported()) {
            navigator.serial.removeEventListener("connect", this.onConnect);
            navigator.serial.removeEventListener("disconnect", this.onDisconnect);
        }

        await this.closePort();
    }

    matches(port) {
        const info = port.getInfo ? port.getInfo() : {};

        if (this.vendorId !== null && info.usbVendorId !== this.vendorId) return false;
        if (this.productId !== null && info.usbProductId !== this.productId) return false;

        return true;
    }

    /** Open a port this PC already paired, if it is plugged in. */
    async openGranted() {
        if (this.stopped) return;

        const ports = (await navigator.serial.getPorts()).filter((port) => this.matches(port));

        if (ports.length === 0) {
            this.setStatus(wasPaired() ? "disconnected" : "unpaired");

            return;
        }

        await this.open(ports[0]);
    }

    /** Ask the user to pick the scanner. Must run from a click. */
    async pair() {
        if (!SerialDriver.supported()) return;

        const filters = this.vendorId !== null
            ? [{ usbVendorId: this.vendorId, ...(this.productId !== null ? { usbProductId: this.productId } : {}) }]
            : [];

        let port;

        try {
            port = await navigator.serial.requestPort({ filters });
        } catch {
            return; // Picker closed without choosing.
        }

        rememberPaired(true);
        await this.closePort();
        await this.open(port);
    }

    async retry() {
        await this.closePort();
        await this.openGranted();
    }

    async open(port) {
        if (this.stopped) return;

        this.setStatus("connecting");

        try {
            await port.open({ baudRate: this.baudRate });
        } catch (error) {
            // Another tab (or program) holds the port.
            this.setStatus(error?.name === "InvalidStateError" || error?.name === "NetworkError" ? "busy" : "error", error?.message ?? "");

            return;
        }

        this.port = port;
        rememberPaired(true);
        this.setStatus("connected");
        this.readLoop(port);
    }

    async readLoop(port) {
        const decoder = new TextDecoder();

        while (port.readable && !this.stopped && this.port === port) {
            this.reader = port.readable.getReader();

            try {
                for (;;) {
                    const { value, done } = await this.reader.read();
                    if (done) break;
                    if (value) this.feed(decoder.decode(value, { stream: true }));
                }
            } catch {
                // Device lost or stream closed; the disconnect event updates the status.
                break;
            } finally {
                try {
                    this.reader.releaseLock();
                } catch {
                    // Already released.
                }
                this.reader = null;
            }
        }
    }

    /** Split incoming text into scans on the scanner's suffix. */
    feed(text) {
        this.buffer += text;
        this.lastBurstAt = performance.now();

        if (this.suffix === "none") {
            clearTimeout(this.idleTimer);
            this.idleTimer = setTimeout(() => this.flush(this.buffer, true), this.idleMs);

            return;
        }

        const separator = this.suffix === "tab" ? /\t/ : /\r\n|\r|\n/;
        const parts = this.buffer.split(separator);

        this.buffer = parts.pop();
        parts.forEach((line) => this.flush(line, false));
    }

    flush(line, clearBuffer) {
        if (clearBuffer) this.buffer = "";

        const raw = line.trim();

        if (raw.length < this.minLength) return;

        this.deliver(this.accept(raw));
    }

    handleConnect(port) {
        if (this.port || !this.matches(port)) return;

        this.open(port);
    }

    async handleDisconnect(port) {
        if (this.port !== port) return;

        await this.closePort();
        this.setStatus("disconnected");
    }

    async closePort() {
        const port = this.port;
        this.port = null;

        if (this.reader) {
            try {
                await this.reader.cancel();
            } catch {
                // Already cancelled.
            }
        }

        if (port) {
            try {
                await port.close();
            } catch {
                // Already closed or unplugged.
            }
        }
    }
}

/**
 * The registry a future driver (e.g. a desktop-app bridge) plugs into.
 */
const drivers = {
    keyboard: WedgeDriver,
    serial: SerialDriver,
};

export function registerBarcodeDriver(name, driver) {
    drivers[name] = driver;
}

// ----------------------------------------------------------------------
// Page-wide state, scan routing and connection status
// ----------------------------------------------------------------------

const state = {
    driver: null,
    config: null,
    claims: [],
    lastCode: null,
    lastCodeAt: 0,
    status: "off",
    statusDetail: "",
    statusListeners: new Set(),
};

function setStatus(status, detail = "") {
    state.status = status;
    state.statusDetail = detail;
    state.statusListeners.forEach((listener) => listener(status, detail));
}

/** Follow the scanner connection status; called immediately with the current one. */
export function onScannerStatus(listener) {
    state.statusListeners.add(listener);
    listener(state.status, state.statusDetail);

    return () => state.statusListeners.delete(listener);
}

/**
 * Prefix removal and the allowed-types check, shared by both drivers.
 *
 * @returns {{ ok: boolean, code: string, label: string, reason?: string }}
 */
export function acceptScan(raw, config = state.config ?? {}) {
    let code = raw;

    if (config.prefix && code.startsWith(config.prefix)) {
        code = code.slice(config.prefix.length);
    }

    const result = classifyBarcode(code);
    const label = describeBarcode(result);

    if (!result.code) {
        return { ok: false, code: "", label, reason: "The scan was empty." };
    }

    if (!isAllowedBarcode(result, config.allowed_types)) {
        return {
            ok: false,
            code: result.code,
            label,
            reason: `${label} barcodes are not allowed for this scanner (${result.code}).`,
        };
    }

    return { ok: true, code: result.code, label };
}

function deliver(verdict) {
    if (!verdict.ok) {
        scanBeep("error");

        if (typeof window !== "undefined") {
            window.dispatchEvent(new CustomEvent("notify", { detail: { content: verdict.reason, type: "error" } }));
            window.dispatchEvent(new CustomEvent("barcode-rejected", { detail: verdict }));
        }

        return;
    }

    route(verdict.code);
}

function route(code) {
    // Scanners can double-fire; ignore an identical code within 300ms.
    const now = performance.now();
    if (code === state.lastCode && now - state.lastCodeAt < 300) return;

    state.lastCode = code;
    state.lastCodeAt = now;

    // Newest claim first. A handler returning false passes the scan down,
    // e.g. a dialog that is not actually open right now.
    for (let i = state.claims.length - 1; i >= 0; i--) {
        if (state.claims[i].handler(code) !== false) return;
    }
}

function ensureDriver(config) {
    if (state.driver) return;

    const Driver = drivers[config.connection] || WedgeDriver;

    state.config = config;
    state.driver = new Driver({
        minLength: config.min_length ?? 6,
        thresholdMs: config.threshold_ms ?? 50,
        suffix: config.suffix ?? "enter",
        baudRate: config.baud_rate ?? 9600,
        vendorId: config.usb_vendor_id ?? "",
        productId: config.usb_product_id ?? "",
        accept: (raw) => acceptScan(raw),
        deliver,
    });

    state.driver.start(setStatus);
}

function stopDriverIfIdle() {
    if (state.claims.length > 0 || !state.driver) return;

    state.driver.stop();
    state.driver = null;
    state.config = null;
    setStatus("off");
}

/**
 * Take scans until the returned release function is called.
 */
export function claimScans(handler) {
    const claim = { handler };
    state.claims.push(claim);

    return () => {
        state.claims = state.claims.filter((c) => c !== claim);
        stopDriverIfIdle();
    };
}

/** "Pair scanner" (USB COM mode). Must be called from a click. */
export function pairScanner() {
    return state.driver?.pair?.();
}

export function retryScanner() {
    return state.driver?.retry?.();
}

/**
 * True when scanner keystrokes arrived after the given performance.now()
 * timestamp. Lets a single-key shortcut wait a moment and step aside if the
 * key turned out to be the first character of a scan.
 */
export function scannedSince(timestamp) {
    return Boolean(state.driver && state.driver.lastBurstAt > timestamp);
}

// ----------------------------------------------------------------------
// Feedback
// ----------------------------------------------------------------------

let audioContext = null;

/**
 * Short tones for scans the scanner's own beep cannot flag as wrong:
 * unknown barcode, a type that is not allowed, or a refused scan.
 */
export function scanBeep(kind = "error") {
    if (state.config && state.config.sound === false) return;

    try {
        audioContext ??= new (window.AudioContext || window.webkitAudioContext)();

        const tones = kind === "error" ? [220, 180] : [880];

        tones.forEach((frequency, i) => {
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            const start = audioContext.currentTime + i * 0.16;

            oscillator.type = "square";
            oscillator.frequency.value = frequency;
            gain.gain.setValueAtTime(0.06, start);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.14);

            oscillator.connect(gain).connect(audioContext.destination);
            oscillator.start(start);
            oscillator.stop(start + 0.15);
        });
    } catch {
        // No audio available; the on-screen message still shows.
    }
}

// ----------------------------------------------------------------------
// Status badge text
// ----------------------------------------------------------------------

/**
 * Badge text per status: a short label, a one-line detail under it, and a
 * longer tooltip. "live" makes the dot pulse while the scanner is reachable.
 */
const STATUS_INFO = {
    off: { tone: "muted", label: "Scanner off", detail: "", hint: "" },
    keyboard: {
        tone: "neutral",
        label: "Scanner on",
        detail: "Connection unknown",
        hint: "This scanner works as a USB keyboard. Browsers cannot see keyboards, so this page cannot tell whether it is plugged in. Scanning still works.",
    },
    unsupported: {
        tone: "danger",
        label: "Scanner needs Chrome or Edge",
        detail: "USB COM scanner",
        hint: "This scanner is set to USB COM mode, which this browser does not support. Open CitiPOS in Chrome or Edge.",
    },
    insecure: {
        tone: "danger",
        label: "Scanner needs a secure address",
        detail: "Open over https://",
        hint: "USB COM scanners only work when CitiPOS is opened over https:// or on localhost.",
    },
    unpaired: {
        tone: "warning",
        label: "Pair scanner",
        detail: "Click to choose it",
        hint: "Click, then choose the scanner in the list. This is needed once per PC.",
        action: "pair",
    },
    connecting: { tone: "neutral", label: "Connecting scanner…", detail: "Please wait", hint: "", live: true },
    connected: { tone: "success", label: "Scanner connected", detail: "Ready to scan", hint: "The scanner is plugged in and ready.", live: true },
    disconnected: {
        tone: "danger",
        label: "Scanner unplugged",
        detail: "Plug in, or click",
        hint: "Plug the scanner in; it reconnects by itself. Click to pick it again if it does not.",
        action: "pair",
    },
    busy: {
        tone: "warning",
        label: "Scanner in use elsewhere",
        detail: "Close it, then click",
        hint: "Another CitiPOS tab or program is using the scanner. Close it, then click to retry.",
        action: "retry",
    },
    error: { tone: "danger", label: "Scanner error", detail: "Click to try again", hint: "Click to try again.", action: "retry" },
};

const TONE_CLASSES = {
    success: "border-emerald-500/30 bg-emerald-50/80 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-300",
    neutral: "border-black/10 dark:border-white/10 bg-white dark:bg-white/5 text-neutral-800 dark:text-neutral-200",
    warning: "border-amber-500/40 bg-amber-50/80 dark:bg-amber-900/20 text-amber-900 dark:text-amber-300",
    danger: "border-rose-500/40 bg-rose-50/80 dark:bg-rose-900/20 text-rose-800 dark:text-rose-300",
    muted: "border-black/10 dark:border-white/10 text-neutral-400",
};

const DOT_CLASSES = {
    success: "bg-emerald-500",
    neutral: "bg-neutral-400",
    warning: "bg-amber-500",
    danger: "bg-rose-500",
    muted: "bg-neutral-300",
};

// ----------------------------------------------------------------------
// Alpine components
// ----------------------------------------------------------------------

/**
 * Page-level scanner listener and status badge. Starts the driver with the
 * branch's scanner model and dispatches `barcode-scanned` from its element
 * when no dialog has claimed the scan.
 *
 * Usage in Blade:
 *   <div x-data="barcodeScanner(@js($this->scannerConfig))"> ... </div>
 */
export function barcodeScanner(config = {}) {
    return {
        scannerEnabled: Boolean(config.enabled),
        profileName: config.profile || null,
        status: "off",
        lastScan: null,
        release: null,
        unsubscribe: null,

        init() {
            if (!this.scannerEnabled) return;

            this.unsubscribe = onScannerStatus((status) => {
                this.status = status;
            });

            ensureDriver(config);
            this.release = claimScans((code) => this.handleScan(code));
        },

        destroy() {
            this.release?.();
            this.release = null;
            this.unsubscribe?.();
        },

        handleScan(code) {
            this.lastScan = code;
            this.$dispatch("barcode-scanned", { code });
        },

        statusInfo() {
            return STATUS_INFO[this.status] ?? STATUS_INFO.off;
        },

        statusClass() {
            return TONE_CLASSES[this.statusInfo().tone] ?? TONE_CLASSES.neutral;
        },

        statusDotClass() {
            return DOT_CLASSES[this.statusInfo().tone] ?? DOT_CLASSES.neutral;
        },

        /** Second line: the last scanned code while scanning works, else the status detail. */
        statusDetail() {
            if (this.lastScan && (this.status === "keyboard" || this.status === "connected")) {
                return "Last: " + this.lastScan;
            }

            return this.statusInfo().detail;
        },

        statusTitle() {
            const info = this.statusInfo();

            return [this.profileName ? `Scanner: ${this.profileName}` : "", info.hint].filter(Boolean).join(". ");
        },

        statusAction() {
            const action = this.statusInfo().action;

            if (action === "pair") pairScanner();
            if (action === "retry") retryScanner();
        },
    };
}

/**
 * Lets a dialog take scans while it is open.
 *
 * Usage: x-data="barcodeDialogScanner('modal-id', (code) => ...)" on an
 * element inside the dialog. The claim follows the modal's own
 * modal-opened / modal-closed events, and also checks it is really on
 * screen, so a missed close event can never leave it stealing scans.
 */
export function barcodeDialogScanner(modalId, onScan) {
    return {
        release: null,

        init() {
            this.onOpened = (e) => {
                if (e.detail?.id === modalId) this.claim();
            };
            this.onClosed = (e) => {
                if (e.detail?.id === modalId) this.unclaim();
            };

            window.addEventListener("modal-opened", this.onOpened);
            window.addEventListener("modal-closed", this.onClosed);
        },

        destroy() {
            window.removeEventListener("modal-opened", this.onOpened);
            window.removeEventListener("modal-closed", this.onClosed);
            this.unclaim();
        },

        claim() {
            if (this.release) return;

            this.release = claimScans((code) => {
                if (this.$el.offsetParent === null) return false;

                onScan.call(this, code);
            });
        },

        unclaim() {
            this.release?.();
            this.release = null;
        },
    };
}

/**
 * Settings > Scanners & Printers scan test for keyboard-mode scanners.
 * Records the raw keystroke timing of one scan, shows the barcode type, and
 * suggests profile values -- so a new scanner model can be tuned by scanning
 * once instead of guessing.
 */
export function scannerTest() {
    return {
        keys: [],
        result: null,
        idleTimer: null,

        reset() {
            this.keys = [];
            this.result = null;
            this.$refs.area?.focus();
        },

        record(event) {
            if (event.ctrlKey || event.altKey || event.metaKey) return;

            event.preventDefault();

            const isSuffix = event.key === "Enter" || event.key === "Tab";

            if (this.result && !isSuffix) {
                this.keys = [];
                this.result = null;
            }

            this.keys.push({ key: event.key, at: performance.now() });

            clearTimeout(this.idleTimer);

            if (isSuffix) {
                this.finish(event.key);
            } else {
                // A scanner configured with no suffix just stops typing.
                this.idleTimer = setTimeout(() => this.finish(null), 400);
            }
        },

        finish(suffixKey) {
            const chars = this.keys.filter((k) => k.key.length === 1);

            if (chars.length === 0) {
                this.keys = [];

                return;
            }

            const gaps = [];
            for (let i = 1; i < this.keys.length; i++) {
                gaps.push(this.keys[i].at - this.keys[i - 1].at);
            }

            const maxGap = gaps.length ? Math.max(...gaps) : 0;
            const avgGap = gaps.length ? gaps.reduce((a, b) => a + b, 0) / gaps.length : 0;
            const code = chars.map((k) => k.key).join("");
            const classified = classifyBarcode(code);

            this.result = {
                code,
                length: code.length,
                suffix: suffixKey === "Enter" ? "enter" : suffixKey === "Tab" ? "tab" : "none",
                avgGap: Math.round(avgGap),
                maxGap: Math.round(maxGap),
                looksLikeScanner: maxGap < 100,
                type: describeBarcode(classified),
                types: classified.types,
                aim: classified.aim,
                // Double the slowest gap seen, with a floor, leaves headroom
                // without letting human typing (100ms+) through.
                suggestedThreshold: Math.min(200, Math.max(30, Math.ceil(maxGap * 2) + 10)),
                suggestedMinLength: Math.max(4, Math.min(6, classified.code.length)),
            };

            this.keys = [];
        },

        isAllowed(allowedTypes) {
            if (!this.result) return false;

            return isAllowedBarcode({ types: this.result.types }, allowedTypes);
        },
    };
}

/**
 * Settings > Scanners & Printers test for USB COM scanners: pick the port,
 * read one scan, and report its USB ID and barcode type.
 */
export function serialScannerTest() {
    return {
        supported: SerialDriver.supported(),
        secure: typeof window === "undefined" || window.isSecureContext !== false,
        phase: "idle", // idle | waiting | done | error
        message: "",
        result: null,

        async run(baudRate, suffix) {
            this.result = null;
            this.message = "";

            let port;

            try {
                port = await navigator.serial.requestPort();
            } catch {
                return; // Picker closed.
            }

            const info = port.getInfo ? port.getInfo() : {};

            try {
                await port.open({ baudRate: Number(baudRate) || 9600 });
            } catch {
                this.phase = "error";
                this.message = "Could not open the scanner. Close other CitiPOS tabs that use it, then try again.";

                return;
            }

            this.phase = "waiting";

            const raw = await this.readOneScan(port, suffix, 15000);

            try {
                await port.close();
            } catch {
                // Already closed.
            }

            if (raw === null) {
                this.phase = "error";
                this.message = "No scan received within 15 seconds. Check that the scanner is in USB COM mode and try again.";

                return;
            }

            const classified = classifyBarcode(raw.trim());

            rememberPaired(true);
            this.phase = "done";
            this.result = {
                code: classified.code,
                raw: raw.trim(),
                type: describeBarcode(classified),
                types: classified.types,
                aim: classified.aim,
                vendorId: formatHexId(info.usbVendorId),
                productId: formatHexId(info.usbProductId),
            };
        },

        async readOneScan(port, suffix, timeoutMs) {
            const reader = port.readable.getReader();
            const decoder = new TextDecoder();
            let text = "";
            let idleTimer = null;
            let timeoutTimer = null;

            const finished = new Promise((resolve) => {
                timeoutTimer = setTimeout(() => resolve(text.trim() ? text : null), timeoutMs);

                (async () => {
                    try {
                        for (;;) {
                            const { value, done } = await reader.read();
                            if (done) break;

                            text += decoder.decode(value, { stream: true });

                            const ended = suffix === "tab" ? /\t/.test(text) : suffix === "none" ? false : /[\r\n]/.test(text);

                            if (ended) {
                                resolve(text.split(suffix === "tab" ? /\t/ : /\r\n|\r|\n/)[0]);
                                break;
                            }

                            clearTimeout(idleTimer);
                            idleTimer = setTimeout(() => resolve(text), 300);
                        }
                    } catch {
                        resolve(text.trim() ? text : null);
                    }
                })();
            });

            const raw = await finished;

            clearTimeout(idleTimer);
            clearTimeout(timeoutTimer);

            try {
                await reader.cancel();
            } catch {
                // Already cancelled.
            }

            reader.releaseLock();

            return raw;
        },

        isAllowed(allowedTypes) {
            if (!this.result) return false;

            return isAllowedBarcode({ types: this.result.types }, allowedTypes);
        },
    };
}

export default barcodeScanner;
