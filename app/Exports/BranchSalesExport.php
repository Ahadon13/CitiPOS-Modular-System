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

final class BranchSalesExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly int $branchId,
        private readonly Carbon $startDate,
        private readonly Carbon $endDate
    ) {}

    public function query()
    {
        return Sale::query()
            ->with(['user', 'customer', 'paymentMethod'])
            ->withCount('saleItems')
            ->where('branch_id', $this->branchId)
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Reference No.', 'Date', 'Time', 'Cashier', 'Customer', 'Items', 'Method', 'Subtotal (PHP)', 'Discount (PHP)', 'Grand Total (PHP)', 'Status',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->payment_reference ?? 'N/A',
            $sale->created_at->format('M d, Y'),
            $sale->created_at->format('h:i A'),
            $sale->user->name ?? 'Unknown',
            $sale->customer->name ?? 'Walk-in',
            $sale->sale_items_count,
            $sale->paymentMethod->name ?? 'Unspecified',
            number_format((float) ($sale->getRawOriginal('subtotal') / 100), 2, '.', ''),
            number_format((float) ($sale->getRawOriginal('discount_amount') / 100), 2, '.', ''),
            number_format((float) ($sale->getRawOriginal('grand_total') / 100), 2, '.', ''),
            $sale->status->label() ?? 'Unknown',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [ 1 => ['font' => ['bold' => true], 'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']]] ];
    }
}
