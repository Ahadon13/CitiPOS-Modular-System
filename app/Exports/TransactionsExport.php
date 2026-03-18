<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class TransactionsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $branchId,
        private readonly string $dateFilter,
        private readonly ?int $paymentMethodFilter,
        private readonly string $search,
        private readonly ?string $exactDate = null
    ) {}

    public function query()
    {
        $query = Sale::query()
            ->with(['user', 'customer', 'paymentMethod'])
            ->withCount('saleItems')
            ->where('branch_id', $this->branchId);

        // Apply Date Filters (Mirroring Livewire component)
        if ($this->exactDate) {
            $query->whereDate('created_at', $this->exactDate);
        } else {
            // Apply Standard Date Filters (Mirroring Livewire component)
            $query->when($this->dateFilter === 'today', function ($q) {
                $q->whereDate('created_at', today());
            })
            ->when($this->dateFilter === 'yesterday', function ($q) {
                $q->whereDate('created_at', today()->subDay());
            })
            ->when($this->dateFilter === '7days', function ($q) {
                $q->where('created_at', '>=', today()->subDays(7));
            })
            ->when($this->dateFilter === '30days', function ($q) {
                $q->where('created_at', '>=', today()->subDays(30));
            });
        }

        // Apply Payment Method Filter
        $query->when(!empty($this->paymentMethodFilter), function ($q) {
            $q->where('payment_method_id', $this->paymentMethodFilter);
        });

        // Apply Text Search
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('reference_no', 'like', $searchTerm)
                  ->orWhereHas('user', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('customer', function ($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', $searchTerm);
                  });
            });
        }

        // Export sorted by newest first
        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Reference No.',
            'Date',
            'Time',
            'Cashier / User',
            'Customer',
            'Total Items',
            'Payment Method',
            'Subtotal (PHP)',
            'Discount (PHP)',
            'Tax (PHP)',
            'Grand Total (PHP)',
            'Status',
        ];
    }

    /**
     * @param Sale $transaction
     */
    public function map($transaction): array
    {
        // Assuming your amounts are stored in cents, divide by 100 for Excel.
        // If they are already decimals, remove the `/ 100` calculation.
        $subtotal = $transaction->getRawOriginal('subtotal') / 100;
        $discount = $transaction->getRawOriginal('discount_amount') / 100;
        $tax = $transaction->getRawOriginal('tax_amount') / 100;
        $grandTotal = $transaction->getRawOriginal('grand_total') / 100;

        return [
            $transaction->reference_no,
            $transaction->created_at->format('M d, Y'),
            $transaction->created_at->format('h:i A'),
            $transaction->user->name ?? 'Unknown',
            $transaction->customer->name ?? 'Walk-in',
            $transaction->sale_items_count,
            $transaction->paymentMethod->name ?? 'Unspecified',
            number_format((float) $subtotal, 2, '.', ''),
            number_format((float) $discount, 2, '.', ''),
            number_format((float) $tax, 2, '.', ''),
            number_format((float) $grandTotal, 2, '.', ''),
            $transaction->status->label() ?? 'Unknown',
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
