<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $branchId,
        private readonly array $targetCategories,
        private readonly array $productCategories,
        private readonly string $search,
        private readonly bool $lowStockOnly,
        private readonly bool $outOfStockOnly
    ) {}

    public function query()
    {
        $query = Product::query()
            ->select('products.*')
            ->with(['baseUnit', 'supplier', 'category', 'productPackagings', 'productCategory']);

        // 1. Join for Category Filtering
        $query->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
              ->whereIn('product_categories.name', $this->targetCategories);

        // 2. Calculate Total Stock for Filtering & Mapping
        $query->withSum(['inventoryBatches as total_stock' => function ($subQ) {
            $subQ->where('branch_id', $this->branchId);
        }], 'quantity_on_hand');

        // 3. Search Filter
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('products.brand_name', 'like', $searchTerm)
                  ->orWhere('products.generic_name', 'like', $searchTerm)
                  ->orWhere('products.product_code', 'like', $searchTerm);
            });
        }

        // 4. Stock Toggles
        $query->when($this->lowStockOnly, function ($q) {
            $q->havingRaw('COALESCE(total_stock, 0) > 0') // Has some stock
              ->havingRaw('COALESCE(total_stock, 0) < products.reorder_level'); // But below threshold
        });

        $query->when($this->outOfStockOnly, function ($q) {
            $q->havingRaw('COALESCE(total_stock, 0) <= 0');
        });

        // 5. Filter by categories (if any)
        if (!empty($this->productCategories)) {
            $query->whereHas('category', function ($q) {
                $q->whereIn('name', $this->productCategories);
            });
        }

        return $query->orderBy('products.brand_name', 'asc');
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Brand Name',
            'Generic Name',
            'Dosage',
            'Form',
            'Category',
            'Supplier',
            'Base Unit',
            'Total Stock on Hand',
            'Low Stock Alert Level',
            'Requires Prescription',
            'Status',
        ];
    }

    /**
     * @param Product $product
     */
    public function map($product): array
    {
        $stockStatus = 'In Stock';
        $totalStock = (float) ($product->total_stock ?? 0);

        if ($totalStock <= 0) {
            $stockStatus = 'Out of Stock';
        } elseif ($totalStock < (float) $product->reorder_level) {
            $stockStatus = 'Low Stock';
        }

        return [
            $product->product_code,
            $product->brand_name,
            $product->generic_name ?? 'N/A',
            $product->dosage ?? '-',
            $product->form ?? '-',
            $product->category->name ?? 'Uncategorized',
            $product->supplier->name ?? 'N/A',
            $product->baseUnit->name ?? 'pcs',
            $totalStock,
            $product->reorder_level,
            $product->requires_prescription ? 'Yes' : 'No',
            $product->is_active ? $stockStatus : 'Disabled',
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
