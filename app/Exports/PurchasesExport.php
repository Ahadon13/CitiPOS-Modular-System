<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class PurchasesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $branchId,
        private readonly string $statusFilter,
        private readonly ?int $supplierFilter,
        private readonly string $search
    ) {}

    public function query()
    {
        $query = Purchase::query()
            ->with(['supplier', 'user'])
            ->withCount('purchaseItems')
            ->where('branch_id', $this->branchId);

        // Apply Status Filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Apply Supplier Filter
        if ($this->supplierFilter !== null) {
            $query->where('supplier_id', $this->supplierFilter);
        }

        // Apply Text Search
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference_no', 'like', $searchTerm)
                  ->orWhereHas('supplier', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  });
            });
        }

        // Export sorted by newest first
        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'PO Number',
            'Order Date',
            'Expected Delivery',
            'Supplier',
            'Created By',
            'Total Items',
            'Total Cost (PHP)',
            'Status',
        ];
    }

    /**
     * @param Purchase $purchase
     */
    public function map($purchase): array
    {
        // Assuming total_cost is stored in cents, divide by 100
        $totalCost = $purchase->getRawOriginal('total_cost') / 100;

        $expectedDelivery = $purchase->expected_delivery_date
            ? Carbon::parse($purchase->expected_delivery_date)->format('M d, Y')
            : 'Not specified';

        return [
            $purchase->reference_no,
            $purchase->created_at->format('M d, Y h:i A'),
            $expectedDelivery,
            $purchase->supplier->name ?? 'Unknown',
            $purchase->user->name ?? 'System',
            $purchase->purchase_items_count,
            number_format((float) $totalCost, 2, '.', ''), // Two decimal places for Excel
            $purchase->status->label(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }
}
