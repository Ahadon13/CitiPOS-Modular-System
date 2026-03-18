<?php

declare(strict_types=1);

namespace App\Livewire\PointOfSale\Pages\Pharmacy;

use App\Actions\Inventory\DeductInventoryBatch;
use App\Livewire\Concerns\HasToast;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Category;
use App\Models\InventoryBatch;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Illuminate\Support\Facades\DB;
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
            ->with(['productPackagings.unit', 'baseUnit']); // Eager load baseUnit to prevent N+1

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
        return Customer::orderBy('name')->get()->map(fn ($c) => [
            'label' => $c->name,
            'value' => $c->id,
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
        $this->dispatch('close-modal', id: 'customer-form');
    }

    /**
     * Complete Order Processing Logic
     */
    public function submitOrder(array $checkoutData)
    {
        $cart = $checkoutData['cart'];

        if (empty($cart)) {
            $this->toastError('Cart is empty.');
            return;
        }

        try {
            DB::beginTransaction();

            // 1. Calculate Discounts (e.g., 20% for PWD/Senior)
            $discountPercentage = 0;
            if ($this->customerMode === 'customer' && $this->customer_id) {
                $customer = Customer::with('customerType')->find($this->customer_id);
                $type = strtolower($customer->customerType->name ?? '');

                if (in_array($type, ['pwd', 'senior citizen', 'senior'])) {
                    $discountPercentage = 0.20; // 20% discount
                }
            }

            $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
            $discountAmount = $subtotal * $discountPercentage;
            $grandTotal = $subtotal - $discountAmount;

            // 2. TODO: Create your Transaction/Sale record here
            // $sale = Sale::create(['total' => $grandTotal, 'customer_id' => $this->customer_id, ... ]);

            $deductAction = app(DeductInventoryBatch::class);

            // 3. Loop through cart items and deduct inventory
            foreach ($cart as $item) {
                $remainingToDeduct = (float) $item['quantity'];

                // Find active batches for this product in this branch using FIFO (Oldest first)
                $batches = InventoryBatch::where('product_id', $item['product_id'])
                    ->where('branch_id', $this->currentBranchId)
                    ->where('quantity_on_hand', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($batches as $batch) {
                    if ($remainingToDeduct <= 0) break;

                    // Deduct what we can from this batch
                    $deductFromThisBatch = min($batch->quantity_on_hand, $remainingToDeduct);

                    // Call your action safely
                    $deductAction->execute(
                        $batch->id,
                        $item['product_id'],
                        $item['unit_id'], // Requires unit_id logic in your DB, mapped in cart
                        $deductFromThisBatch
                    );

                    // Create SaleItem Record here if needed...
                    // $sale->items()->create([...])

                    $remainingToDeduct -= $deductFromThisBatch;
                }

                if ($remainingToDeduct > 0) {
                    throw new \Exception("Insufficient total stock to fulfill {$item['name']}");
                }
            }

            DB::commit();

            // Tell frontend to clear cart and close modal
            $this->dispatch('sale-completed');
            $this->toastSuccess('Payment processed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->toastError('Transaction failed: ' . $e->getMessage());
        }
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['search', 'activeCategory'];
    }
}
