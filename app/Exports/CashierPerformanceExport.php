<?php

declare(strict_types=1);

namespace App\Exports;

use App\Support\PartnershipSalesFilters;
use App\Support\SalesAudit;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

final class CashierPerformanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected PartnershipSalesFilters $filters) {}

    public function query()
    {
        return SalesAudit::byCashier($this->filters);
    }

    public function title(): string
    {
        return 'Cashier Performance';
    }

    public function headings(): array
    {
        return ['Cashier', 'Branch', 'Orders', 'Revenue', 'Discounts Given', 'Average Order'];
    }

    /**
     * @param  object  $row
     */
    public function map($row): array
    {
        return [
            $row->cashier_name,
            $row->branch_name,
            (int) $row->orders,
            round((float) $row->revenue / 100, 2),
            round((float) $row->discounts / 100, 2),
            round((float) $row->average_order / 100, 2),
        ];
    }
}
