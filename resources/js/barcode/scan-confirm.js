import { claimScans, scanBeep } from "./scanner";

/**
 * "Confirm scanned item" step for every POS cart (pharmacy, grocery, motor
 * shop). Spread into the cart's Alpine object:
 *
 *   return { ...scanConfirmMixin(), cart: [], ... }
 *
 * The cart must provide addProductToCart(product, packagingId, quantity),
 * getUsedBaseStock(productId) and getPackagePrice(pkg).
 *
 * Flow: scan -> dialog shows product, unit, price, stock -> Enter adds it,
 * Esc cancels. While the dialog is open it owns the scanner: scanning the
 * same barcode again adds one more, anything else is refused until this item
 * is confirmed or cancelled, so a cashier can never lose track of a scan.
 */
export function scanConfirmMixin() {
    return {
        scanConfirm: {
            open: false,
            product: null,
            packagingId: null,
            code: null,
            quantity: 1,
            openedAt: 0,
            warning: "",
        },

        scanConfirmRelease: null,

        openScanConfirm(product, packagingId, code) {
            if (!product || !Array.isArray(product.packagings) || product.packagings.length === 0) {
                scanBeep("error");

                return;
            }

            const pkg = product.packagings.find((p) => p.id == packagingId) || product.packagings[0];

            this.scanConfirm = {
                open: true,
                product,
                packagingId: pkg.id,
                code,
                quantity: this.scanConfirmStep(pkg),
                openedAt: performance.now(),
                warning: "",
            };

            // While the dialog is up, scans come here instead of the page.
            this.scanConfirmRelease?.();
            this.scanConfirmRelease = claimScans((scanned) => this.scanWhileConfirming(scanned));

            // Focus the quantity so typing digits replaces it straight away.
            this.$nextTick(() => {
                const input = document.getElementById("scan-confirm-qty");
                input?.focus();
                input?.select();
            });
        },

        closeScanConfirm() {
            this.scanConfirmRelease?.();
            this.scanConfirmRelease = null;
            this.scanConfirm.open = false;
            this.scanConfirm.product = null;
            this.scanConfirm.warning = "";
        },

        scanWhileConfirming(code) {
            if (code === this.scanConfirm.code) {
                this.changeScanConfirmQty(1);
                this.scanConfirm.warning = "";

                return;
            }

            this.scanConfirm.warning = `Scanned ${code}. Press Enter to add the current item or Esc to cancel it first.`;
            scanBeep("error");
        },

        scanConfirmPackaging() {
            const product = this.scanConfirm.product;
            if (!product) return null;

            return product.packagings.find((p) => p.id == this.scanConfirm.packagingId) || product.packagings[0];
        },

        selectScanConfirmPackaging(id) {
            this.scanConfirm.packagingId = id;
            this.scanConfirm.quantity = Math.min(
                Math.max(this.scanConfirm.quantity, this.scanConfirmStep()),
                Math.max(this.scanConfirmMax(), this.scanConfirmStep()),
            );
        },

        /**
         * Quantity step follows the cart's own rules: only carts that sell by
         * fractional units (motor shop) define getQuantityStep().
         */
        scanConfirmStep(pkg = null) {
            pkg ??= this.scanConfirmPackaging();

            if (typeof this.getQuantityStep !== "function") return 1;

            return parseFloat(this.getQuantityStep({ allowDecimal: Boolean(pkg?.allow_decimal) })) || 1;
        },

        scanConfirmIsSpecialOrder() {
            return this.scanConfirm.product?.stock_type === "special_order";
        },

        /** How many more of this unit can go in the cart. */
        scanConfirmMax() {
            const product = this.scanConfirm.product;
            const pkg = this.scanConfirmPackaging();
            if (!product || !pkg) return 0;
            if (this.scanConfirmIsSpecialOrder()) return 999999;

            const conversionFactor = parseFloat(pkg.conversion_factor) || 1;
            const remainingBase = (parseFloat(product.stock) || 0) - this.getUsedBaseStock(product.id);
            const max = remainingBase / conversionFactor;

            return Math.max(0, this.scanConfirmStep(pkg) < 1 ? Math.floor(max * 100) / 100 : Math.floor(max));
        },

        scanConfirmInCart() {
            const product = this.scanConfirm.product;
            const pkg = this.scanConfirmPackaging();
            if (!product || !pkg) return 0;

            const item = this.cart.find((i) => i.cartId === product.id + "_" + pkg.id);

            return item ? item.quantity : 0;
        },

        scanConfirmPrice() {
            return this.getPackagePrice(this.scanConfirmPackaging());
        },

        scanConfirmCanAdd() {
            const quantity = parseFloat(this.scanConfirm.quantity) || 0;

            return quantity > 0 && quantity <= this.scanConfirmMax();
        },

        changeScanConfirmQty(direction) {
            const step = this.scanConfirmStep();
            const next = Math.round(((parseFloat(this.scanConfirm.quantity) || 0) + direction * step) * 100) / 100;

            this.scanConfirm.quantity = Math.min(Math.max(next, step), Math.max(this.scanConfirmMax(), step));
        },

        normalizeScanConfirmQty() {
            const step = this.scanConfirmStep();
            let quantity = parseFloat(this.scanConfirm.quantity);

            if (Number.isNaN(quantity) || quantity < step) quantity = step;

            quantity = step < 1 ? Math.round(quantity * 100) / 100 : Math.floor(quantity);

            this.scanConfirm.quantity = Math.min(quantity, Math.max(this.scanConfirmMax(), step));
        },

        confirmScan() {
            this.normalizeScanConfirmQty();

            if (!this.scanConfirmCanAdd()) {
                scanBeep("error");

                return;
            }

            this.addProductToCart(this.scanConfirm.product, this.scanConfirm.packagingId, this.scanConfirm.quantity);
            this.closeScanConfirm();
        },

        /**
         * Keyboard for the open dialog. The cart's own shortcuts are paused
         * while it is open, so Esc here can never clear the cart.
         */
        handleScanConfirmKeydown(event) {
            if (!this.scanConfirm.open) return;

            if (event.key === "Enter") {
                event.preventDefault();

                // The scanner's own Enter is consumed before the dialog opens;
                // this guard just makes sure no stray Enter confirms instantly.
                if (performance.now() - this.scanConfirm.openedAt < 150) return;

                this.confirmScan();
            } else if (event.key === "Escape") {
                event.preventDefault();
                this.closeScanConfirm();
            } else if (event.key === "ArrowUp" || event.key === "+") {
                event.preventDefault();
                this.changeScanConfirmQty(1);
            } else if (event.key === "ArrowDown" || event.key === "-") {
                event.preventDefault();
                this.changeScanConfirmQty(-1);
            }
        },
    };
}
