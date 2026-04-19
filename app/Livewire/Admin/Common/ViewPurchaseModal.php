<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Common;

use App\Exports\PurchaseOrderExport;
use App\Livewire\Concerns\HasToast;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

final class ViewPurchaseModal extends Component
{
    use HasToast;

    #[Modelable]
    public ?array $view_purchase = null;

    public function downloadExcel()
    {
        if (empty($this->view_purchase)) {
            $this->toastError('No Purchase Order selected.');

            return;
        }

        $poNumber = $this->view_purchase['reference_no'];
        $poId = $this->view_purchase['id'];

        return Excel::download(new PurchaseOrderExport($poId), "{$poNumber}.xlsx");
    }
}
