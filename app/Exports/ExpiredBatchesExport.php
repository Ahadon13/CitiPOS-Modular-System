<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\InventoryBatch;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

final class ExpiredBatchesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(
        protected int $branchId,
        protected array $targetCategories = ['Pharmacy', 'Medicine'],
    ) {}

    public function collection(): Collection
    {
        return InventoryBatch::query()
            ->with(['product.productCategory', 'product.baseUnit'])
            ->where('quantity_on_hand', '>', 0)
            ->where('branch_id', $this->branchId)
            ->where('expiration_date', '<=', Carbon::now())
            ->whereHas('product.productCategory', function ($q) {
                $q->whereIn('name', $this->targetCategories);
            })
            ->orderBy('expiration_date', 'asc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'Generic Name',
            'Dosage',
            'Form',
            'Batch Number',
            'Date Expired',
            'Quantity Lost',
            'Unit',
            'Cost Per Unit',
        ];
    }

    public function map($batch): array
    {
        return [
            $batch->product->brand_name ?? 'Unknown',
            $batch->product->generic_name ?? '-',
            $batch->product->dosage ?? '-',
            $batch->product->form ?? '-',
            $batch->batch_number ?? '-',
            optional($batch->expiration_date)?->format('Y-m-d') ?? '-',
            (float) ($batch->quantity_on_hand ?? 0),
            $batch->product->baseUnit->abbreviation ?? 'pcs',
            $batch->cost_per_unit ? ((int) $batch->cost_per_unit->getAmount() / 100) : 0,
        ];
    }
}
