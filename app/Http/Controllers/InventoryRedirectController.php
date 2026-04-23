<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Product\CategoryType;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Support\ModuleAccess;
use Illuminate\Http\Request;

final class InventoryRedirectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Branch $branch)
    {
        $branch->load('productCategory');
        $category = ProductCategory::find($branch->product_category_id);

        if (! $category) {
            abort(403, 'Unauthorized access to Inventory module.');
        }

        if (! ModuleAccess::canAccessBranchModule($request->user(), $branch, $category->name)) {
            abort(403, 'You are not allowed to manage this branch/module.');
        }

        ModuleAccess::setActiveBranch($request->user(), $branch);

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
