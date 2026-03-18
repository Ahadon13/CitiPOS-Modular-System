<?php

namespace App\Actions\Inventory;

use App\Models\Purchase;

class CancelPurchaseOrder
{
    public function execute(int $purchaseId): Purchase
    {
        $purchase = Purchase::findOrFail($purchaseId);

        if ($purchase->status === 'completed') {
            throw new \Exception('Cannot cancel a Purchase Order that has already been completed and received.');
        }

        $purchase->update([
            'status' => 'cancelled',
        ]);

        return $purchase;
    }
}
