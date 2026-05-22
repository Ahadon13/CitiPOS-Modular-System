<?php

declare(strict_types=1);

namespace App\Exports;

use App\Enums\Product\CategoryType;
use App\Enums\Sale\Status;
use App\Models\Branch;
use App\Models\ProductCategory;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

final class SalesReportExport implements FromArray, ShouldAutoSize, WithColumnFormatting, WithEvents, WithTitle
{
    private int $headingRow = 7;

    private int $firstDataRow = 8;

    private int $lastDataRow = 8;

    private int $totalStartRow = 10;

    public function __construct(
        private readonly ?int $branchId,
        private readonly array $dateRange,
        private readonly ?int $productCategoryId = null,
        private readonly array $targetCategories = [],
        private readonly bool $includeServices = false,
    ) {}

    public function array(): array
    {
        $rows = [
            ['Sales Report'],
            ['Period', $this->periodLabel()],
            ['Branch', $this->branchLabel()],
            ['Module', $this->moduleLabel()],
            ['Generated At', now()->format('M d, Y h:i A')],
            [],
            ['Type', 'Product / Service', 'Code', 'Category', 'Quantity', 'Unit', 'Unit Price (PHP)', 'Gross Sales (PHP)'],
        ];

        $reportRows = $this->reportRows();
        $grossSalesCents = 0;

        $this->firstDataRow = count($rows) + 1;

        foreach ($reportRows as $row) {
            $grossSalesCents += (int) $row->gross_sales;

            $rows[] = [
                $row->row_type,
                $row->item_name,
                $row->item_code ?: '-',
                $row->category_name ?: '-',
                (float) $row->quantity,
                $row->unit_name ?: '-',
                $this->centsToDecimal((int) $row->unit_price),
                $this->centsToDecimal((int) $row->gross_sales),
            ];
        }

        if ($reportRows->isEmpty()) {
            $rows[] = ['No completed sales found for the selected date range.'];
        }

        $this->lastDataRow = count($rows);

        $summary = $this->summaryTotals();
        $discountCents = (int) $summary['discount_cents'];
        $netSalesCents = $grossSalesCents - $discountCents;

        $rows[] = [];
        $this->totalStartRow = count($rows) + 1;
        $rows[] = ['', '', '', '', '', '', 'Gross Sales', $this->centsToDecimal($grossSalesCents)];
        $rows[] = ['', '', '', '', '', '', 'Less Discounts', $this->centsToDecimal($discountCents)];
        $rows[] = ['', '', '', '', '', '', 'Net Sales', $this->centsToDecimal($netSalesCents)];
        $rows[] = ['', '', '', '', '', '', 'Completed Sales', (int) $summary['completed_sales']];

        return $rows;
    }

    public function title(): string
    {
        return 'Sales Report';
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'G' => '"PHP" #,##0.00',
            'H' => '"PHP" #,##0.00',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $this->totalStartRow + 3;

                $sheet->mergeCells('A1:H1');
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle("A{$this->headingRow}:H{$this->headingRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E2E8F0'],
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']],
                    ],
                ]);

                if ($this->lastDataRow >= $this->firstDataRow) {
                    $sheet->getStyle("A{$this->firstDataRow}:H{$this->lastDataRow}")->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']],
                        ],
                    ]);
                }

                $sheet->getStyle("G{$this->totalStartRow}:H{$lastRow}")->applyFromArray([
                    'font' => ['bold' => true],
                    'borders' => [
                        'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']],
                    ],
                ]);

                $sheet->getStyle('E:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $sheet->getStyle('H' . ($this->totalStartRow + 3))->getNumberFormat()->setFormatCode('0');
                $sheet->getStyle('A1:H5')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->freezePane("A{$this->firstDataRow}");
                $sheet->setAutoFilter("A{$this->headingRow}:H{$this->headingRow}");
            },
        ];
    }

    private function reportRows(): Collection
    {
        return $this->productRows()
            ->concat($this->serviceRows())
            ->sortBy([
                ['row_type', 'asc'],
                ['item_name', 'asc'],
                ['unit_price', 'asc'],
            ])
            ->values();
    }

    private function productRows(): Collection
    {
        [$start, $end] = $this->parsedDates();

        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('units', 'sale_items.unit_id', '=', 'units.id')
            ->where('sales.status', Status::Completed->value)
            ->whereBetween('sales.created_at', [$start, $end]);

        $this->applyBranchScope($query, 'sales.branch_id');
        $this->applyProductCategoryScope($query);

        return $query
            ->select([
                DB::raw("'Product' as row_type"),
                'products.id as product_id',
                'products.product_code as item_code',
                'products.name as product_name',
                'products.brand_name',
                'products.generic_name',
                'products.dosage',
                'products.form',
                'product_categories.name as module_name',
                'categories.name as item_category_name',
                DB::raw("COALESCE(units.abbreviation, units.name, 'pcs') as unit_name"),
                'sale_items.price_at_moment as unit_price',
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.subtotal) as gross_sales'),
            ])
            ->groupBy([
                'products.id',
                'products.product_code',
                'products.name',
                'products.brand_name',
                'products.generic_name',
                'products.dosage',
                'products.form',
                'product_categories.name',
                'categories.name',
                'units.abbreviation',
                'units.name',
                'sale_items.price_at_moment',
            ])
            ->get()
            ->map(function (object $row): object {
                $row->item_name = $this->formatProductName($row);
                $row->category_name = trim($this->moduleDisplayName((string) $row->module_name) . ' / ' . ($row->item_category_name ?: 'Uncategorized'), ' /');

                return $row;
            });
    }

    private function serviceRows(): Collection
    {
        if (! $this->includeServices) {
            return collect();
        }

        [$start, $end] = $this->parsedDates();

        $query = DB::table('motor_shop_sale_services')
            ->join('sales', 'motor_shop_sale_services.sale_id', '=', 'sales.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->join('product_categories as branch_modules', 'branches.product_category_id', '=', 'branch_modules.id')
            ->where('sales.status', Status::Completed->value)
            ->whereBetween('sales.created_at', [$start, $end])
            ->where('branch_modules.name', CategoryType::MotorShop->value);

        $this->applyBranchScope($query, 'sales.branch_id');

        if ($this->productCategoryId) {
            $query->where('branches.product_category_id', $this->productCategoryId);
        } elseif (! empty($this->targetCategories)) {
            $query->whereIn('branch_modules.name', $this->targetCategories);
        }

        return $query
            ->select([
                DB::raw("'Service' as row_type"),
                'motor_shop_sale_services.service_name as item_name',
                DB::raw('NULL as item_code'),
                DB::raw("'Motor Shop / Service' as category_name"),
                DB::raw("'service' as unit_name"),
                'motor_shop_sale_services.price_at_moment as unit_price',
                DB::raw('SUM(motor_shop_sale_services.quantity) as quantity'),
                DB::raw('SUM(motor_shop_sale_services.subtotal) as gross_sales'),
            ])
            ->groupBy([
                'motor_shop_sale_services.service_name',
                'motor_shop_sale_services.price_at_moment',
            ])
            ->get();
    }

    private function summaryTotals(): array
    {
        $query = $this->scopedSalesQuery();

        return [
            'completed_sales' => (clone $query)->count(),
            'discount_cents' => (int) (clone $query)->sum('discount_amount'),
        ];
    }

    private function scopedSalesQuery(): Builder
    {
        [$start, $end] = $this->parsedDates();

        $query = Sale::query()
            ->where('status', Status::Completed->value)
            ->whereBetween('created_at', [$start, $end]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        if ($this->hasProductCategoryScope()) {
            $query->where(function (Builder $query): void {
                $query->whereHas('saleItems.product', function (Builder $productQuery): void {
                    if ($this->productCategoryId) {
                        $productQuery->where('product_category_id', $this->productCategoryId);
                    }

                    if (! empty($this->targetCategories)) {
                        $productQuery->whereHas('productCategory', fn (Builder $categoryQuery) => $categoryQuery->whereIn('name', $this->targetCategories));
                    }
                });

                if ($this->includeServices) {
                    $query->orWhereHas('motorShopServices');
                }
            });
        }

        return $query;
    }

    private function applyBranchScope(\Illuminate\Database\Query\Builder $query, string $column): void
    {
        if ($this->branchId) {
            $query->where($column, $this->branchId);
        }
    }

    private function applyProductCategoryScope(\Illuminate\Database\Query\Builder $query): void
    {
        if ($this->productCategoryId) {
            $query->where('products.product_category_id', $this->productCategoryId);
        }

        if (! empty($this->targetCategories)) {
            $query->whereIn('product_categories.name', $this->targetCategories);
        }
    }

    private function hasProductCategoryScope(): bool
    {
        return $this->productCategoryId !== null || ! empty($this->targetCategories);
    }

    private function parsedDates(): array
    {
        $start = Carbon::parse($this->dateRange[0] ?? now()->format('Y-m-d'))->startOfDay();
        $end = Carbon::parse($this->dateRange[1] ?? $this->dateRange[0] ?? now()->format('Y-m-d'))->endOfDay();

        return [$start, $end];
    }

    private function periodLabel(): string
    {
        [$start, $end] = $this->parsedDates();

        return $start->format('M d, Y') . ' to ' . $end->format('M d, Y');
    }

    private function branchLabel(): string
    {
        if (! $this->branchId) {
            return 'All Branches';
        }

        return Branch::find($this->branchId)?->name ?? 'Branch #' . $this->branchId;
    }

    private function moduleLabel(): string
    {
        if ($this->productCategoryId) {
            $category = ProductCategory::find($this->productCategoryId);

            return $category ? $this->moduleDisplayName($category->name) : 'Selected Module';
        }

        if (! empty($this->targetCategories)) {
            return collect($this->targetCategories)
                ->map(fn (string $module): string => $this->moduleDisplayName($module))
                ->implode(', ');
        }

        return 'All Modules';
    }

    private function moduleDisplayName(string $module): string
    {
        return CategoryType::tryFrom($module)?->label() ?? str($module)->headline()->toString();
    }

    private function formatProductName(object $row): string
    {
        $name = $row->brand_name ?: $row->product_name ?: $row->item_code ?: 'Unknown Product';
        $details = collect([$row->generic_name, $row->dosage, $row->form])
            ->filter(fn ($value): bool => filled($value))
            ->implode(' / ');

        return $details ? "{$name} ({$details})" : $name;
    }

    private function centsToDecimal(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
