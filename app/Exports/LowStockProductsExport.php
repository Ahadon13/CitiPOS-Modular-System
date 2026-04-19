<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class LowStockProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected int $branchId,
        protected array $targetCategories = ['Pharmacy', 'Medicine'],
    ) {}

    public function collection(): Collection
    {
        return Product::query()
            ->where('branch_id', $this->branchId)
            ->with(['productCategory', 'baseUnit'])
            ->with(['productPackagings' => function ($q) {
                $q->orderBy('conversion_factor', 'asc');
            }])
            ->whereHas('productCategory', function ($q) {
                $q->whereIn('name', $this->targetCategories);
            })
            ->withSum(['inventoryBatches as total_stock' => function ($query) {
                $query->where('branch_id', $this->branchId);
            }], 'quantity_on_hand')
            ->havingRaw('COALESCE(total_stock, 0) < products.reorder_level')
            ->orderBy('total_stock', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Generic Name',
            'Dosage',
            'Form',
            'Product Code',
            'Current Stock',
            'Unit',
            'Reorder Level',
            'Selling Price',
        ];
    }

    public function map($product): array
    {
        $basePkg = $product->productPackagings
            ->where('unit_id', $product->base_unit_id)
            ->first() ?? $product->productPackagings->first();

        return [
            $product->brand_name ?? 'Unknown',
            $product->generic_name ?? '-',
            $product->dosage ?? '-',
            $product->form ?? '-',
            $product->product_code ?? '-',
            (float) ($product->total_stock ?? 0),
            $product->baseUnit->abbreviation ?? 'pcs',
            (float) ($product->reorder_level ?? 0),
            $basePkg ? ((int) $basePkg->price->getAmount() / 100) : 0,
        ];
    }
}