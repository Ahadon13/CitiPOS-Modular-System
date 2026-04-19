<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\InventoryTransaction;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryLedgerExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct(
        protected ?int $branchId,
        protected ?int $categoryId,
        protected string $transactionType,
        protected string $search,
        protected $startDate,
        protected $endDate
    ) {}

    public function query()
    {
        $query = InventoryTransaction::with(['product.category', 'branch', 'user'])
            ->whereBetween('created_at', [$this->startDate, $this->endDate]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->categoryId) {
            $query->whereHas('product', function ($q) {
                $q->where('product_category_id', $this->categoryId);
            });
        }

        if ($this->transactionType) {
            $query->where('type', $this->transactionType);
        }

        if ($this->search) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('product', function ($subQ) use ($searchTerm) {
                    $subQ->where('name', 'like', $searchTerm);
                })
                ->orWhere('type', 'like', $searchTerm);
            });
        }

        return $query->latest('created_at');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Time',
            'Product Name',
            'Dosage',
            'Form',
            'Category',
            'Transaction Type',
            'Quantity Change',
            'Running Balance',
            'Location',
            'Operator'
        ];
    }

    /**
     * @param InventoryTransaction $transaction
     */
    public function map($transaction): array
    {
        return [
            $transaction->created_at->format('Y-m-d'),
            $transaction->created_at->format('h:i:s A'),
            $transaction->product->name,
            $transaction->product->dosage ?? '-',
            $transaction->product->form ?? '-',
            $transaction->product->category->name ?? 'Uncategorized',
            $transaction->type->label(),
            ($transaction->type->isAddition() ? '+' : '') . rtrim(rtrim((string)$transaction->quantity, '0'), '.'),
            rtrim(rtrim((string)$transaction->running_balance, '0'), '.'),
            $transaction->branch->name,
            $transaction->user->name,
        ];
    }
}
