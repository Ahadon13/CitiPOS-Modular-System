<?php

declare(strict_types=1);

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Support\ModuleAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ReceiptController extends Controller
{
    public function __invoke(Request $request, Sale $sale): View
    {
        $module = (string) $request->route('module');

        $sale->load([
            'branch.productCategory',
            'customer.customerType',
            'discountType',
            'paymentMethod',
            'user',
            'saleItems.product',
            'saleItems.unit',
            'saleItems.productPackaging.unit',
            'saleItems.partnership.customerType',
            'motorShopServices.mechanic',
        ]);

        if ($sale->branch_id !== $request->user()->branch_id) {
            abort(403, 'Receipt is not available for the current branch.');
        }

        if (! ModuleAccess::canAccessBranchModule($request->user(), $sale->branch, $module)) {
            abort(403, 'Receipt is not available for this module.');
        }

        return view('pos.receipt', [
            'sale' => $sale,
            'module' => $module,
            'moduleLabel' => ModuleAccess::moduleLabel($module),
        ]);
    }
}
