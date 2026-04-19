<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class TopDemandProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected int $branchId,
        protected array $targetCategories = ['Pharmacy', 'Medicine'],
    ) {}

    public function collection(): Collection
    {
        return SaleItem::query()
            ->selectRaw('product_id, SUM(quantity) as total_sold, SUM(subtotal) as total_revenue')
            ->whereHas('sale', function ($q) {
                $q->where('branch_id', $this->branchId);
            })
            ->whereHas('product.productCategory', function ($q) {
                $q->whereIn('name', $this->targetCategories);
            })
            ->with(['product.baseUnit'])
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Rank',
            'Product Name',
            'Generic Name',
            'Dosage',
            'Form',
            'Product Code',
            'Total Volume Sold',
            'Unit',
            'Revenue Generated',
            'Exported At',
        ];
    }

    public function map($row): array
    {
        static $rank = 0;
        $rank++;

        return [
            $rank,
            $row->product->brand_name ?? 'Unknown',
            $row->product->generic_name ?? '-',
            $row->product->dosage ?? '-',
            $row->product->form ?? '-',
            $row->product->product_code ?? '-',
            (float) ($row->total_sold ?? 0),
            $row->product->baseUnit->abbreviation ?? 'pcs',
            (float) ($row->total_revenue ?? 0) / 100,
            Carbon::now()->format('Y-m-d H:i:s'),
        ];
    }
}
