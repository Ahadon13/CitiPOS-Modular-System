<?php

declare(strict_types=1);

namespace App\Livewire\Inventory\Pages\Pharmacy\Purchase;

use App\Exports\PurchaseOrderExport;
use App\Livewire\Concerns\HasToast;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

final class ViewPurchaseModal extends Component
{
    use HasToast;

    // Receive the full JSON array from the parent table
    #[Modelable]
    public array|null $view_purchase = null;

    public function downloadExcel()
    {
        if (empty($this->view_purchase)) {
            $this->toastError('No Purchase Order selected.');
            return;
        }

        $poNumber = $this->view_purchase['reference_no'];
        $poId = $this->view_purchase['id'];

        // Instantly download the Excel file using our new Export class
        return Excel::download(new PurchaseOrderExport($poId), "{$poNumber}.xlsx");
    }
}
