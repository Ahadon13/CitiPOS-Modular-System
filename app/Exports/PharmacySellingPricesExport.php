<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\Product\CategoryType;
use App\Models\ProductPackaging;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class PharmacySellingPricesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly int $branchId,
        private readonly string $search = '',
        private readonly ?int $customerTypeId = null,
        private readonly ?int $categoryId = null,
        private readonly bool $onlyWithPartnership = false,
    ) {}

    public function collection(): Collection
    {
        return $this->query()->get();
    }

    public function headings(): array
    {
        return [
            'Product',
            'Generic Name',
            'Dosage / Form',
            'Product Code',
            'Category',
            'Packaging Unit',
            'Barcode',
            'Regular Price',
            'Customer Type',
            'Partnership Price',
            'Difference',
            'Branch ID',
            'Last Updated',
        ];
    }

    public function map($packaging): array
    {
        $partnerships = $packaging->partnerships;

        if ($this->customerTypeId) {
            $partnerships = $partnerships->where('customer_type_id', $this->customerTypeId);
        }

        if ($partnerships->isEmpty()) {
            return $this->row($packaging);
        }

        return $partnerships
            ->map(fn ($partnership): array => $this->row($packaging, $partnership))
            ->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
        ];
    }

    private function query(): Builder
    {
        return ProductPackaging::query()
            ->with([
                'unit',
                'product.category',
                'product.productCategory',
                'partnerships' => function ($query) {
                    $query->with('customerType')
                        ->where('branch_id', $this->branchId)
                        ->when($this->customerTypeId, fn ($query) => $query->where('customer_type_id', $this->customerTypeId));
                },
            ])
            ->whereHas('product', function (Builder $query) {
                $query->where('branch_id', $this->branchId)
                    ->whereHas('productCategory', fn (Builder $query) => $query->where('name', CategoryType::Pharmacy->value))
                    ->when($this->categoryId, fn (Builder $query) => $query->where('category_id', $this->categoryId));
            })
            ->when($this->search !== '', function (Builder $query) {
                $search = '%' . trim($this->search) . '%';
                $query->where(function (Builder $query) use ($search) {
                    $query->where('barcode', 'like', $search)
                        ->orWhereHas('product', function (Builder $query) use ($search) {
                            $query->where('brand_name', 'like', $search)
                                ->orWhere('generic_name', 'like', $search)
                                ->orWhere('product_code', 'like', $search);
                        });
                });
            })
            ->when($this->onlyWithPartnership, function (Builder $query) {
                $query->whereHas('partnerships', function (Builder $query) {
                    $query->where('branch_id', $this->branchId)
                        ->when($this->customerTypeId, fn (Builder $query) => $query->where('customer_type_id', $this->customerTypeId));
                });
            })
            ->orderBy(
                \App\Models\Product::select('brand_name')
                    ->whereColumn('products.id', 'product_packagings.product_id')
                    ->limit(1)
            );
    }

    private function row($packaging, $partnership = null): array
    {
        $regularPrice = (int) $packaging->getRawOriginal('price') / 100;
        $partnershipPrice = $partnership ? ((int) $partnership->getRawOriginal('special_price') / 100) : null;

        return [
            $packaging->product->brand_name ?? 'Unknown',
            $packaging->product->generic_name ?? '-',
            trim(($packaging->product->dosage ?? '') . ' ' . ($packaging->product->form ?? '')) ?: '-',
            $packaging->product->product_code ?? '-',
            $packaging->product->category->name ?? 'Uncategorized',
            $packaging->unit->name ?? $packaging->unit->abbreviation ?? 'Unit',
            $packaging->barcode ?? '-',
            number_format($regularPrice, 2, '.', ''),
            $partnership?->customerType?->name ?? '-',
            $partnershipPrice === null ? '-' : number_format($partnershipPrice, 2, '.', ''),
            $partnershipPrice === null ? '-' : number_format($regularPrice - $partnershipPrice, 2, '.', ''),
            $this->branchId,
            $partnership?->updated_at?->format('Y-m-d H:i:s') ?? '-',
        ];
    }
}
