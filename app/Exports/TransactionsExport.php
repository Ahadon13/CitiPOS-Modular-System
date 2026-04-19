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
        private readonly array $dateRange,
        private readonly ?int $paymentMethodFilter,
        private readonly string $search,
        private readonly ?string $exactDate = null,
        private readonly array $targetCategories = []
    ) {}

    public function query()
    {
        $query = Sale::query()
            ->with(['user', 'customer', 'paymentMethod'])
            ->withCount('saleItems')
            ->where('branch_id', $this->branchId);

        if (! empty($this->targetCategories)) {
            $query->whereHas('saleItems.product.productCategory', function ($q) {
                $q->whereIn('name', $this->targetCategories);
            });
        }

        // Apply Date Filters
        if ($this->exactDate) {
            // Triggered by the "Daily Report" button
            $query->whereDate('created_at', $this->exactDate);
        } else {
            // Triggered by the new Export Modal (Uses the TallStackUI Date Range)
            $startDate = Carbon::parse($this->dateRange[0])->startOfDay();
            $endDate = Carbon::parse($this->dateRange[1])->endOfDay();

            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        // Apply Payment Method Filter
        $query->when(!empty($this->paymentMethodFilter), function ($q) {
            $q->where('payment_method_id', $this->paymentMethodFilter);
        });

        // Apply Text Search
        if (!empty($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                // Ensure this matches your actual DB column! (Often payment_reference or reference_no)
                $q->where('payment_reference', 'like', $searchTerm)
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
            'Grand Total (PHP)',
            'Change (PHP)',
            'Status',
        ];
    }

    /**
     * @param Sale $transaction
     */
    public function map($transaction): array
    {
        // Convert cents to dollars/pesos for Excel output
        $subtotal = $transaction->getRawOriginal('subtotal') / 100;
        $discount = $transaction->getRawOriginal('discount_amount') / 100;
        $grandTotal = $transaction->getRawOriginal('grand_total') / 100;
        $change = $transaction->getRawOriginal('change_amount') / 100;

        return [
            $transaction->payment_reference ?? 'N/A', // Updated to match standard schema naming
            $transaction->created_at->format('M d, Y'),
            $transaction->created_at->format('h:i A'),
            $transaction->user->name ?? 'Unknown',
            $transaction->customer->name ?? 'Walk-in',
            $transaction->sale_items_count,
            $transaction->paymentMethod->name ?? 'Unspecified',
            number_format((float) $subtotal, 2, '.', ''),
            number_format((float) $discount, 2, '.', ''),
            number_format((float) $grandTotal, 2, '.', ''),
            number_format((float) $change, 2, '.', ''),
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
