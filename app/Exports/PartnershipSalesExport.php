<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\PartnershipSales;
use App\Support\PartnershipSalesFilters;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Every sale line that used a partnership price, with the regular price it was
 * discounted from, so an auditor can see exactly what each partner saved.
 */
final class PartnershipSalesExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected PartnershipSalesFilters $filters) {}

    public function query()
    {
        return PartnershipSales::items($this->filters);
    }

    public function title(): string
    {
        return 'Partnership Sales';
    }

    public function headings(): array
    {
        return [
            'Date',
            'Time',
            'Sale #',
            'Branch',
            'Partner (Customer Type)',
            'Customer',
            'Product',
            'Product Code',
            'Unit',
            'Quantity',
            'Regular Price',
            'Partnership Price',
            'Discount / Unit',
            'Line Total',
            'Partner Savings',
        ];
    }

    /**
     * @param  object  $row
     */
    public function map($row): array
    {
        $soldAt = Carbon::parse($row->sold_at);
        $regular = (int) ($row->regular_price_at_moment ?? $row->price_at_moment);
        $paid = (int) $row->price_at_moment;

        return [
            $soldAt->format('Y-m-d'),
            $soldAt->format('h:i A'),
            $row->sale_id,
            $row->branch_name,
            $row->partner_name ?? 'Unknown partner',
            $row->customer_name ?? 'Walk-in',
            $row->brand_name ?: ($row->product_name ?: '-'),
            $row->product_code ?? '-',
            $row->unit_abbreviation ?? $row->unit_name ?? '-',
            (float) $row->quantity,
            $this->toPeso($regular),
            $this->toPeso($paid),
            $this->toPeso($regular - $paid),
            $this->toPeso((int) round((float) $row->subtotal)),
            $this->toPeso((int) round((float) $row->partner_savings)),
        ];
    }

    private function toPeso(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
