<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy\Purchase;

use App\Enums\Product\CategoryType;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\Inventory\RecordPurchaseForm;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Traits\HasAuth;
use Illuminate\Testing\Fluent\Concerns\Has;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Record Purchase', 'inventory' => true])]
final class RecordPurchase extends Component
{
    use HasAuth, HasToast;

    public RecordPurchaseForm $form;

    /**
     * When the user switches between 'po' and 'direct', reset the arrays.
     */
    public function updatedFormReceiveType(): void
    {
        $this->form->purchase_id = null;
        $this->form->supplier_id = null;
        $this->form->orderItems = [];

        // If direct, give them one blank row to start
        if ($this->form->receive_type === 'direct') {
            $this->form->orderItems = [
                ['product_id' => '', 'unit_id' => '', 'quantity' => 1, 'cost' => 0, 'batch_number' => '', 'expiration_date' => '']
            ];
        }
    }

    /**
     * When the user selects a PO, fetch its items and pre-fill the form!
     */
    public function updatedFormPurchaseId($value): void
    {
        if (! $value) {
            $this->form->orderItems = [];
            return;
        }

        $purchase = Purchase::with('purchaseItems.product')->find($value);

        if ($purchase) {
            $this->form->orderItems = $purchase->purchaseItems->map(function ($item) {
                return [
                    'purchase_item_id' => $item->id,
                    'product_id'       => $item->product_id,
                    'product_name'     => $item->product->brand_name . ' (' . $item->product->dosage . ')' . ' - ' . $item->product->generic_name,
                    'unit_id'          => $item->unit_id,
                    'quantity'         => (float) $item->quantity_ordered,
                    'cost'             => (int) $item->getRawOriginal('cost_per_unit') / 100,
                    'batch_number'     => '',
                    'expiration_date'  => '',
                ];
            })->toArray();
        }
    }

    public function save(array $items): void
    {
        try {
            // Intercept the Alpine payload
            $this->form->orderItems = $items;

            // Execute the action (passing the Branch ID and User ID)
            $purchase = $this->form->store($this->currentBranchId, $this->user->id);

            $this->toastSuccess("Purchase {$purchase->reference_no} recorded and inventory updated!");

            // Redirect back to purchases list
            // return $this->redirectRoute('inventory.Invetory.index', navigate: true);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->toastError('Please fix the errors in the form before submitting.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to record purchase: ' . $e->getMessage());
        }
    }

    // --- Computed Options for the Dropdowns ---

    #[Computed]
    public function pendingPurchases(): array
    {
        return Purchase::where('branch_id', $this->currentBranchId)
            ->whereIn('status', ['pending', 'receiving'])

            // check the category name
            ->whereHas('purchaseItems.product.productCategory', function ($query) {
                // Adjust 'name' if your database column is different (e.g., 'slug' => 'pharmacy')
                $query->where('name', CategoryType::Pharmacy->value);
            })

            ->with('supplier') // Eager load supplier to prevent N+1 queries in the map() below
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($po) => [
                'value' => $po->id,
                'label' => $po->reference_no . ' (' . ($po->supplier->name ?? 'Unknown') . ')'
            ])
            ->toArray();
    }

    #[Computed]
    public function suppliers(): array
    {
        return Supplier::orderBy('name')->get()
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name])
            ->toArray();
    }

    #[Computed]
    public function products(): array
    {
        return Product::where('branch_id', $this->currentBranchId)->orderBy('brand_name')->whereHas('productCategory', function ($query) {
            $query->where('name', CategoryType::Pharmacy->value);
        })->get()
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->brand_name . ' (' . $p->dosage . ')' . ' - ' . $p->generic_name])
            ->toArray();
    }

    #[Computed]
    public function units(): array
    {
        return Unit::orderBy('name')->get()
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
            ->toArray();
    }
}
