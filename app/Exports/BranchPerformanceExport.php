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

final class BranchPerformanceExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithTitle
{
    use Exportable;

    public function __construct(protected PartnershipSalesFilters $filters) {}

    public function query()
    {
        return SalesAudit::byBranch($this->filters);
    }

    public function title(): string
    {
        return 'Branch Performance';
    }

    public function headings(): array
    {
        return ['Branch', 'Module', 'Orders', 'Revenue', 'Discounts Given', 'Average Order'];
    }

    /**
     * @param  object  $row
     */
    public function map($row): array
    {
        $orders = (int) $row->orders;
        $revenue = (int) round((float) $row->revenue);

        return [
            $row->branch_name,
            $row->module ?? '-',
            $orders,
            round($revenue / 100, 2),
            round((float) $row->discounts / 100, 2),
            $orders > 0 ? round($revenue / $orders / 100, 2) : 0.0,
        ];
    }
}
