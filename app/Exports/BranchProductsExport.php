<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\Product\CategoryType;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class BranchProductsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $branchId,
        private readonly string $search,
        private readonly bool $lowStockOnly,
        private readonly bool $outOfStockOnly,
        private readonly bool $nearExpiryOnly,
        private readonly bool $expiredOnly,
        private readonly bool $requirePrescription,
        private readonly bool $active,
        private readonly bool $disabled,
        private readonly array $productCategories
    ) {}

    public function query()
    {
        $query = Product::query()
            ->with(['category', 'baseUnit', 'inventoryBatches' => function ($q) {
                $q->where('branch_id', $this->branchId)->orderBy('expiration_date', 'asc');
            }])
            ->withSum(['inventoryBatches as total_stock' => function($q) {
                $q->where('branch_id', $this->branchId);
            }], 'quantity_on_hand')
            ->where('branch_id', $this->branchId)
            ->where('product_category_id', Branch::whereKey($this->branchId)->value('product_category_id'));

        // 1. Search
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('brand_name', 'like', '%' . $this->search . '%')
                  ->orWhere('name', 'like', '%' . $this->search . '%')
                  ->orWhere('generic_name', 'like', '%' . $this->search . '%')
                  ->orWhere('product_code', 'like', '%' . $this->search . '%');
            });
        }

        // 2. Basic Filters
        if ($this->active) $query->where('is_active', true);
        if ($this->disabled) $query->where('is_active', false);
        if ($this->isPharmacyBranch() && $this->requirePrescription) $query->where('requires_prescription', true);
        if (!empty($this->productCategories)) {
            $query->whereIn('category_id', $this->productCategories);
        }

        // 3. Stock Filters
        if ($this->outOfStockOnly) {
            $query->having('total_stock', '<=', 0)->orHavingNull('total_stock');
        } elseif ($this->lowStockOnly) {
            $query->having('total_stock', '>', 0)->havingRaw('total_stock <= reorder_level');
        }

        // 4. Expiry Filters
        if ($this->expiredOnly) {
            $query->whereHas('inventoryBatches', function($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('quantity_on_hand', '>', 0)
                  ->whereDate('expiration_date', '<', now());
            });
        } elseif ($this->nearExpiryOnly) {
            $query->whereHas('inventoryBatches', function($q) {
                $q->where('branch_id', $this->branchId)
                  ->where('quantity_on_hand', '>', 0)
                  ->whereDate('expiration_date', '>=', now())
                  ->whereDate('expiration_date', '<=', now()->addMonths(3));
            });
        }

        return $query->orderBy('brand_name', 'asc');
    }

    public function headings(): array
    {
        return [
            'Product Code',
            'Product Name',
            'Product Detail',
            'Category',
            'Current Stock',
            'Base Unit',
            'Reorder Level',
            'Earliest Expiry',
            'Prescription Required',
            'Status',
        ];
    }

    public function map($product): array
    {
        $stock = $product->total_stock ?? 0;
        $currentBatch = $product->inventoryBatches->first();

        $expiryDate = 'No active batches';
        if ($currentBatch && $currentBatch->expiration_date) {
            $expiryDate = \Carbon\Carbon::parse($currentBatch->expiration_date)->format('M d, Y');
        }

        return [
            $product->product_code ?? 'N/A',
            $product->brand_name ?? $product->name ?? 'N/A',
            $this->productDetail($product),
            $product->category->name ?? 'Uncategorized',
            (float) $stock, // Casting to float keeps Excel from complaining about "Numbers stored as text"
            $product->baseUnit->name ?? 'N/A',
            (float) $product->reorder_level,
            $expiryDate,
            $this->isPharmacyBranch() ? ($product->requires_prescription ? 'Yes' : 'No') : 'N/A',
            $product->is_active ? 'Active' : 'Disabled',
        ];
    }

    private function isPharmacyBranch(): bool
    {
        return (int) Branch::whereKey($this->branchId)->value('product_category_id') === $this->pharmacyProductCategoryId();
    }

    private function pharmacyProductCategoryId(): int
    {
        return (int) ProductCategory::where('name', CategoryType::Pharmacy->value)->value('id');
    }

    private function productDetail(Product $product): string
    {
        $pharmacyDetail = trim(($product->generic_name ?? '') . ' ' . (($product->dosage ?? '') ? "- {$product->dosage}" : '') . ' ' . (($product->form ?? '') ? "({$product->form})" : ''));

        if ($pharmacyDetail !== '') {
            return $pharmacyDetail;
        }

        $attributes = is_array($product->attributes) ? $product->attributes : [];

        return collect([
            $attributes['part_number'] ?? null,
            $attributes['oem_number'] ?? null,
            $attributes['vehicle_fitment'] ?? null,
        ])->filter()->implode(' | ') ?: 'N/A';
    }

    public function styles(Worksheet $sheet)
    {
        // Make the header row bold and give it a light gray background
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0']
                ]
            ]
        ];
    }
}
