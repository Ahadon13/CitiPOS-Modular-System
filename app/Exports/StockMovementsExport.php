<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\InventoryTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithStyles;

final class StockMovementsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(
        protected int $branchId,
        protected array $targetCategories = ['Pharmacy', 'Medicine'],
        protected ?string $transactionType = null,
        protected array $dateRange = [],
    ) {}

    public function collection(): Collection
    {
        return InventoryTransaction::query()
            ->with(['product.baseUnit', 'product.productCategory', 'batch', 'user', 'reference'])
            ->where('branch_id', $this->branchId)
            ->whereHas('product.productCategory', function (Builder $query) {
                $query->whereIn('name', $this->targetCategories);
            })
            ->when(! empty($this->transactionType), function (Builder $query) {
                $query->where('type', $this->transactionType);
            })
            ->when($this->hasDateRange(), function (Builder $query) {
                $query->whereBetween('created_at', [
                    Carbon::parse($this->dateRange[0])->startOfDay(),
                    Carbon::parse($this->dateRange[1])->endOfDay(),
                ]);
            })
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        if ($this->isMotorShopExport()) {
            return [
                'Date',
                'Time',
                'Product',
                'Part Number',
                'OEM Number',
                'Product Code',
                'Batch No.',
                'Type',
                'IN',
                'OUT',
                'Running Balance',
                'Unit',
                'Unit Cost',
                'Unit Price',
                'User',
                'Remarks',
                'Reference',
            ];
        }

        if ($this->isGroceryExport()) {
            return [
                'Date',
                'Time',
                'Product',
                'Product Code',
                'Batch No.',
                'Type',
                'IN',
                'OUT',
                'Running Balance',
                'Unit',
                'Unit Cost',
                'Unit Price',
                'User',
                'Remarks',
                'Reference',
            ];
        }

        return [
            'Date',
            'Time',
            'Product',
            'Generic Name',
            'Dosage',
            'Product Code',
            'Batch No.',
            'Type',
            'IN',
            'OUT',
            'Running Balance',
            'Unit',
            'Unit Cost',
            'Unit Price',
            'User',
            'Remarks',
            'Reference',
        ];
    }

    public function map($transaction): array
    {
        $quantity = (float) $transaction->quantity;
        $isAddition = $transaction->type?->isAddition() ?? false;

        if ($this->isMotorShopExport()) {
            return [
                $transaction->created_at?->format('Y-m-d') ?? '-',
                $transaction->created_at?->format('h:i A') ?? '-',
                $transaction->product->brand_name ?? $transaction->product->name ?? 'Unknown',
                data_get($transaction->product->attributes, 'part_number', '-') ?: '-',
                data_get($transaction->product->attributes, 'oem_number', '-') ?: '-',
                $transaction->product->product_code ?? '-',
                $transaction->batch->batch_number ?? '-',
                $transaction->type?->label() ?? $transaction->type?->value ?? '-',
                $isAddition ? $quantity : 0,
                $isAddition ? 0 : $quantity,
                (float) $transaction->running_balance,
                $transaction->product->baseUnit->abbreviation ?? 'pcs',
                $transaction->unit_cost ? ((int) $transaction->unit_cost->getAmount() / 100) : 0,
                $transaction->unit_price ? ((int) $transaction->unit_price->getAmount() / 100) : 0,
                $transaction->user->name ?? 'Unknown',
                $transaction->remarks ?? '-',
                $this->formatReference($transaction),
            ];
        }

        if ($this->isGroceryExport()) {
            return [
                $transaction->created_at?->format('Y-m-d') ?? '-',
                $transaction->created_at?->format('h:i A') ?? '-',
                $transaction->product->brand_name ?? $transaction->product->name ?? 'Unknown',
                $transaction->product->product_code ?? '-',
                $transaction->batch->batch_number ?? '-',
                $transaction->type?->label() ?? $transaction->type?->value ?? '-',
                $isAddition ? $quantity : 0,
                $isAddition ? 0 : $quantity,
                (float) $transaction->running_balance,
                $transaction->product->baseUnit->abbreviation ?? 'pcs',
                $transaction->unit_cost ? ((int) $transaction->unit_cost->getAmount() / 100) : 0,
                $transaction->unit_price ? ((int) $transaction->unit_price->getAmount() / 100) : 0,
                $transaction->user->name ?? 'Unknown',
                $transaction->remarks ?? '-',
                $this->formatReference($transaction),
            ];
        }

        return [
            $transaction->created_at?->format('Y-m-d') ?? '-',
            $transaction->created_at?->format('h:i A') ?? '-',
            $transaction->product->brand_name ?? $transaction->product->name ?? 'Unknown',
            $transaction->product->generic_name ?? '-',
            trim(($transaction->product->dosage ?? '') . ' ' . ($transaction->product->form ?? '')) ?: '-',
            $transaction->product->product_code ?? '-',
            $transaction->batch->batch_number ?? '-',
            $transaction->type?->label() ?? $transaction->type?->value ?? '-',
            $isAddition ? $quantity : 0,
            $isAddition ? 0 : $quantity,
            (float) $transaction->running_balance,
            $transaction->product->baseUnit->abbreviation ?? 'pcs',
            $transaction->unit_cost ? ((int) $transaction->unit_cost->getAmount() / 100) : 0,
            $transaction->unit_price ? ((int) $transaction->unit_price->getAmount() / 100) : 0,
            $transaction->user->name ?? 'Unknown',
            $transaction->remarks ?? '-',
            $this->formatReference($transaction),
        ];
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

    private function hasDateRange(): bool
    {
        return count($this->dateRange) === 2
            && ! empty($this->dateRange[0])
            && ! empty($this->dateRange[1]);
    }

    private function formatReference(InventoryTransaction $transaction): string
    {
        if (! $transaction->reference) {
            return '-';
        }

        $referenceNo = $transaction->reference->reference_no
            ?? $transaction->reference->payment_reference
            ?? $transaction->reference->id
            ?? null;

        return class_basename($transaction->reference_type) . ' #' . ($referenceNo ?? '-');
    }

    private function isGroceryExport(): bool
    {
        return count($this->targetCategories) === 1 && $this->targetCategories[0] === 'grocery';
    }

    private function isMotorShopExport(): bool
    {
        return count($this->targetCategories) === 1 && $this->targetCategories[0] === 'motor-shop';
    }
}
