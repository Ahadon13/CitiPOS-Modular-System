<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\MotorShop\Purchase;

use App\Actions\Inventory\CancelPurchaseOrder;
use App\Actions\Inventory\ReceivePurchaseOrder;
use App\Data\Inventory\ReceiveItemData;
use App\Livewire\Concerns\HasToast;
use App\Models\Unit;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Component;

final class ReceivePurchaseModal extends Component
{
    use HasToast;

    #[Modelable]
    public array|null $selected_purchase = null;
    public string $referenceNo = '';
    public string $supplierName = '';
    public array $receiveItems = [];

    #[Computed]
    public function units(): array
    {
        return Unit::orderBy('name')->get()
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
            ->toArray();
    }

    public function submit(ReceivePurchaseOrder $receiveAction): void
    {
        if (empty($this->selected_purchase)) {
            return;
        }

        $this->validate([
            'receiveItems' => ['required', 'array', 'min:1'],
            'receiveItems.*.actual_unit_id' => ['required', 'exists:units,id'],
            'receiveItems.*.actual_quantity' => ['required', 'numeric', 'min:0.01'],
            'receiveItems.*.actual_cost' => ['required', 'numeric', 'min:0'],
            'receiveItems.*.batch_number' => ['nullable', 'string', 'max:255'],
            'receiveItems.*.expiration_date' => ['nullable', 'date', 'after:today'],
        ], [
            'receiveItems.required' => 'Please add at least one item to receive.',
            'receiveItems.*.actual_unit_id.required' => 'Please select the actual unit for all items.',
            'receiveItems.*.actual_unit_id.exists' => 'The selected unit does not exist.',
            'receiveItems.*.actual_quantity.required' => 'Please enter the actual quantity received for all items.',
            'receiveItems.*.actual_quantity.min' => 'The actual quantity must be at least 0.01.',
            'receiveItems.*.actual_cost.required' => 'Please enter the actual cost for all items.',
            'receiveItems.*.actual_cost.min' => 'The actual cost cannot be negative.',
            'receiveItems.*.batch_number.max' => 'The batch number is too long.',
            'receiveItems.*.expiration_date.date' => 'Please enter a valid expiration date for all items.',
            'receiveItems.*.expiration_date.after' => 'The expiration date must be a future date.',
        ]);

        $itemsData = collect($this->receiveItems)->map(function ($item) {
            return new ReceiveItemData(
                purchase_item_id: (int) $item['purchase_item_id'],
                product_id:       (int) $item['product_id'],
                actual_unit_id:   (int) $item['actual_unit_id'],
                actual_quantity:  (float) $item['actual_quantity'],
                actual_cost:      (int) round((float) $item['actual_cost'] * 100),
                batch_number:     $item['batch_number'] ?? null,
                expiration_date:  $item['expiration_date'] ?: null,
            );
        })->all();

        try {
            $purchase = $receiveAction->execute($this->selected_purchase['id'], $itemsData);

            $this->toastSuccess(content: "PO {$purchase->reference_no} has been received successfully!");

            // Mirroring your Offer modal closure logic
            $this->dispatch('close-modal', id: 'receive-purchase');

            // Refresh parent table
            $this->dispatch('page-reset');

        } catch (\Exception $e) {
            $this->toastError(content: 'Failed to receive order: ' . $e->getMessage());
        }
    }

    public function cancelOrder(CancelPurchaseOrder $cancelAction): void
    {
        if (empty($this->selected_purchase)) {
            return;
        }

        try {
            $purchase = $cancelAction->execute($this->selected_purchase['id']);

            $this->toastSuccess(content: "PO {$purchase->reference_no} has been cancelled.");

            // Close the modal and refresh the parent table
            $this->dispatch('close-modal', id: 'receive-purchase');
            $this->dispatch('page-reset');

        } catch (\Exception $e) {
            $this->toastError(content: 'Failed to cancel order: ' . $e->getMessage());
        }
    }
}
