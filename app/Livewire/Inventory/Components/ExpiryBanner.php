<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Components;

use App\Models\InventoryBatch;
use App\Traits\HasAuth;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class ExpiryBanner extends Component
{
    use HasAuth;

    // This will accept 'pharmacy' or 'grocery' when you call the component
    public string $module = 'pharmacy';

    #[Computed]
    public function expiredCount(): int
    {
        // Define which categories belong to which module
        $targetCategories = $this->module === 'pharmacy'
            ? ['Pharmacy', 'Medicine']
            : ['Grocery', 'Food', 'Beverage']; // Adjust these to match your actual grocery categories

        return InventoryBatch::where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0)
            ->whereDate('expiration_date', '<=', Carbon::now()) // Only items that are actually expired
            ->whereHas('product.productCategory', function ($q) use ($targetCategories) {
                $q->whereIn('name', $targetCategories);
            })
            ->count();
    }
}