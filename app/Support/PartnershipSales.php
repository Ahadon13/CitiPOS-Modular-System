<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Sale\Status;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Reporting over sales that were rung up at a partnership price.
 *
 * No schema work is needed for any of this: the POS already stamps every sale
 * line with `price_source`, `partnership_id` and `regular_price_at_moment`
 * alongside the price actually charged, so the discount a partner received is
 * recoverable per line, forever, even if the partnership price later changes.
 *
 * "Partnership" is grouped by customer type (LGU, DSWD, ...) rather than by
 * individual partnership row -- a row is per product packaging, so grouping on
 * it would put hundreds of lines on a chart instead of one per partner.
 */
final class PartnershipSales
{
    public const PRICE_SOURCE = 'partnership';

    /**
     * Every partnership-priced sale line, with the regular price it was
     * discounted from.
     */
    public static function itemsQuery(PartnershipSalesFilters $filters): Builder
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->leftJoin('partnerships', 'sale_items.partnership_id', '=', 'partnerships.id')
            ->leftJoin('customer_types', 'partnerships.customer_type_id', '=', 'customer_types.id')
            ->leftJoin('customers', 'sales.customer_id', '=', 'customers.id')
            ->leftJoin('product_packagings', 'sale_items.product_packaging_id', '=', 'product_packagings.id')
            ->leftJoin('units', 'sale_items.unit_id', '=', 'units.id')
            ->where('sales.status', Status::Completed->value)
            ->where('sale_items.price_source', self::PRICE_SOURCE)
            ->whereNotNull('sale_items.partnership_id');

        return self::applyFilters($query, $filters);
    }

    /**
     * Table rows for the report, newest first.
     */
    public static function items(PartnershipSalesFilters $filters): Builder
    {
        return self::itemsQuery($filters)
            ->select([
                'sale_items.id',
                'sales.id as sale_id',
                'sales.created_at as sold_at',
                'branches.name as branch_name',
                'customers.name as customer_name',
                'customer_types.name as partner_name',
                'products.name as product_name',
                'products.brand_name',
                'products.generic_name',
                'products.product_code',
                'units.abbreviation as unit_abbreviation',
                'units.name as unit_name',
                'sale_items.quantity',
                'sale_items.regular_price_at_moment',
                'sale_items.price_at_moment',
                'sale_items.subtotal',
                DB::raw('(COALESCE(sale_items.regular_price_at_moment, sale_items.price_at_moment) - sale_items.price_at_moment) * sale_items.quantity as partner_savings'),
                DB::raw('COALESCE(sale_items.regular_price_at_moment, sale_items.price_at_moment) * sale_items.quantity as regular_value'),
            ])
            ->orderByDesc('sales.created_at')
            ->orderByDesc('sale_items.id');
    }

    /**
     * Headline figures for the partnership tab.
     *
     * @return array{lines: int, quantity: float, revenue: int, regular_value: int, savings: int, partners: int}
     */
    public static function summary(PartnershipSalesFilters $filters): array
    {
        $row = self::itemsQuery($filters)
            // Not aliased "lines": LINES is a reserved word in MySQL.
            ->selectRaw('COUNT(*) as line_count')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(COALESCE(sale_items.regular_price_at_moment, sale_items.price_at_moment) * sale_items.quantity), 0) as regular_value')
            ->selectRaw('COUNT(DISTINCT partnerships.customer_type_id) as partners')
            ->first();

        $revenue = (int) round((float) ($row->revenue ?? 0));
        $regularValue = (int) round((float) ($row->regular_value ?? 0));

        return [
            'lines' => (int) ($row->line_count ?? 0),
            'quantity' => (float) ($row->quantity ?? 0),
            'revenue' => $revenue,
            'regular_value' => $regularValue,
            'savings' => max(0, $regularValue - $revenue),
            'partners' => (int) ($row->partners ?? 0),
        ];
    }

    /**
     * One line per partner for the trend chart, bucketed so a decade-wide
     * range stays plottable.
     *
     * Partners past ChartPalette::MAX_SERIES are folded into a single "Other
     * partners" series rather than given a generated colour.
     *
     * @return array{labels: list<string>, series: list<array{name: string, data: list<float>}>, granularity: string}
     */
    public static function trend(PartnershipSalesFilters $filters): array
    {
        $bucket = DateBucket::resolve($filters->start, $filters->end);
        $labels = $bucket->buckets();

        $rows = self::itemsQuery($filters)
            ->selectRaw('customer_types.name as partner_name')
            ->selectRaw('DATE(sales.created_at) as date')
            ->selectRaw('SUM(sale_items.subtotal) as total')
            ->groupBy('partner_name', 'date')
            ->get();

        // Rank partners by total spend so the biggest keep their own line.
        $totals = [];

        foreach ($rows as $row) {
            $name = (string) ($row->partner_name ?? 'Unknown partner');
            $totals[$name] = ($totals[$name] ?? 0) + (float) $row->total;
        }

        arsort($totals);
        $named = array_slice(array_keys($totals), 0, ChartPalette::MAX_SERIES);
        $hasOther = count($totals) > count($named);

        $series = [];

        foreach ($named as $name) {
            $series[$name] = array_fill_keys(array_keys($labels), 0.0);
        }

        if ($hasOther) {
            $series['Other partners'] = array_fill_keys(array_keys($labels), 0.0);
        }

        foreach ($rows as $row) {
            $name = (string) ($row->partner_name ?? 'Unknown partner');
            $target = in_array($name, $named, true) ? $name : 'Other partners';

            if (! isset($series[$target])) {
                continue;
            }

            $key = $bucket->keyFor((string) $row->date);

            if (! array_key_exists($key, $series[$target])) {
                continue;
            }

            $series[$target][$key] += (float) $row->total / 100;
        }

        return [
            'labels' => array_values($labels),
            'series' => array_map(
                fn (string $name) => [
                    'name' => $name,
                    'data' => array_map(
                        fn (float $value) => round($value, 2),
                        array_values($series[$name])
                    ),
                ],
                array_keys($series)
            ),
            'granularity' => $bucket->describe(),
            'has_other' => $hasOther,
        ];
    }

    /**
     * Per-partner totals for the breakdown table and bar chart.
     */
    public static function byPartner(PartnershipSalesFilters $filters): Builder
    {
        return self::itemsQuery($filters)
            ->select([
                'customer_types.id as customer_type_id',
                'customer_types.name as partner_name',
            ])
            ->selectRaw('COUNT(DISTINCT sales.id) as orders')
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM((COALESCE(sale_items.regular_price_at_moment, sale_items.price_at_moment) - sale_items.price_at_moment) * sale_items.quantity), 0) as savings')
            ->groupBy('customer_types.id', 'customer_types.name')
            ->orderByDesc('revenue');
    }

    /**
     * Partnership vs regular pricing mix -- how much of the business is being
     * sold at a discounted partner price.
     *
     * @return array{partnership: int, regular: int}
     */
    public static function priceSourceMix(PartnershipSalesFilters $filters): array
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->where('sales.status', Status::Completed->value);

        $rows = self::applyFilters($query, $filters, includePartnerFilter: false)
            ->selectRaw('sale_items.price_source')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as total')
            ->groupBy('sale_items.price_source')
            ->pluck('total', 'price_source');

        return [
            'partnership' => (int) round((float) ($rows[self::PRICE_SOURCE] ?? 0)),
            'regular' => (int) round((float) ($rows['regular'] ?? 0)),
        ];
    }

    private static function applyFilters(Builder $query, PartnershipSalesFilters $filters, bool $includePartnerFilter = true): Builder
    {
        $query->whereBetween('sales.created_at', [
            CarbonImmutable::parse($filters->start)->startOfDay(),
            CarbonImmutable::parse($filters->end)->endOfDay(),
        ]);

        if ($filters->branchId) {
            $query->where('sales.branch_id', $filters->branchId);
        }

        if ($filters->categoryId) {
            $query->where('products.product_category_id', $filters->categoryId);
        }

        if ($includePartnerFilter && $filters->customerTypeId) {
            $query->where('partnerships.customer_type_id', $filters->customerTypeId);
        }

        if ($filters->search !== '') {
            $term = '%'.mb_trim($filters->search).'%';

            $query->where(function (Builder $sub) use ($term) {
                $sub->where('products.name', 'like', $term)
                    ->orWhere('products.brand_name', 'like', $term)
                    ->orWhere('products.generic_name', 'like', $term)
                    ->orWhere('products.product_code', 'like', $term)
                    ->orWhere('customers.name', 'like', $term);
            });
        }

        return $query;
    }
}
