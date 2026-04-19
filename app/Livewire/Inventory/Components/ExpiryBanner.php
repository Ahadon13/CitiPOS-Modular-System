<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Components;

use App\Enums\Product\CategoryType;
use App\Models\InventoryBatch;
use App\Traits\HasAuth;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class ExpiryBanner extends Component
{
    use HasAuth;

    // This will accept supported inventory module route prefixes.
    public string $module = 'pharmacy';

    #[Computed]
    public function expiredCount(): int
    {
        $targetCategories = match ($this->module) {
            'pharmacy' => [CategoryType::Pharmacy->value],
            'grocery' => [CategoryType::Grocery->value],
            'motor-shop' => [CategoryType::MotorShop->value],
            default => [],
        };

        return InventoryBatch::where('branch_id', $this->currentBranchId)
            ->where('quantity_on_hand', '>', 0)
            ->whereDate('expiration_date', '<=', Carbon::now()) // Only items that are actually expired
            ->whereHas('product.productCategory', function ($q) use ($targetCategories) {
                $q->whereIn('name', $targetCategories);
            })
            ->count();
    }
}
