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

window.posApp = (
    paymentMethods = [],
    customerTypes = [],
    customerMode,
    customerId,
    customers = [],
) => {
    return {
        cart: [],
        paymentMethods: paymentMethods,
        customerTypes: customerTypes,

        // Use Alpine's Entangle logic via parameters
        customerMode: customerMode,
        customerId: customerId,
        customersData: customers,

        walkInDiscountTypeId: "", // Track manual discount selection for walk-ins

        isCalculatorOpen: false,
        checkoutState: {
            payment_method_id: "",
            reference_number: "",
            amount_received: "",
            remarks: "",
        },

        init() {
            this.$watch("customerId", () => this.refreshCartPricing());
            this.$watch("customerMode", () => {
                if (this.customerMode === "customer") {
                    this.walkInDiscountTypeId = "";
                }

                this.refreshCartPricing();
            });

            window.addEventListener("close-modal", (e) => {
                if (e.detail && e.detail.id === "calculator-modal") {
                    this.isCalculatorOpen = false;
                }
            });
            // Clear cart listener dispatched by Livewire on success
            window.addEventListener("sale-completed", (e) => {
                if (e.detail?.receiptUrl) {
                    const receiptWindow = window.open(e.detail.receiptUrl, "_blank");

                    if (receiptWindow) {
                        receiptWindow.focus();
                    } else {
                        window.location.href = e.detail.receiptUrl;
                    }
                }

                this.cart = [];
                this.resetCheckout();
                window.dispatchEvent(
                    new CustomEvent("close-modal", {
                        detail: { id: "checkout-modal" },
                    }),
                );
            });
            window.addEventListener("customer-created", (e) => {
                if (!e.detail?.customer) return;

                const customer = e.detail.customer;
                const existingIndex = this.customersData.findIndex(
                    (item) => item.value == customer.value,
                );

                if (existingIndex === -1) {
                    this.customersData.push(customer);
                } else {
                    this.customersData[existingIndex] = customer;
                }

                this.customerId = customer.value;
                this.refreshCartPricing();
            });
        },

        get netSales() {
            return this.cart.reduce(
                (sum, item) => sum + item.price * item.quantity,
                0,
            );
        },

        get selectedCustomerTypeId() {
            if (this.customerMode !== "customer" || !this.customerId) {
                return null;
            }

            const customer = this.customersData.find(
                (c) => c.value == this.customerId,
            );

            return customer?.type_id ?? null;
        },

        get selectedCustomerType() {
            const customerTypeId = this.selectedCustomerTypeId;

            if (!customerTypeId) return null;

            return this.customerTypes.find((type) => type.id == customerTypeId);
        },

        get selectedCustomerTypeName() {
            return this.selectedCustomerType?.name || "";
        },

        get discountPercentage() {
            let percentage = 0;

            if (this.customerMode === "customer" && this.customerId) {
                // Find the customer, then find their type's discount
                let customer = this.customersData.find(
                    (c) => c.value == this.customerId,
                );
                if (customer && customer.type_id) {
                    let type = this.customerTypes.find(
                        (t) => t.id == customer.type_id,
                    );
                    // FIX: Divide by 100 to convert 20 into 0.20
                    if (type)
                        percentage =
                            (parseFloat(type.discount_percentage) || 0) / 100;
                }
            } else if (
                this.customerMode === "walk_in" &&
                this.walkInDiscountTypeId
            ) {
                // Use the manually selected walk-in discount
                let type = this.customerTypes.find(
                    (t) => t.id == this.walkInDiscountTypeId,
                );
                // FIX: Divide by 100 to convert 20 into 0.20
                if (type)
                    percentage =
                        (parseFloat(type.discount_percentage) || 0) / 100;
            }

            return percentage;
        },

        getPackageRegularPrice(pkg) {
            if (!pkg) return 0;

            return parseFloat(pkg.regular_price ?? pkg.price) || 0;
        },

        getPackagePartnershipPrice(pkg) {
            if (!pkg) return null;

            const customerTypeId = this.selectedCustomerTypeId;
            const partnershipPrices = pkg.partnership_prices || {};

            if (
                customerTypeId &&
                Object.prototype.hasOwnProperty.call(
                    partnershipPrices,
                    String(customerTypeId),
                )
            ) {
                return parseFloat(partnershipPrices[String(customerTypeId)]) || 0;
            }

            return null;
        },

        hasPackagePartnershipPrice(pkg) {
            return this.getPackagePartnershipPrice(pkg) !== null;
        },

        getPackagePrice(pkg) {
            const partnershipPrice = this.getPackagePartnershipPrice(pkg);

            return partnershipPrice !== null
                ? partnershipPrice
                : this.getPackageRegularPrice(pkg);
        },

        getPackagePriceSource(pkg) {
            return this.hasPackagePartnershipPrice(pkg)
                ? "Partnership"
                : "Regular";
        },

        refreshCartPricing() {
            this.cart = this.cart.map((item) => {
                const price = this.getPackagePrice(item.packaging);
                const partnershipPrice = this.getPackagePartnershipPrice(
                    item.packaging,
                );

                return {
                    ...item,
                    price,
                    regularPrice: this.getPackageRegularPrice(item.packaging),
                    partnershipPrice,
                    priceSource: this.getPackagePriceSource(item.packaging),
                };
            });
        },

        get discountAmount() {
            return this.netSales * this.discountPercentage;
        },

        get total() {
            return this.netSales - this.discountAmount;
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

        getUsedBaseStock(productId, exceptCartId = null) {
            return this.cart.reduce((sum, item) => {
                if (item.product_id != productId || item.cartId === exceptCartId) {
                    return sum;
                }

                return sum + item.quantity * item.conversionFactor;
            }, 0);
        },

        getMaxQuantityForItem(item) {
            if (item.specialOrder) return 999999;

            const remainingBase =
                item.productStock -
                this.getUsedBaseStock(item.product_id, item.cartId);

            return Math.max(
                0,
                Math.floor(remainingBase / item.conversionFactor),
            );
        },

        updateQuantity(cartId, event) {
            let val = parseInt(event.target.value);
            if (isNaN(val) || val < 0) val = 1;
            let existingIndex = this.cart.findIndex((i) => i.cartId === cartId);

            if (existingIndex !== -1) {
                const item = this.cart[existingIndex];
                const maxQuantity = this.getMaxQuantityForItem(item);

                if (val > maxQuantity) val = maxQuantity;

                event.target.value = val;

                if (val === 0) this.cart.splice(existingIndex, 1);
                else this.cart[existingIndex].quantity = val;
            }
        },

        addProductToCart(product, selectedPkgId) {
            if (!product || !Array.isArray(product.packagings)) return;

            let pkg =
                product.packagings.find((p) => p.id == selectedPkgId) ||
                product.packagings[0];

            if (!pkg) {
                console.warn("Product has no sellable packaging.", product);
                return;
            }

            const conversionFactor = parseFloat(pkg.conversion_factor) || 1;
            const productStock = parseFloat(product.stock) || 0;
            const specialOrder = product.stock_type === "special_order";
            let cartId = product.id + "_" + pkg.id; // Unique ID based on product + packaging
            const remainingBase =
                productStock - this.getUsedBaseStock(product.id, cartId);
            let maxAvailable = Math.max(
                0,
                Math.floor(remainingBase / conversionFactor),
            );

            let item = this.cart.find((i) => i.cartId === cartId);

            if (item) {
                if (specialOrder || item.quantity < maxAvailable) item.quantity++;
            } else if (specialOrder || maxAvailable > 0) {
                this.cart.push({
                    cartId: cartId,
                    product_id: product.id,
                    packaging_id: pkg.id,
                    name: product.name,
                    generic_name: product.generic_name,
                    price: this.getPackagePrice(pkg),
                    regularPrice: this.getPackageRegularPrice(pkg),
                    partnershipPrice: this.getPackagePartnershipPrice(pkg),
                    priceSource: this.getPackagePriceSource(pkg),
                    quantity: 1,
                    maxStock: maxAvailable,
                    productStock: productStock,
                    specialOrder: specialOrder,
                    conversionFactor: conversionFactor,
                    unit: pkg.unit,
                    packaging: pkg,
                });
            } else {
                console.warn("Out of stock for this packaging.");
            }
        },

        increaseQuantity(cartId) {
            let item = this.cart.find((i) => i.cartId === cartId);
            if (!item) return;

            const maxQuantity = this.getMaxQuantityForItem(item);

            if (item.quantity < maxQuantity) {
                item.quantity++;
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
                cart: this.cart.map((item) => ({
                    product_id: item.product_id,
                    packaging_id: item.packaging_id,
                    quantity: item.quantity,
                    name: item.name,
                    special_order: item.specialOrder,
                })),
                payment_method_id: this.checkoutState.payment_method_id,
                amount_received: parseFloat(this.checkoutState.amount_received),
                reference_number: this.checkoutState.reference_number,
                remarks: this.checkoutState.remarks,
                // Send the calculated discount data so the backend can verify it
                applied_discount_type_id:
                    this.customerMode === "walk_in" && this.walkInDiscountTypeId
                        ? this.walkInDiscountTypeId
                        : null,
            };

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

window.groceryPosApp = window.posApp;

window.motorShopPosApp = (
    paymentMethods = [],
    customerTypes = [],
    customerMode,
    customerId,
    customers = [],
    mechanics = [],
) => {
    return {
        cart: [],
        serviceLines: [],
        serviceDraft: {
            service_name: "",
            mechanic_id: "",
            quantity: 1,
            price: "",
            description: "",
        },
        paymentMethods: paymentMethods,
        customerTypes: customerTypes,
        customerMode: customerMode,
        customerId: customerId,
        customersData: customers,
        mechanicsData: mechanics,
        walkInDiscountTypeId: "",
        checkoutState: {
            payment_method_id: "",
            reference_number: "",
            amount_received: "",
            remarks: "",
        },

        init() {
            this.$watch("customerMode", () => {
                if (this.customerMode === "customer") {
                    this.walkInDiscountTypeId = "";
                }
            });

            window.addEventListener("sale-completed", (e) => {
                if (e.detail?.receiptUrl) {
                    const receiptWindow = window.open(e.detail.receiptUrl, "_blank");

                    if (receiptWindow) {
                        receiptWindow.focus();
                    } else {
                        window.location.href = e.detail.receiptUrl;
                    }
                }

                this.cart = [];
                this.serviceLines = [];
                this.resetCheckout();
                window.dispatchEvent(
                    new CustomEvent("close-modal", {
                        detail: { id: "checkout-modal" },
                    }),
                );
            });

            window.addEventListener("customer-created", (e) => {
                if (!e.detail?.customer) return;

                const customer = e.detail.customer;
                const existingIndex = this.customersData.findIndex(
                    (item) => item.value == customer.value,
                );

                if (existingIndex === -1) {
                    this.customersData.push(customer);
                } else {
                    this.customersData[existingIndex] = customer;
                }

                this.customerId = customer.value;
            });
        },

        get netSales() {
            return this.cart.reduce(
                (sum, item) => sum + item.price * item.quantity,
                0,
            ) + this.serviceSales;
        },

        get productSales() {
            return this.cart.reduce(
                (sum, item) => sum + item.price * item.quantity,
                0,
            );
        },

        get serviceSales() {
            return this.serviceLines.reduce(
                (sum, item) => sum + item.price * item.quantity,
                0,
            );
        },

        get orderLineCount() {
            return this.cart.length + this.serviceLines.length;
        },

        get selectedCustomerTypeId() {
            if (this.customerMode !== "customer" || !this.customerId) {
                return null;
            }

            const customer = this.customersData.find(
                (c) => c.value == this.customerId,
            );

            return customer?.type_id ?? null;
        },

        get discountPercentage() {
            let typeId = null;

            if (this.customerMode === "customer") {
                typeId = this.selectedCustomerTypeId;
            } else if (this.walkInDiscountTypeId) {
                typeId = this.walkInDiscountTypeId;
            }

            if (!typeId) return 0;

            const type = this.customerTypes.find((t) => t.id == typeId);

            return type ? (parseFloat(type.discount_percentage) || 0) / 100 : 0;
        },

        get discountAmount() {
            return this.netSales * this.discountPercentage;
        },

        get total() {
            return Math.max(0, this.netSales - this.discountAmount);
        },

        get change() {
            const received = parseFloat(this.checkoutState.amount_received) || 0;
            return Math.max(0, received - this.total);
        },

        get requiresReference() {
            const method = this.paymentMethods.find(
                (p) => p.id == this.checkoutState.payment_method_id,
            );
            return method ? method.requires_reference : false;
        },

        getPackagePrice(pkg) {
            if (!pkg) return 0;

            return parseFloat(pkg.regular_price ?? pkg.price) || 0;
        },

        getQuantityStep(item) {
            return item?.allowDecimal ? "0.01" : "1";
        },

        normalizeQuantity(value, item = null) {
            const quantity = parseFloat(value);

            if (Number.isNaN(quantity) || quantity <= 0) {
                return item?.allowDecimal ? 0.01 : 1;
            }

            if (item?.allowDecimal) {
                return Math.round(quantity * 100) / 100;
            }

            return Math.floor(quantity);
        },

        formatQuantity(value) {
            const quantity = parseFloat(value) || 0;
            return Number.isInteger(quantity)
                ? String(quantity)
                : quantity.toFixed(2).replace(/\.?0+$/, "");
        },

        getItemQuantity(cartId) {
            const item = this.cart.find((i) => i.cartId === cartId);
            return item ? item.quantity : 0;
        },

        getUsedBaseStock(productId, exceptCartId = null) {
            return this.cart.reduce((sum, item) => {
                if (item.product_id != productId || item.cartId === exceptCartId) {
                    return sum;
                }

                return sum + item.quantity * item.conversionFactor;
            }, 0);
        },

        getMaxQuantityForItem(item) {
            if (item.specialOrder) return 999999;

            const remainingBase =
                item.productStock -
                this.getUsedBaseStock(item.product_id, item.cartId);
            const maxQuantity = remainingBase / item.conversionFactor;

            if (item.allowDecimal) {
                return Math.max(0, Math.floor(maxQuantity * 100) / 100);
            }

            return Math.max(0, Math.floor(maxQuantity));
        },

        updateQuantity(cartId, event) {
            const existingIndex = this.cart.findIndex((i) => i.cartId === cartId);

            if (existingIndex === -1) return;

            const item = this.cart[existingIndex];
            let value = this.normalizeQuantity(event.target.value, item);
            const maxQuantity = this.getMaxQuantityForItem(item);

            if (value > maxQuantity) value = maxQuantity;

            event.target.value = this.formatQuantity(value);

            if (value <= 0) {
                this.cart.splice(existingIndex, 1);
            } else {
                this.cart[existingIndex].quantity = value;
            }
        },

        addProductToCart(product, selectedPkgId) {
            if (!product || !Array.isArray(product.packagings)) return;

            const pkg =
                product.packagings.find((p) => p.id == selectedPkgId) ||
                product.packagings[0];

            if (!pkg) {
                console.warn("Product has no sellable packaging.", product);
                return;
            }

            const conversionFactor = parseFloat(pkg.conversion_factor) || 1;
            const productStock = parseFloat(product.stock) || 0;
            const specialOrder = product.stock_type === "special_order";
            const cartId = product.id + "_" + pkg.id;
            const allowDecimal = Boolean(pkg.allow_decimal);
            const remainingBase =
                productStock - this.getUsedBaseStock(product.id, cartId);
            const maxAvailable = allowDecimal
                ? Math.max(0, Math.floor((remainingBase / conversionFactor) * 100) / 100)
                : Math.max(0, Math.floor(remainingBase / conversionFactor));
            const step = allowDecimal ? 0.01 : 1;
            const item = this.cart.find((i) => i.cartId === cartId);

            if (item) {
                item.quantity = specialOrder
                    ? Math.round((item.quantity + step) * 100) / 100
                    : Math.min(maxAvailable, item.quantity + step);
            } else if (specialOrder || maxAvailable > 0) {
                this.cart.push({
                    cartId: cartId,
                    product_id: product.id,
                    packaging_id: pkg.id,
                    name: product.name,
                    price: this.getPackagePrice(pkg),
                    quantity: step,
                    productStock: productStock,
                    specialOrder: specialOrder,
                    conversionFactor: conversionFactor,
                    unit: pkg.unit,
                    packaging: pkg,
                    allowDecimal: allowDecimal,
                });
            } else {
                console.warn("Out of stock for this packaging.");
            }
        },

        increaseQuantity(cartId) {
            const item = this.cart.find((i) => i.cartId === cartId);
            if (!item) return;

            const step = item.allowDecimal ? 0.01 : 1;
            const maxQuantity = this.getMaxQuantityForItem(item);

            item.quantity = Math.min(
                maxQuantity,
                Math.round((item.quantity + step) * 100) / 100,
            );
        },

        decrease(cartId) {
            const item = this.cart.find((i) => i.cartId === cartId);
            if (!item) return;

            const step = item.allowDecimal ? 0.01 : 1;
            const nextQuantity = Math.round((item.quantity - step) * 100) / 100;

            if (nextQuantity > 0) {
                item.quantity = nextQuantity;
            } else {
                this.removeItem(cartId);
            }
        },

        removeItem(cartId) {
            this.cart = this.cart.filter((i) => i.cartId !== cartId);
        },

        resetServiceDraft() {
            this.serviceDraft = {
                service_name: "",
                mechanic_id: "",
                quantity: 1,
                price: "",
                description: "",
            };
        },

        addServiceLine() {
            const serviceName = (this.serviceDraft.service_name || "").trim();
            const quantity = parseFloat(this.serviceDraft.quantity) || 0;
            const price = parseFloat(this.serviceDraft.price) || 0;

            if (!serviceName || quantity <= 0 || price < 0) {
                return false;
            }

            this.serviceLines.push({
                cartId: "service_" + Date.now() + "_" + Math.random().toString(16).slice(2),
                service_name: serviceName,
                mechanic_id: this.serviceDraft.mechanic_id || null,
                quantity: Math.round(quantity * 100) / 100,
                price: Math.round(price * 100) / 100,
                description: (this.serviceDraft.description || "").trim(),
            });

            this.resetServiceDraft();
            return true;
        },

        removeServiceLine(cartId) {
            this.serviceLines = this.serviceLines.filter((i) => i.cartId !== cartId);
        },

        getMechanicName(mechanicId) {
            const mechanic = this.mechanicsData.find((item) => item.value == mechanicId);

            return mechanic?.label || "No mechanic";
        },

        async clearCart() {
            const isConfirmed = await window.confirmModal(
                "Clear Cart",
                "Are you sure you want to clear the current order?",
            );
            if (isConfirmed) {
                this.cart = [];
                this.serviceLines = [];
            }
        },

        triggerCheckout() {
            if (this.cart.length === 0 && this.serviceLines.length === 0) return;
            this.resetCheckout();
            window.dispatchEvent(
                new CustomEvent("open-modal", {
                    detail: { id: "checkout-modal" },
                }),
            );
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

        submitToBackend($wire) {
            $wire.submitOrder({
                cart: this.cart.map((item) => ({
                    product_id: item.product_id,
                    packaging_id: item.packaging_id,
                    quantity: item.quantity,
                    name: item.name,
                    special_order: item.specialOrder,
                })),
                services: this.serviceLines.map((item) => ({
                    service_name: item.service_name,
                    mechanic_id: item.mechanic_id,
                    quantity: item.quantity,
                    price: item.price,
                    description: item.description,
                })),
                payment_method_id: this.checkoutState.payment_method_id,
                amount_received: parseFloat(this.checkoutState.amount_received),
                reference_number: this.checkoutState.reference_number,
                remarks: this.checkoutState.remarks,
                applied_discount_type_id:
                    this.customerMode === "walk_in" && this.walkInDiscountTypeId
                        ? this.walkInDiscountTypeId
                        : null,
            });
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
        },
    };
};

window.transactionManager = () => {
    return {
        selectedTx: null,

        viewTx(transaction) {
            this.selectedTx = transaction;
            console.table("Selected transaction:", this.selectedTx);
            window.dispatchEvent(
                new CustomEvent("open-modal", {
                    detail: { id: "view-transaction-modal" },
                }),
            );
        },

        formatMoney(cents) {
            if (!cents) return "₱0.00";
            return (
                "₱" +
                (cents / 100).toLocaleString("en-US", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })
            );
        },

        formatDate(dateString) {
            if (!dateString) return "";
            const d = new Date(dateString);
            return d.toLocaleDateString("en-US", {
                year: "numeric",
                month: "long",
                day: "numeric",
            });
        },

        formatTime(dateString) {
            if (!dateString) return "";
            const d = new Date(dateString);
            return d.toLocaleTimeString("en-US", {
                hour: "2-digit",
                minute: "2-digit",
            });
        },

        capitalize(str) {
            if (!str) return "";
            return str.charAt(0).toUpperCase() + str.slice(1);
        },
    };
};

Livewire.start();
