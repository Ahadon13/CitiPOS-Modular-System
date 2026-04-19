<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\SaleItem;
use App\Enums\Product\CategoryType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DemandProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected int $branchId,
        protected array $dateRange,
        protected array $targetCategories = ['pharmacy']
    ) {}

    public function query()
    {
        $startDate = Carbon::parse($this->dateRange[0])->startOfDay();
        $endDate = Carbon::parse($this->dateRange[1])->endOfDay();

        return SaleItem::query()
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->where('sales.branch_id', $this->branchId)
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->whereIn('product_categories.name', $this->targetCategories)
            ->selectRaw('
                sale_items.product_id,
                SUM(sale_items.quantity) as total_sold,
                SUM(sale_items.subtotal) as total_revenue
            ')
            ->with(['product.baseUnit', 'product.productCategory', 'product.category'])
            ->groupBy('sale_items.product_id')
            ->orderByDesc('total_sold');
    }

    public function headings(): array
    {
        if ($this->isGroceryExport()) {
            return [
                'Rank',
                'Product Code',
                'Brand Name',
                'Category',
                'Total Volume Sold',
                'Unit',
                'Total Revenue Generated (PHP)'
            ];
        }

        return [
            'Rank',
            'Product Code',
            'Brand Name',
            'Generic Name',
            'Dosage',
            'Category',
            'Total Volume Sold',
            'Unit',
            'Total Revenue Generated (PHP)'
        ];
    }

    public function map($item): array
    {
        // Static counter for the rank
        static $rank = 0;
        $rank++;

        if ($this->isGroceryExport()) {
            return [
                $rank,
                $item->product->product_code ?? 'N/A',
                $item->product->brand_name ?? 'Unknown',
                $item->product->category->name ?? 'N/A',
                $item->total_sold,
                $item->product->baseUnit->abbreviation ?? 'pcs',
                number_format($item->total_revenue / 100, 2, '.', ''),
            ];
        }

        return [
            $rank,
            $item->product->product_code ?? 'N/A',
            $item->product->brand_name ?? 'Unknown',
            $item->product->generic_name ?? 'N/A',
            $item->product->dosage ?? 'N/A',
            $item->product->category->name ?? 'N/A',
            $item->total_sold,
            $item->product->baseUnit->abbreviation ?? 'pcs',
            // Format money (assuming it's in cents, divide by 100)
            number_format($item->total_revenue / 100, 2, '.', ''),
        ];
    }

    private function isGroceryExport(): bool
    {
        return count($this->targetCategories) === 1 && $this->targetCategories[0] === CategoryType::Grocery->value;
    }
}
