<?php

namespace App\Livewire\Forms\Inventory;

use App\Actions\Inventory\CreatePurchaseOrder;
use App\Data\Inventory\PurchaseData;
use App\Data\Inventory\PurchaseItemData;
use App\Enums\Purchase\Status;
use Livewire\Form;

class PurchaseOrderForm extends Form
{
    public ?int $supplier_id = null;
    public ?string $expected_delivery_date = null;

    public array $orderItems = [
        ['product_id' => '', 'unit_id' => '', 'quantity' => 1, 'cost' => 0]
    ];

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:today'],
            'orderItems' => ['required', 'array', 'min:1'],
            'orderItems.*.product_id' => ['required', 'exists:products,id'],
            'orderItems.*.unit_id' => ['required', 'exists:units,id'],
            'orderItems.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'orderItems.*.cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier for this purchase order.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'orderItems.required' => 'Please add at least one product to the order.',
            'orderItems.array' => 'Invalid format for order items.',
            'orderItems.min' => 'You must add at least one product to the order.',
            'orderItems.*.product_id.required' => 'Please select a product for all rows.',
            'orderItems.*.unit_id.required' => 'Please select an order unit for all rows.',
            'expected_delivery_date.date' => 'Please enter a valid expected delivery date.',
            'expected_delivery_date.after_or_equal' => 'The expected delivery date must be a date after or equal to today.',
        ];
    }

    public function store(int $branchId, int $userId)
    {
        // 1. Run the rules() above
        $this->validate();

        // 2. Map Header DTO
        $purchaseData = new PurchaseData(
            branch_id: $branchId,
            supplier_id: $this->supplier_id,
            user_id: $userId,
            expected_delivery_date: $this->expected_delivery_date ?: null,
            status: Status::Pending // Starts as pending, stock is not updated yet!
        );

        // 3. Map Items DTO array
        $itemsData = collect($this->orderItems)->map(function ($item) {
            $costCents = (int) round((float)$item['cost'] * 100);
            $qty = (float) $item['quantity'];
            $subtotalCents = (int) round($qty * $costCents);

            return new PurchaseItemData(
                product_id: (int) $item['product_id'],
                unit_id: (int) $item['unit_id'],
                quantity_ordered: $qty,
                cost_per_unit: $costCents,
                subtotal: $subtotalCents
            );
        })->all();

        // 4. Resolve the Action and Execute
        $createPoAction = app(CreatePurchaseOrder::class);
        $purchase = $createPoAction->execute($purchaseData, $itemsData);

        // 5. Reset the form state for the next use
        $this->reset(['supplier_id', 'expected_delivery_date']);
        $this->orderItems = [['product_id' => '', 'unit_id' => '', 'quantity' => 1, 'cost' => 0]];

        return $purchase;
    }
}
