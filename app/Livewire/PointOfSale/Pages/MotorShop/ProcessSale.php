<?php

declare(strict_types=1);

namespace App\Livewire\PointOfSale\Pages\MotorShop;

use App\Actions\POS\ProcessSale as ProcessSaleAction;
use App\Data\ProcessSale\MotorShopServiceItemData;
use App\Data\ProcessSale\SaleData;
use App\Data\ProcessSale\SaleItemData;
use App\Enums\Role;
use App\Enums\Sale\Status;
use App\Livewire\Concerns\HasToast;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductPackaging;
use App\Models\User;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.pos', ['title' => 'Motor Shop Point of Sale'])]
final class ProcessSale extends Component
{
    use HasAuth, HasToast, HasDataTable, WithPagination;

    public ?int $activeCategory = null;
    public string $customerMode = 'walk_in';
    public ?int $customer_id = null;
    public string $name = '';
    public ?int $customer_type_id = null;
    public ?string $id_card_number = null;
    public ?string $booklet_number = null;
    public ?string $contact_number = null;
    public ?string $address = null;

    public function mount(): void
    {
        $this->perPage = 50;
    }

    public function setCategory(?int $categoryId = null): void
    {
        $this->activeCategory = $categoryId;
        $this->resetPage();
    }

    public function setCustomerMode(string $mode): void
    {
        $this->customerMode = $mode;
    }

    #[Computed]
    public function categories()
    {
        return Category::whereHas('products', function (Builder $query) {
            $query->where('branch_id', $this->currentBranchId)
                ->where('is_active', true)
                ->isMotorShop();
        })->orderBy('name')->get();
    }

    #[Computed]
    public function activePaymentMethods()
    {
        return PaymentMethod::where('is_active', true)->orderBy('name')->get(['id', 'name', 'requires_reference']);
    }

    #[Computed]
    public function mechanics()
    {
        return User::query()
            ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', [
                Role::ChiefMechanic->value,
                Role::Mechanic->value,
            ]))
            ->where(function (Builder $query) {
                $query->where('branch_id', $this->currentBranchId)
                    ->orWhereHas('accessibleBranches', fn (Builder $branchQuery) => $branchQuery->whereKey($this->currentBranchId));
            })
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'value' => $user->id,
                'label' => $user->name,
            ]);
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->isMotorShop()
            ->where('is_active', true)
            ->where('branch_id', $this->currentBranchId)
            ->with(['productPackagings.unit', 'baseUnit'])
            ->when($this->activeCategory !== null, fn (Builder $query) => $query->where('category_id', $this->activeCategory))
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->where('branch_id', $this->currentBranchId);
            }], 'quantity_on_hand')
            ->when(! empty($this->search), fn (Builder $query) => $query->search($this->search))
            ->orderBy('brand_name')
            ->paginate($this->perPage)
            ->through(function (Product $product) {
                $packagings = $product->productPackagings->map(fn (ProductPackaging $pkg) => [
                    'id' => $pkg->id,
                    'unit' => $pkg->unit->abbreviation ?? 'Unit',
                    'allow_decimal' => (bool) ($pkg->unit->allow_decimal ?? false),
                    'price' => (float) ($pkg->getRawOriginal('price') / 100),
                    'regular_price' => (float) ($pkg->getRawOriginal('price') / 100),
                    'conversion_factor' => (float) $pkg->conversion_factor,
                    'barcode' => $pkg->barcode,
                ])->values()->toArray();

                return (object) [
                    'id' => $product->id,
                    'name' => $product->brand_name,
                    'stock' => (float) ($product->total_stock ?? 0),
                    'barcode' => $product->product_code ?? null,
                    'base_unit' => $product->baseUnit->abbreviation ?? 'pcs',
                    'packagings' => $packagings,
                ];
            });
    }

    #[Computed]
    public function customers()
    {
        return Customer::with('customerType')->orderBy('name')->get()->map(fn (Customer $customer) => [
            'label' => $customer->name . ($customer->customerType ? " ({$customer->customerType->name} - {$customer->customerType->discount_percentage}%)" : ''),
            'value' => $customer->id,
            'type_id' => $customer->customer_type_id,
        ]);
    }

    #[Computed]
    public function availableCustomerTypes()
    {
        return CustomerType::orderBy('name')->get()->map(fn (CustomerType $type) => [
            'label' => $type->name,
            'value' => $type->id,
        ]);
    }

    #[Computed]
    public function customerTypesData()
    {
        return CustomerType::select('id', 'name', 'discount_percentage')->get();
    }

    public function saveCustomer(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'customer_type_id' => ['required', 'exists:customer_types,id'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'booklet_number' => ['nullable', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = Customer::create($validated);
        $this->customer_id = $customer->id;

        $this->reset(['name', 'customer_type_id', 'id_card_number', 'booklet_number', 'contact_number', 'address']);

        $this->toastSuccess("Customer '{$customer->name}' created and selected!");
        $this->dispatch('customer-created', customer: [
            'label' => $customer->name . ($customer->customerType ? " ({$customer->customerType->name} - {$customer->customerType->discount_percentage}%)" : ''),
            'value' => $customer->id,
            'type_id' => $customer->customer_type_id,
        ]);
        $this->dispatch('close-modal', id: 'customer-form');
    }

    public function submitOrder(array $checkoutData): void
    {
        $validator = Validator::make($checkoutData, [
            'cart' => ['nullable', 'array'],
            'cart.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'cart.*.packaging_id' => ['required', 'integer', 'exists:product_packagings,id'],
            'cart.*.quantity' => ['required', 'numeric', 'gt:0'],
            'cart.*.name' => ['nullable', 'string'],
            'services' => ['nullable', 'array'],
            'services.*.service_name' => ['required', 'string', 'max:255'],
            'services.*.mechanic_id' => ['nullable', 'integer', 'exists:users,id'],
            'services.*.quantity' => ['required', 'numeric', 'gt:0'],
            'services.*.price' => ['required', 'numeric', 'min:0'],
            'services.*.description' => ['nullable', 'string', 'max:1000'],
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
            $cartItems = $validated['cart'] ?? [];
            $serviceItems = $validated['services'] ?? [];

            if (count($cartItems) === 0 && count($serviceItems) === 0) {
                throw new \Exception('Please add at least one product or service before checkout.');
            }

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

            $itemsData = [];

            foreach ($cartItems as $cartItem) {
                $remainingToDeduct = (float) $cartItem['quantity'];

                $packaging = ProductPackaging::query()
                    ->with('product.productCategory')
                    ->whereKey((int) $cartItem['packaging_id'])
                    ->where('product_id', (int) $cartItem['product_id'])
                    ->whereHas('product', function (Builder $query) {
                        $query->where('branch_id', $this->currentBranchId)
                            ->where('is_active', true)
                            ->isMotorShop();
                    })
                    ->firstOrFail();

                $unitId = $packaging->unit_id;
                $conversionFactor = (float) $packaging->conversion_factor;
                $priceCents = (int) $packaging->getRawOriginal('price');

                $batches = InventoryBatch::where('product_id', $cartItem['product_id'])
                    ->where('branch_id', $this->currentBranchId)
                    ->where('quantity_on_hand', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingToDeduct <= 0) {
                        break;
                    }

                    $baseNeeded = $remainingToDeduct * $conversionFactor;
                    $baseTaken = min((float) $batch->quantity_on_hand, $baseNeeded);
                    $qtyTaken = $baseTaken / $conversionFactor;
                    $unitCostCents = (int) $batch->cost_per_unit->getAmount();
                    $packagingCostCents = (int) round($unitCostCents * $conversionFactor);

                    $itemsData[] = new SaleItemData(
                        product_id: (int) $cartItem['product_id'],
                        inventory_batch_id: $batch->id,
                        unit_id: $unitId,
                        quantity: $qtyTaken,
                        price_at_moment: $priceCents,
                        cost_at_moment: $packagingCostCents,
                        subtotal: (int) round($qtyTaken * $priceCents)
                    );

                    $remainingToDeduct -= $qtyTaken;
                }

                if (round($remainingToDeduct, 4) > 0) {
                    throw new \Exception('Insufficient stock in inventory for ' . ($cartItem['name'] ?? 'the selected product') . '. Another transaction may have consumed it.');
                }
            }

            $serviceItemsData = collect($serviceItems)
                ->map(function (array $service): MotorShopServiceItemData {
                    $quantity = (float) $service['quantity'];
                    $priceCents = (int) round(((float) $service['price']) * 100);

                    return new MotorShopServiceItemData(
                        service_name: trim((string) $service['service_name']),
                        quantity: $quantity,
                        price_at_moment: $priceCents,
                        subtotal: (int) round($quantity * $priceCents),
                        mechanic_id: ! empty($service['mechanic_id']) ? (int) $service['mechanic_id'] : null,
                        description: $service['description'] ?? null,
                    );
                })
                ->all();

            $subtotalCents = collect($itemsData)->sum(fn (SaleItemData $item) => $item->subtotal);
            $serviceSubtotalCents = collect($serviceItemsData)->sum(fn (MotorShopServiceItemData $item) => $item->subtotal);
            $subtotalCents += $serviceSubtotalCents;
            $requestedDiscountTypeId = ! empty($validated['applied_discount_type_id'])
                ? (int) $validated['applied_discount_type_id']
                : null;
            $discountTypeId = $this->resolveDiscountTypeId($requestedDiscountTypeId, $customer);
            $discountCents = $this->calculateDiscountAmount($subtotalCents, $discountTypeId);
            $grandTotalCents = max(0, $subtotalCents - $discountCents);
            $amountTenderedCents = (int) round(((float) $validated['amount_received']) * 100);

            if ($amountTenderedCents < $grandTotalCents) {
                throw new \Exception('Amount received is lower than the amount due.');
            }

            $sale = app(ProcessSaleAction::class)->execute(new SaleData(
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
            ), $itemsData, $serviceItemsData);

            $this->dispatch('sale-completed', receiptUrl: route('pos.motor-shop.sales.receipt', $sale));
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
        return $customer?->customer_type_id ?: $requestedDiscountTypeId;
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
