<?php

namespace App\Livewire\Inventory\Pages\Pharmacy\Product;

use App\Actions\Inventory\AdjustStock;
use App\Livewire\Concerns\HasToast; // Assuming you use this trait based on your snippet
use App\Models\InventoryBatch;
use App\Models\Product;
use App\Traits\HasAuth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\On;
use Livewire\Component;

final class AdjustStockModal extends Component
{
    use HasToast, HasAuth;

    // Receive the full JSON array from the parent table
    #[Modelable]
    public array|null $adjust_product = null;

    public string $adjustment_type = 'deduct';
    public string $quantity = '1';
    public string $reason = '';

    // Deduct fields
    public ?int $selected_batch_id = null;

    // Add fields
    public string $new_batch_number = '';
    public string $new_expiry_date = '';

    #[On('open-adjust-stock-modal')]
    public function loadModal(int $id): void
    {
        $product = Product::with('baseUnit')->findOrFail($id);

        $this->adjust_product = [
            'id' => $product->id,
            'brand_name' => $product->brand_name,
            'generic_name' => $product->generic_name,
            'base_unit' => $product->baseUnit->abbreviation ?? 'pcs',
        ];

        $this->resetFormState();

        // Tell the frontend to open the modal
        $this->dispatch('open-modal', id: 'adjust-stock');
    }

    // Fetch live active batches securely from DB based on the selected product ID
    #[Computed]
    public function activeBatches(): array
    {
        if (empty($this->adjust_product)) {
            return [];
        }

        return InventoryBatch::where('product_id', $this->adjust_product['id'])
            ->where('branch_id', $this->currentBranchId) // Use your current branch ID trait
            ->where('quantity_on_hand', '>', 0)
            ->orderBy('expiration_date', 'asc')
            ->get()
            ->map(function ($batch) {
                return [
                    'value' => $batch->id, // Use 'value' to match your x-ui-select setup
                    'label' => "Batch: {$batch->batch_number} - Exp: " . \Carbon\Carbon::parse($batch->expiration_date)->format('M d, Y') . " ({$batch->quantity_on_hand} left)",
                ];
            })->toArray();
    }

    public function submit(AdjustStock $adjustAction): void
    {
        if (empty($this->adjust_product)) {
            return;
        }

        $rules = [
            'adjustment_type' => 'required|in:add,deduct',
            'quantity' => 'required|numeric|min:0.01|max:999999',
        ];

        if ($this->adjustment_type === 'deduct') {
            $rules['selected_batch_id'] = 'required|integer';
        } else {
            $rules['new_batch_number'] = 'required|string|max:255';
            $rules['new_expiry_date'] = 'required|date';
        }

        $this->validate($rules);

        try {
            if(!$adjustAction->execute(
                branchId: $this->currentBranchId, // Replace with your current branch ID trait
                productId: $this->adjust_product['id'],
                type: $this->adjustment_type,
                quantity: (float) $this->quantity,
                batchId: $this->selected_batch_id,
                batchNumber: $this->new_batch_number,
                expiryDate: $this->new_expiry_date,
            )){
                $this->toastError('Stock adjustment failed. Please try again.');
                return;
            }

            $this->toastSuccess("Stock adjusted successfully for {$this->adjust_product['brand_name']}");

            $this->dispatch('close-modal', id: 'adjust-stock');
            $this->resetFormState();
            $this->dispatch('page-reset'); // Refresh the parent table

        } catch (\Exception $e) {
            $this->toastError('Adjustment failed: ' . $e->getMessage());
        }
    }

    public function resetFormState(): void
    {
        $this->adjustment_type = 'deduct';
        $this->quantity = '1';
        $this->reason = '';
        $this->selected_batch_id = null;
        $this->new_batch_number = 'ADJ-' . now()->format('Ymd');
        $this->new_expiry_date = '';
        $this->resetValidation();
    }
}
