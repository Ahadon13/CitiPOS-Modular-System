<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

final class BranchPurchasesExport implements FromView, ShouldAutoSize
{
    public function __construct(
        public int $branchId,
        public Carbon $startDate,
        public Carbon $endDate
    ) {}

    public function view(): View
    {
        return view('exports.branch-purchases', [
            'purchases' => Purchase::with([
                'supplier', 'branch', 'user', 'purchaseItems.product', 'purchaseItems.unit'
            ])
            ->where('branch_id', $this->branchId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc')
            ->get()
        ]);
    }
}
