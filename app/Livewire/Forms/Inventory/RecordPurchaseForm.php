<?php

declare(strict_types=1);

namespace App\Livewire\Forms\Inventory;

use App\Actions\Inventory\ReceivePurchaseOrder;
use App\Actions\Inventory\RecordDirectPurchase;
use App\Data\Inventory\DirectPurchaseData;
use App\Data\Inventory\DirectPurchaseItemData;
use App\Data\Inventory\ReceiveItemData;
use App\Models\Purchase;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class RecordPurchaseForm extends Form
{
    public string $receive_type = 'po'; // Defaults to receiving a pending PO

    public ?int $purchase_id = null;
    public ?int $supplier_id = null;

    public array $orderItems = [];

    public function rules(): array
    {
        return [
            'receive_type' => ['required', 'in:po,direct'],
            'purchase_id'  => ['required_if:receive_type,po', 'nullable', 'exists:purchases,id'],
            'supplier_id'  => ['required_if:receive_type,direct', 'nullable', 'exists:suppliers,id'],

            // Item validations
            'orderItems' => ['required', 'array', 'min:1'],
            'orderItems.*.product_id' => ['required', 'exists:products,id'],
            'orderItems.*.unit_id' => ['required', 'exists:units,id'],
            'orderItems.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'orderItems.*.cost' => ['required', 'numeric', 'min:0'],
            'orderItems.*.batch_number' => ['nullable', 'string', 'max:255'],
            'orderItems.*.expiration_date' => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_id.required_if' => 'Please select a pending Purchase Order.',
            'supplier_id.required_if' => 'Please select a supplier for this direct delivery.',
            'orderItems.*.batch_number.required' => 'Batch number required.',
            'orderItems.*.expiration_date.required' => 'Expiration date required.',
            'orderItems.*.expiration_date.after' => 'Expiration date must be a future date.',
            'orderItems.*.quantity.min' => 'Quantity must be at least 0.01.',
            'orderItems.*.cost.min' => 'Cost cannot be negative.',
            'orderItems.*.product_id.required' => 'Please select a product for all items.',
            'orderItems.*.unit_id.required' => 'Please select a unit for all items.',
            'orderItems.*.product_id.exists' => 'The selected product does not exist.',
            'orderItems.*.unit_id.exists' => 'The selected unit does not exist.',
            'orderItems.required' => 'Please add at least one item to receive.',
            'orderItems.array' => 'Invalid format for items.',
            'orderItems.min' => 'You must add at least one item to receive.',
            'receive_type.required' => 'Receive type is required.',
            'receive_type.in' => 'Invalid receive type selected.',
            'purchase_id.exists' => 'The selected Purchase Order does not exist.',
            'supplier_id.exists' => 'The selected supplier does not exist.',
            'orderItems.*.batch_number.max' => 'The batch number is too long.',
            'orderItems.*.expiration_date.date' => 'Please enter a valid expiration date.',
        ];
    }

    public function store(int $branchId, int $userId): Purchase
    {
        $this->validate();

        // BRANCH 1: DIRECT RECEIVING (NO PO)
        if ($this->receive_type === 'direct') {

            $purchaseData = new DirectPurchaseData(
                branch_id: $branchId,
                supplier_id: (int) $this->supplier_id,
                user_id: $userId,
            );

            $itemsData = collect($this->orderItems)->map(function ($item) {
                return new DirectPurchaseItemData(
                    product_id: (int) $item['product_id'],
                    unit_id: (int) $item['unit_id'],
                    quantity: (float) $item['quantity'],
                    cost: (int) round((float) $item['cost'] * 100), // convert to cents
                    batch_number: $item['batch_number'],
                    expiration_date: $item['expiration_date'],
                );
            })->all();

            $purchase = app(RecordDirectPurchase::class)->execute($purchaseData, $itemsData);

        }
        // BRANCH 2: RECEIVING AN EXISTING PO
        else {

            $itemsData = collect($this->orderItems)->map(function ($item) {
                return new ReceiveItemData(
                    purchase_item_id: (int) $item['purchase_item_id'], // Crucial for updating the existing record!
                    product_id: (int) $item['product_id'],
                    actual_unit_id: (int) $item['unit_id'],
                    actual_quantity: (float) $item['quantity'],
                    actual_cost: (int) round((float) $item['cost'] * 100),
                    batch_number: $item['batch_number'],
                    expiration_date: $item['expiration_date'],
                );
            })->all();

            $purchase = app(ReceivePurchaseOrder::class)->execute((int) $this->purchase_id, $itemsData);
        }

        // Reset the form state for the next entry
        $this->reset(['purchase_id', 'supplier_id', 'orderItems']);
        if ($this->receive_type === 'direct') {
            $this->orderItems = [['product_id' => '', 'unit_id' => '', 'quantity' => 1, 'cost' => 0, 'batch_number' => '', 'expiration_date' => '']];
        }

        return $purchase;
    }
}
