<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use App\Models\ProductPackaging;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns a scanned code into the product packaging it identifies.
 *
 * Two things can carry a barcode in this system, so both are honoured:
 *   1. `product_packagings.barcode` -- the usual case, and the precise one,
 *      because a box and a single piece of the same product scan differently.
 *   2. `products.product_code` -- a fallback for products barcoded at the
 *      product level, which resolves to that product's base packaging.
 *
 * Lookups are always scoped to a branch and a module scope, so a scanner on
 * the pharmacy counter can never ring up a grocery item from another branch.
 */
final class BarcodeResolver
{
    /**
     * @param  callable(Builder): Builder  $moduleScope  applies the caller's
     *                                                   module filter (isPharmacy(), isGrocery(), ...)
     */
    public static function resolve(string $code, int $branchId, callable $moduleScope): ?ProductPackaging
    {
        $code = mb_trim($code);

        if ($code === '') {
            return null;
        }

        $packaging = ProductPackaging::query()
            ->with(['product.productCategory', 'unit', 'partnerships'])
            ->where('barcode', $code)
            ->whereHas('product', fn (Builder $query) => $moduleScope(
                $query->where('branch_id', $branchId)->where('is_active', true)
            ))
            ->first();

        if ($packaging) {
            return $packaging;
        }

        // Fall back to a product-level code, resolving to its base packaging.
        $product = Product::query()
            ->where('product_code', $code)
            ->where('branch_id', $branchId)
            ->where('is_active', true)
            ->tap(fn (Builder $query) => $moduleScope($query))
            ->first();

        if (! $product) {
            return null;
        }

        return ProductPackaging::query()
            ->with(['product.productCategory', 'unit', 'partnerships'])
            ->where('product_id', $product->id)
            ->orderByRaw('CASE WHEN unit_id = ? THEN 0 ELSE 1 END', [$product->base_unit_id])
            ->orderBy('conversion_factor')
            ->first();
    }
}
