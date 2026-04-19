<?php

declare(strict_types=1);

namespace App\Livewire\PointOfSale\Pages\Pharmacy;

use App\Actions\POS\ProcessSale as ProcessSaleAction;
use App\Data\ProcessSale\SaleData;
use App\Data\ProcessSale\SaleItemData;
use App\Enums\Sale\Status;
use App\Livewire\Concerns\HasToast;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPackaging;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.pos', ['title' => 'Point of Sale'])]
final class ProcessSale extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    // Use null for 'All' so it's easier to check
    public ?int $activeCategory = null;

    // UI State
    public string $customerMode = 'walk_in';
    public string $pricingMode = 'retail';
    public ?int $customer_id = null;
    public string $name = '';
    public ?int $customer_type_id = null;
    public ?string $id_card_number = null;
    public ?string $booklet_number = null;
    public ?string $contact_number = null;
    public ?string $address = null;

    public function setCategory(?int $categoryId = null): void
    {
        $this->activeCategory = $categoryId;
        $this->resetPage(); // Always reset to page 1 when changing filters
    }

    public function setCustomerMode(string $mode): void
    {
        $this->customerMode = $mode;

        if ($mode === 'walk_in') {
            $this->customer_id = null;
        }
    }

    public function mount(): void
    {
        $this->perPage = 50; // Default items per page for the product list
    }

    #[Computed]
    public function categories()
    {
        // Fetch all categories that have at least one active pharmacy product
        return Category::whereHas('products', function ($query) {
            $query->isPharmacy();
        })->orderBy('name')->get();
    }

    #[Computed]
    public function activePaymentMethods()
    {
        return PaymentMethod::where('is_active', true)->orderBy('name')->get(['id', 'name', 'requires_reference']);
    }

    #[Computed]
    public function products()
    {
        $query = Product::query()
            ->isPharmacy() // Only Pharmacy products
            ->where('is_active', true)
            ->where('branch_id', $this->currentBranchId)
            ->with(['productPackagings.unit', 'productPackagings.partnerships', 'baseUnit']); // Eager load baseUnit to prevent N+1

        if ($this->activeCategory !== null) {
            $query->where('category_id', $this->activeCategory);
        }

        // Calculate Stock
        $query->withSum(['inventoryBatches as total_stock' => function ($subQ) {
            $subQ->where('branch_id', $this->currentBranchId);
        }], 'quantity_on_hand');

        // Apply Search
        if (!empty($this->search)) {
            $query->search($this->search);
        }

        // Use paginate() and through() instead of get() and map()
        return $query->orderBy('brand_name')
            ->paginate($this->perPage) // Set how many items you want per page here
            ->through(function ($product) {
                // Map all packagings for Alpine
                $packagings = $product->productPackagings->map(fn($pkg) => [
                    'id' => $pkg->id,
                    'unit' => $pkg->unit->abbreviation ?? 'Unit',
                    'price' => (float) ($pkg->getRawOriginal('price') / 100),
                    'regular_price' => (float) ($pkg->getRawOriginal('price') / 100),
                    'partnership_prices' => $pkg->partnerships
                        ->where('branch_id', $this->currentBranchId)
                        ->mapWithKeys(fn ($partnership) => [
                            (string) $partnership->customer_type_id => (float) ($partnership->getRawOriginal('special_price') / 100),
                        ])
                        ->toArray(),
                    'conversion_factor' => (float) $pkg->conversion_factor,
                ])->values()->toArray();

                return (object) [
                    'id' => $product->id,
                    'name' => $product->brand_name,
                    'generic_name' => $product->generic_name,
                    'required_prescription' => $product->requires_prescription,
                    'stock' => (float) ($product->total_stock ?? 0),
                    'barcode' => $product->product_code ?? null,
                    'packagings' => $packagings, // Pass array of options
                ];
            });
    }

    #[Computed]
    public function customers()
    {
        return Customer::with('customerType')->orderBy('name')->get()->map(fn ($c) => [
            'label' => $c->name . ($c->customerType ? " ({$c->customerType->name} - {$c->customerType->discount_percentage}%)" : ''),
            'value' => $c->id,
            'type_id' => $c->customer_type_id, // Important for linking
        ]);
    }

    #[Computed]
    public function availableCustomerTypes()
    {
        return CustomerType::orderBy('name')->get()->map(fn ($type) => [
            'label' => $type->name,
            'value' => $type->id,
        ]);
    }

    #[Computed]
    public function customerTypesData()
    {
        return CustomerType::select('id', 'name', 'discount_percentage')->get();
    }

    public function saveCustomer()
    {
        // 1. Validate the modal inputs
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'customer_type_id' => ['required', 'exists:customer_types,id'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'booklet_number' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        // 2. Create the customer in the database
        $customer = Customer::create($validated);

        // 3. Auto-select the new customer for the transaction
        $this->customer_id = $customer->id;

        // 4. Clear the modal form for next time
        $this->reset([
            'name',
            'customer_type_id',
            'id_card_number',
            'booklet_number',
            'contact_number',
            'address'
        ]);

        // 5. Notify the user and close the modal
        $this->toastSuccess("Customer '{$customer->name}' created and selected!");
        $this->dispatch('customer-created', customer: [
            'label' => $customer->name . ($customer->customerType ? " ({$customer->customerType->name} - {$customer->customerType->discount_percentage}%)" : ''),
            'value' => $customer->id,
            'type_id' => $customer->customer_type_id,
        ]);
        $this->dispatch('close-modal', id: 'customer-form');
    }

    /**
     * Handles the Checkout payload from Alpine.js
     */
    public function submitOrder(array $checkoutData)
    {
        // 1. Backend Validation
        $validator = Validator::make($checkoutData, [
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'cart.*.packaging_id' => ['required', 'integer', 'exists:product_packagings,id'],
            'cart.*.quantity' => ['required', 'numeric', 'gt:0'],
            'cart.*.name' => ['nullable', 'string'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'amount_received' => ['required', 'numeric', 'min:0'],
            'applied_discount_type_id' => ['nullable', 'integer', 'exists:customer_types,id'],
            'reference_number' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            $this->toastError('Validation failed. Please check the checkout details.');
            return;
        }

        $validated = $validator->validated();

        try {
            $paymentMethod = PaymentMethod::findOrFail((int) $validated['payment_method_id']);

            if ($paymentMethod->requires_reference && empty($validated['reference_number'])) {
                throw new \Exception("Reference number is required for {$paymentMethod->name} payments.");
            }

            $customer = $this->customerMode === 'customer' && $this->customer_id
                ? Customer::with('customerType')->findOrFail($this->customer_id)
                : null;

            if ($this->customerMode === 'customer' && ! $customer) {
                throw new \Exception('Please select a customer before checking out.');
            }

            $customerTypeId = $customer?->customer_type_id;
            $itemsData = [];

            foreach ($validated['cart'] as $cartItem) {
                $remainingToDeduct = (float) $cartItem['quantity'];

                // Fetch packaging to secure the exact conversion factor and unit_id
                $packaging = ProductPackaging::query()
                    ->with('product.productCategory')
                    ->whereKey((int) $cartItem['packaging_id'])
                    ->where('product_id', (int) $cartItem['product_id'])
                    ->whereHas('product', function (Builder $query) {
                        $query->where('branch_id', $this->currentBranchId)
                            ->where('is_active', true)
                            ->isPharmacy();
                    })
                    ->firstOrFail();

                $unitId = $packaging->unit_id;
                $conversionFactor = (float) $packaging->conversion_factor;
                $regularPriceCents = (int) $packaging->getRawOriginal('price');
                $partnership = $packaging->findPartnershipForCustomer($customerTypeId, $this->currentBranchId);
                $priceCents = $partnership
                    ? (int) $partnership->getRawOriginal('special_price')
                    : $regularPriceCents;
                $priceSource = $partnership ? 'partnership' : 'regular';

                // Fetch available inventory batches (FIFO: Oldest first)
                $batches = InventoryBatch::where('product_id', $cartItem['product_id'])
                    ->where('branch_id', $this->currentBranchId)
                    ->where('quantity_on_hand', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingToDeduct <= 0) break;

                    // Calculate how much BASE quantity this specific batch needs to provide
                    $baseNeeded = $remainingToDeduct * $conversionFactor;
                    $baseTaken = min($batch->quantity_on_hand, $baseNeeded);

                    // Convert the taken base quantity back to the Packaged quantity for the DTO
                    $qtyTaken = $baseTaken / $conversionFactor;

                    // Calculate the cost of 1 unit of the SELECTED packaging
                    $unitCostCents = (int) $batch->cost_per_unit->getAmount();
                    $packagingCostCents = (int) round($unitCostCents * $conversionFactor);

                    $itemsData[] = new SaleItemData(
                        product_id: $cartItem['product_id'],
                        inventory_batch_id: $batch->id,
                        unit_id: $unitId, // Secured from backend packaging
                        quantity: $qtyTaken,
                        price_at_moment: $priceCents,
                        cost_at_moment: $packagingCostCents,
                        subtotal: (int) round($qtyTaken * $priceCents),
                        product_packaging_id: $packaging->id,
                        regular_price_at_moment: $regularPriceCents,
                        price_source: $priceSource,
                        partnership_id: $partnership?->id,
                    );

                    $remainingToDeduct -= $qtyTaken;
                }

                // If we ran out of batches before fulfilling the cart item:
                if (round($remainingToDeduct, 4) > 0) {
                    throw new \Exception('Insufficient stock in inventory for ' . ($cartItem['name'] ?? 'the selected product') . '. Another transaction may have consumed it.');
                }
            }

            $subtotalCents = collect($itemsData)->sum(fn (SaleItemData $item) => $item->subtotal);
            $requestedDiscountTypeId = ! empty($validated['applied_discount_type_id'])
                ? (int) $validated['applied_discount_type_id']
                : null;

            $discountTypeId = $this->resolveDiscountTypeId($requestedDiscountTypeId, $customer);
            $discountCents = $this->calculateDiscountAmount($subtotalCents, $discountTypeId);
            $grandTotalCents = max(0, $subtotalCents - $discountCents);
            $amountTenderedCents = (int) round($validated['amount_received'] * 100);

            if ($amountTenderedCents < $grandTotalCents) {
                throw new \Exception('Amount received is lower than the amount due.');
            }

            // 2. Prepare SaleData DTO (Convert monetary values to CENTS)
            $saleData = new SaleData(
                branch_id: $this->currentBranchId,
                user_id: $this->user->id,
                payment_method_id: (int) $validated['payment_method_id'],
                amount_tendered: $amountTenderedCents,
                change_amount: $amountTenderedCents - $grandTotalCents,
                discount_amount: $discountCents,
                customer_id: $customer?->id,
                discount_type_id: $discountTypeId,
                payment_reference: $validated['reference_number'] ?? null,
                status: Status::Completed
            );

            // 4. Execute the fully structured Action
            $action = app(ProcessSaleAction::class);
            $action->execute($saleData, $itemsData);

            // 5. Cleanup
            $this->dispatch('sale-completed');
            $this->toastSuccess('Payment processed successfully!');

        } catch (\Exception $e) {
            $this->toastError('Transaction failed: ' . $e->getMessage());
        }
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['search', 'activeCategory'];
    }

    private function resolveDiscountTypeId(?int $requestedDiscountTypeId, ?Customer $customer): ?int
    {
        if ($customer) {
            return $customer->customer_type_id;
        }

        return $requestedDiscountTypeId ?: null;
    }

    private function calculateDiscountAmount(int $subtotalCents, ?int $discountTypeId): int
    {
        if (! $discountTypeId) {
            return 0;
        }

        $percentage = (float) CustomerType::whereKey($discountTypeId)->value('discount_percentage');

        return (int) round($subtotalCents * ($percentage / 100));
    }
}
