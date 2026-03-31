<?php

namespace App\Livewire\Inventory\Pages\Pharmacy\Purchase;

use App\Enums\Product\CategoryType;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Traits\HasAuth;
use App\Livewire\Concerns\HasToast;
use App\Livewire\Forms\Inventory\PurchaseOrderForm;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Create Purchase Order', 'inventory' => true])]
class CreatePurchase extends Component
{
    use HasAuth, HasToast;

    public PurchaseOrderForm $form;

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
        return Product::orderBy('brand_name')->whereHas('productCategory', function ($query) {
            $query->where('name', CategoryType::Pharmacy->label());
        })->get()
            ->map(fn($p) => ['value' => $p->id, 'label' => $p->brand_name . ' (' . $p->generic_name . ')' . ' - ' . $p->dosage])
            ->toArray();
    }

    #[Computed]
    public function units(): array
    {
        return Unit::orderBy('name')->get()
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
            ->toArray();
    }

    public function save(): void
    {
        try {
            // Pass the contextual Auth data down to the form
            $purchase = $this->form->store($this->currentBranchId, $this->user->id);

            $this->toastSuccess("Purchase Order {$purchase->reference_no} created successfully!");

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Livewire automatically handles displaying validation errors,
            // but we can throw a generic toast so the user knows to look for the red text.
            $this->toastError('Please fix the errors in the form before submitting.');
            throw $e;
        } catch (\Exception $e) {
            $this->toastError('Failed to create order: ' . $e->getMessage());
        }
    }
}
