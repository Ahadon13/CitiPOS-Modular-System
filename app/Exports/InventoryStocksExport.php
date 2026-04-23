<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\InventoryBatch;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class InventoryStocksExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $branchId,
        private readonly string $search,
        private readonly string $stockFilter,
        private readonly ?int $selectedProduct,
        private readonly ?string $expirationDateFilter,
        private readonly array $targetCategories
    ) {}

    public function query()
    {
        $query = InventoryBatch::query()
            ->with(['product.category', 'product.productCategory', 'product.baseUnit'])
            ->where('branch_id', $this->branchId);

        // Apply Pharmacy Category Scope
        $query->whereHas('product.productCategory', function ($q) {
            $q->whereIn('name', $this->targetCategories);
        });

        // 1. Product Dropdown Filter
        $query->when($this->selectedProduct, function ($q) {
            $q->where('product_id', $this->selectedProduct);
        });

        // 2. Expiration Date Filter (Month-Year)
        $query->when(!empty($this->expirationDateFilter), function ($q) {
            try {
                $date = Carbon::parse($this->expirationDateFilter);
                $q->whereYear('expiration_date', $date->year)
                  ->whereMonth('expiration_date', $date->month);
            } catch (\Exception $e) {
                // Ignore bad formatting
            }
        });

        // 3. Text Search Filter
        $query->when($this->search, function ($q) {
            $searchTerm = '%' . trim($this->search) . '%';
            $q->where(function ($sub) use ($searchTerm) {
                $sub->where('batch_number', 'like', $searchTerm)
                    ->orWhereHas('product', function ($prodQuery) use ($searchTerm) {
                        $prodQuery->where('brand_name', 'like', $searchTerm)
                                  ->orWhere('generic_name', 'like', $searchTerm);
                    });
            });
        });

        // 4. Tabs Filter
        if (! $this->isMotorShop() && $this->stockFilter === 'expiring') {
            $query->where('quantity_on_hand', '>', 0)
                  ->where('expiration_date', '<=', Carbon::now()->addMonths(3))
                  ->where('expiration_date', '>', Carbon::now());
        } elseif (! $this->isMotorShop() && $this->stockFilter === 'expired') {
            $query->where('quantity_on_hand', '>', 0)
                  ->where('expiration_date', '<=', Carbon::now());
        } elseif ($this->stockFilter === 'out_of_stock') {
            $query->where('quantity_on_hand', '<=', 0);
        } else {
            $query->where('quantity_on_hand', '>', 0);
        }

        return $query->orderBy($this->isMotorShop() ? 'created_at' : 'expiration_date', 'asc');
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Brand Name',
            'Generic Name',
            'Dosage',
            'Batch Number',
            $this->isMotorShop() ? 'Received Date' : 'Expiration Date',
            'Quantity on Hand',
            'Unit',
            'Unit Cost (PHP)',
            'Total Value (PHP)',
            'Status'
        ];
    }

    /**
     * @param InventoryBatch $batch
     */
    public function map($batch): array
    {
        $expDate = $batch->expiration_date ? Carbon::parse($batch->expiration_date)->startOfDay() : null;

        $isExpired = $expDate?->isPast() ?? false;
        $isExpiringSoon = $expDate && ! $isExpired && $expDate->lessThanOrEqualTo(now()->addMonths(3));

        $status = 'Active';
        if ($batch->quantity_on_hand <= 0) $status = 'Depleted';
        elseif (! $this->isMotorShop() && $isExpired) $status = 'Expired';
        elseif (! $this->isMotorShop() && $isExpiringSoon) $status = 'Expiring Soon';

        $unitCost = $batch->getRawOriginal('cost_per_unit') / 100;
        $totalValue = $unitCost * $batch->quantity_on_hand;

        return [
            $batch->product->product_code ?? 'N/A',
            $batch->product->brand_name,
            $batch->product->generic_name,
            $batch->product->dosage,
            $batch->batch_number,
            $this->isMotorShop()
                ? $batch->created_at->format('M d, Y')
                : ($expDate?->format('M d, Y') ?? 'N/A'),
            $batch->quantity_on_hand,
            $batch->product->baseUnit->abbreviation ?? 'pcs',
            number_format((float) $unitCost, 2, '.', ''), // Standard decimal for Excel
            number_format((float) $totalValue, 2, '.', ''),
            $status,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Make the first row (headings) bold and have a light gray background
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ],
        ];
    }

    private function isMotorShop(): bool
    {
        return in_array('motor-shop', $this->targetCategories, true);
    }
}
