<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Product\CategoryType;
use App\Models\Branch;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

final class InventoryRedirectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Branch $branch)
    {
        $category = ProductCategory::find($branch->product_category_id);

        if (! $category) {
            abort(403, 'Unauthorized access to Inventory module.');
        }

        // 2. Redirect based on Branch product category
        return match ($category->name) {

            CategoryType::Pharmacy->value => redirect()->route('inventory.pharmacy.dashboard'),
            CategoryType::Grocery->value => redirect()->route('inventory.grocery.dashboard'),
            CategoryType::MotorShop->value => redirect()->route('inventory.motor-shop.dashboard'),

            // Others
            default => abort(403, 'Unauthorized access to Inventory module.'),
        };
    }
}
