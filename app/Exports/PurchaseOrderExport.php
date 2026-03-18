<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

final class PurchaseOrderExport implements FromView, ShouldAutoSize
{
    public function __construct(
        public int $purchaseId
    ) {}

    public function view(): View
    {
        return view('exports.purchase-order', [
            'purchase' => Purchase::with([
                'supplier',
                'branch',
                'user',
                'purchaseItems.product',
                'purchaseItems.unit'
            ])->findOrFail($this->purchaseId)
        ]);
    }
}
