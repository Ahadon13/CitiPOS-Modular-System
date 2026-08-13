<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Sale\Status;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Cross-cutting sales breakdowns for auditing: who sold it, where, how it was
 * paid for, and which sales never completed.
 *
 * Every method here shares the same filter object as the rest of the reports
 * page so the tabs can never drift out of agreement with one another.
 */
final class SalesAudit
{
    /**
     * Completed revenue per branch, bucketed for the trend chart.
     *
     * @return array{labels: list<string>, series: list<array{name: string, data: list<float>}>, granularity: string, has_other: bool}
     */
    public static function branchTrend(PartnershipSalesFilters $filters): array
    {
        $rows = self::completedSales($filters)
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->selectRaw('branches.name as label')
            ->selectRaw('DATE(sales.created_at) as date')
            ->selectRaw('SUM(sales.grand_total) as total')
            ->groupBy('label', 'date')
            ->get();

        return self::buildTrend($rows, $filters, 'Other branches');
    }

    /**
     * Per-branch totals for the comparison table.
     */
    public static function byBranch(PartnershipSalesFilters $filters): Builder
    {
        return self::completedSales($filters)
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->leftJoin('product_categories', 'branches.product_category_id', '=', 'product_categories.id')
            ->select(['branches.id', 'branches.name as branch_name', 'product_categories.name as module'])
            ->selectRaw('COUNT(sales.id) as orders')
            ->selectRaw('COALESCE(SUM(sales.grand_total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sales.discount_amount), 0) as discounts')
            ->groupBy('branches.id', 'branches.name', 'product_categories.name')
            ->orderByDesc('revenue');
    }

    /**
     * Per-cashier totals -- the accountability view during an audit.
     */
    public static function byCashier(PartnershipSalesFilters $filters): Builder
    {
        return self::completedSales($filters)
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->join('branches', 'sales.branch_id', '=', 'branches.id')
            ->select(['users.id', 'users.name as cashier_name', 'branches.name as branch_name'])
            ->selectRaw('COUNT(sales.id) as orders')
            ->selectRaw('COALESCE(SUM(sales.grand_total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sales.discount_amount), 0) as discounts')
            ->selectRaw('COALESCE(AVG(sales.grand_total), 0) as average_order')
            ->groupBy('users.id', 'users.name', 'branches.name')
            ->orderByDesc('revenue');
    }

    /**
     * Payment method mix -- the figures a cash drawer is reconciled against.
     *
     * @return array{labels: list<string>, series: list<float>, rows: list<array{label: string, orders: int, total: int}>}
     */
    public static function paymentMix(PartnershipSalesFilters $filters): array
    {
        $rows = self::completedSales($filters)
            ->leftJoin('payment_methods', 'sales.payment_method_id', '=', 'payment_methods.id')
            ->selectRaw("COALESCE(payment_methods.name, 'Unspecified') as label")
            ->selectRaw('COUNT(sales.id) as orders')
            ->selectRaw('COALESCE(SUM(sales.grand_total), 0) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('label')->map(fn ($v) => (string) $v)->all(),
            'series' => $rows->map(fn ($r) => round((float) $r->total / 100, 2))->all(),
            'rows' => $rows->map(fn ($r) => [
                'label' => (string) $r->label,
                'orders' => (int) $r->orders,
                'total' => (int) round((float) $r->total),
            ])->all(),
        ];
    }

    /**
     * Sales that never reached completion -- pending special orders, voids.
     *
     * @return list<array{status: string, orders: int, total: int}>
     */
    public static function statusBreakdown(PartnershipSalesFilters $filters): array
    {
        $query = DB::table('sales')
            ->whereBetween('sales.created_at', self::window($filters));

        if ($filters->branchId) {
            $query->where('sales.branch_id', $filters->branchId);
        }

        return $query->selectRaw('sales.status')
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(sales.grand_total), 0) as total')
            ->groupBy('sales.status')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'orders' => (int) $row->orders,
                'total' => (int) round((float) $row->total),
            ])
            ->all();
    }

    /**
     * Best sellers by revenue, for the products tab.
     */
    public static function topProducts(PartnershipSalesFilters $filters, int $limit = 25): Builder
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->leftJoin('product_categories', 'products.product_category_id', '=', 'product_categories.id')
            ->where('sales.status', Status::Completed->value)
            ->whereBetween('sales.created_at', self::window($filters));

        if ($filters->branchId) {
            $query->where('sales.branch_id', $filters->branchId);
        }

        if ($filters->categoryId) {
            $query->where('products.product_category_id', $filters->categoryId);
        }

        return $query
            ->select([
                'products.id',
                'products.name as product_name',
                'products.brand_name',
                'products.product_code',
                'product_categories.name as module',
            ])
            ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as quantity')
            ->selectRaw('COALESCE(SUM(sale_items.subtotal), 0) as revenue')
            ->selectRaw('COALESCE(SUM(sale_items.cost_at_moment * sale_items.quantity), 0) as cogs')
            ->groupBy('products.id', 'products.name', 'products.brand_name', 'products.product_code', 'product_categories.name')
            ->orderByDesc('revenue')
            ->limit($limit);
    }

    /**
     * Shared bucketing for the multi-series trend charts.
     *
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return array{labels: list<string>, series: list<array{name: string, data: list<float>}>, granularity: string, has_other: bool}
     */
    private static function buildTrend($rows, PartnershipSalesFilters $filters, string $otherLabel): array
    {
        $bucket = DateBucket::resolve($filters->start, $filters->end);
        $labels = $bucket->buckets();

        $totals = [];

        foreach ($rows as $row) {
            $label = (string) ($row->label ?? 'Unknown');
            $totals[$label] = ($totals[$label] ?? 0) + (float) $row->total;
        }

        arsort($totals);
        $named = array_slice(array_keys($totals), 0, ChartPalette::MAX_SERIES);
        $hasOther = count($totals) > count($named);

        $series = [];

        foreach ($named as $name) {
            $series[$name] = array_fill_keys(array_keys($labels), 0.0);
        }

        if ($hasOther) {
            $series[$otherLabel] = array_fill_keys(array_keys($labels), 0.0);
        }

        foreach ($rows as $row) {
            $label = (string) ($row->label ?? 'Unknown');
            $target = in_array($label, $named, true) ? $label : $otherLabel;

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
                    'data' => array_map(fn (float $v) => round($v, 2), array_values($series[$name])),
                ],
                array_keys($series)
            ),
            'granularity' => $bucket->describe(),
            'has_other' => $hasOther,
        ];
    }

    private static function completedSales(PartnershipSalesFilters $filters): Builder
    {
        $query = DB::table('sales')
            ->where('sales.status', Status::Completed->value)
            ->whereBetween('sales.created_at', self::window($filters));

        if ($filters->branchId) {
            $query->where('sales.branch_id', $filters->branchId);
        }

        if ($filters->categoryId) {
            $query->whereIn('sales.branch_id', function ($sub) use ($filters) {
                $sub->select('id')
                    ->from('branches')
                    ->where('product_category_id', $filters->categoryId);
            });
        }

        return $query;
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private static function window(PartnershipSalesFilters $filters): array
    {
        return [
            CarbonImmutable::parse($filters->start)->startOfDay(),
            CarbonImmutable::parse($filters->end)->endOfDay(),
        ];
    }
}
