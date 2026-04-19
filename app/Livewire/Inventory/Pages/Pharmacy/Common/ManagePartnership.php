<?php

namespace App\Livewire\Inventory\Pages\Pharmacy\Common;

use App\Models\Branch;
use App\Models\CustomerType;
use App\Models\Partnership;
use App\Models\ProductPackaging;
use App\Livewire\Concerns\HasToast;
use App\Traits\HasAuth;
use Livewire\Component;

class ManagePartnership extends Component
{
    use HasToast, HasAuth;

    public ProductPackaging $packaging;
    public $branches;
    public $customerTypes;

    // The currently selected branch filter
    public ?int $selectedBranchId = null;

    // Holds the price inputs from the UI, keyed by CustomerType ID
    public array $prices = [];

    public function mount(ProductPackaging $packaging): void
    {
        $this->packaging = $packaging;

        $this->customerTypes = CustomerType::orderBy('name')->get();

        // Fetch existing mandated prices automatically for the user's active branch
        $existingPrices = Partnership::where('product_packaging_id', $this->packaging->id)
            ->where('branch_id', $this->currentBranchId)
            ->get();

        foreach ($existingPrices as $partnership) {
            // Extract the raw cents from the Money object, then convert to a decimal for the UI
            $rawCents = (int) $partnership->special_price->getAmount();
            $this->prices[$partnership->customer_type_id] = $rawCents / 100;
        }
    }

    public function savePrices(): void
    {
        foreach ($this->prices as $customerTypeId => $price) {
            // If the admin left the input blank, delete any existing partnership price
            // This tells the system to revert back to the regular retail price.
            if ($price === '' || $price === null) {
                Partnership::where('product_packaging_id', $this->packaging->id)
                           ->where('branch_id', $this->currentBranchId) // Locked to their branch
                           ->where('customer_type_id', $customerTypeId)
                           ->delete();
                continue;
            }

            // Convert the peso input into cents (e.g., 7.50 -> 750)
            $priceInCents = (int) round(((float) $price) * 100);

            // Create or Update the price in the pivot table
            Partnership::updateOrCreate(
                [
                    'branch_id' => $this->currentBranchId, // Locked to their branch
                    'product_packaging_id' => $this->packaging->id,
                    'customer_type_id' => $customerTypeId,
                ],
                [
                    'special_price' => $priceInCents,
                ]
            );
        }

        $this->toastSuccess('Partnership prices updated successfully!');
    }
}