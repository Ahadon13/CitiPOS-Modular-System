import "./globals/theme.js"; /* By Sheaf.dev */
import "./globals/modals.js";
import "./bootstrap";
import "./globals/confirm-modals.js";
// Spatie
import "../../vendor/spatie/livewire-filepond/resources/dist/filepond";
import {
    Livewire,
    Alpine,
} from "../../vendor/livewire/livewire/dist/livewire.esm";
// Apex Charts
import ApexCharts from "apexcharts";

window.ApexCharts = ApexCharts;

window.posApp = (paymentMethods = []) => {
    return {
        cart: [],
        paymentMethods: paymentMethods, // Injected from Blade
        isCalculatorOpen: false,
        checkoutState: {
            payment_method_id: "",
            reference_number: "",
            amount_received: "",
            remarks: "",
        },

        init() {
            window.addEventListener("close-modal", (e) => {
                if (e.detail && e.detail.id === "calculator-modal") {
                    this.isCalculatorOpen = false;
                }
            });
            // Clear cart listener dispatched by Livewire on success
            window.addEventListener("sale-completed", () => {
                this.cart = [];
                this.resetCheckout();
                window.dispatchEvent(
                    new CustomEvent("close-modal", {
                        detail: { id: "checkout-modal" },
                    }),
                );
            });
        },

        get netSales() {
            return this.cart.reduce(
                (sum, item) => sum + item.price * item.quantity,
                0,
            );
        },

        get total() {
            return this.netSales; // Add global tax/discount logic here later
        },

        get change() {
            let received = parseFloat(this.checkoutState.amount_received) || 0;
            return Math.max(0, received - this.total);
        },

        get requiresReference() {
            let pm = this.paymentMethods.find(
                (p) => p.id == this.checkoutState.payment_method_id,
            );
            return pm ? pm.requires_reference : false;
        },

        setExactAmount() {
            this.checkoutState.amount_received = this.total.toFixed(2);
        },

        resetCheckout() {
            this.checkoutState = {
                payment_method_id: "",
                reference_number: "",
                amount_received: "",
                remarks: "",
            };
        },

        // CART LOGIC
        getItemQuantity(cartId) {
            const item = this.cart.find((i) => i.cartId === cartId);
            return item ? item.quantity : 0;
        },

        updateQuantity(cartId, event, maxStock) {
            let val = parseInt(event.target.value);
            if (isNaN(val) || val < 0) val = 1;
            if (val > maxStock) val = maxStock;

            event.target.value = val;
            let existingIndex = this.cart.findIndex((i) => i.cartId === cartId);

            if (existingIndex !== -1) {
                if (val === 0) this.cart.splice(existingIndex, 1);
                else this.cart[existingIndex].quantity = val;
            }
        },

        increase(
            productId,
            name,
            generic_name,
            productStock,
            packagings,
            selectedPkgId,
        ) {
            let pkg =
                packagings.find((p) => p.id == selectedPkgId) || packagings[0];
            let cartId = productId + "_" + pkg.id; // Unique ID based on product + packaging

            // Convert global stock to this packaging's capacity
            let maxAvailable = Math.floor(productStock / pkg.conversion_factor);

            let item = this.cart.find((i) => i.cartId === cartId);

            if (item) {
                if (item.quantity < maxAvailable) item.quantity++;
            } else if (maxAvailable > 0) {
                this.cart.push({
                    cartId: cartId,
                    product_id: productId,
                    packaging_id: pkg.id,
                    name: name,
                    generic_name: generic_name,
                    price: pkg.price,
                    quantity: 1,
                    maxStock: maxAvailable,
                    unit: pkg.unit,
                });
            } else {
                console.warn("Out of stock for this packaging.");
            }
        },

        decrease(cartId) {
            let item = this.cart.find((i) => i.cartId === cartId);
            if (item) {
                if (item.quantity > 1) item.quantity--;
                else this.removeItem(cartId);
            }
        },

        removeItem(cartId) {
            this.cart = this.cart.filter((i) => i.cartId !== cartId);
        },

        async clearCart() {
            const isConfirmed = await window.confirmModal(
                "Clear Cart",
                "Are you sure you want to clear the current order?",
            );
            if (isConfirmed) this.cart = [];
        },

        triggerCheckout() {
            if (this.cart.length === 0) return;
            this.resetCheckout();
            window.dispatchEvent(
                new CustomEvent("open-modal", {
                    detail: { id: "checkout-modal" },
                }),
            );
        },

        submitToBackend($wire) {
            // Build the final payload
            const payload = {
                cart: this.cart,
                payment_method_id: this.checkoutState.payment_method_id,
                amount_received: parseFloat(this.checkoutState.amount_received),
                reference_number: this.checkoutState.reference_number,
                remarks: this.checkoutState.remarks,
            };

            // Call Livewire
            $wire.submitOrder(payload);
        },

        focusSearch() {
            const searchInput = document.querySelector(
                'input[wire\\:model\\.live\\.debounce\\.300ms="search"]',
            );
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        },

        handleKeydown(e) {
            console.log(
                "Key pressed:",
                e.key,
                "Ctrl:",
                e.ctrlKey,
                "Meta:",
                e.metaKey,
            );
            if (e.key.toLowerCase() === "k" && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                this.focusSearch();
            }
            if (e.key === "F1") {
                e.preventDefault();
                this.focusSearch();
            }
            if (e.key === "F4") {
                e.preventDefault();
                this.triggerCheckout();
            }
            if (e.key.toLowerCase() === "escape") {
                e.preventDefault();
                this.clearCart();
            }
            if (e.key.toLowerCase() === "c" && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();

                // Flip the state (true becomes false, false becomes true)
                this.isCalculatorOpen = !this.isCalculatorOpen;

                // Dispatch the correct event based on the new state
                window.dispatchEvent(
                    new CustomEvent(
                        this.isCalculatorOpen ? "open-modal" : "close-modal",
                        {
                            detail: { id: "calculator-modal" },
                        },
                    ),
                );
            }
        },
    };
};

Livewire.start();
