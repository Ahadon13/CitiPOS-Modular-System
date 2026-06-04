<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Product\CategoryType;
use App\Enums\Sale\Status;
use Illuminate\Support\Facades\DB;

final class SalesFinancials
{
    /**
     * @param  array<int, mixed>|null  $dateRange
     * @return array{
     *     revenue: int,
     *     cogs: int,
     *     gross_profit: int,
     *     expenses: int,
     *     net_profit: int,
     *     gross_margin: float,
     *     net_margin: float,
     *     orders: int
     * }
     */
    public static function calculate(?int $branchId = null, ?int $categoryId = null, ?array $dateRange = null): array
    {
        $revenue = self::revenue($branchId, $categoryId, $dateRange);
        $cogs = self::cogs($branchId, $categoryId, $dateRange);
        $expenses = self::expenses($branchId, $categoryId, $dateRange);

        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $expenses;

        return [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'gross_margin' => $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0.0,
            'net_margin' => $revenue > 0 ? ($netProfit / $revenue) * 100 : 0.0,
            'orders' => self::orders($branchId, $categoryId, $dateRange),
        ];
    }

    /**
     * @param  array<int, mixed>|null  $dateRange
     * @return array<string, int>
     */
    public static function revenueByDate(?int $branchId = null, ?int $categoryId = null, ?array $dateRange = null): array
    {
        if (! $categoryId) {
            return self::completedSales($branchId, $dateRange)
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(grand_total) as total')
                )
                ->groupBy('date')
                ->pluck('total', 'date')
                ->map(fn ($total) => self::toCents($total))
                ->all();
        }

        $allocatedRevenue = self::allocatedCategoryRevenueBySale($branchId, $categoryId, $dateRange);

        if (empty($allocatedRevenue)) {
            return [];
        }

        $revenueByDate = [];

        self::completedSales($branchId, $dateRange)
            ->whereIn('id', array_keys($allocatedRevenue))
            ->select('id', 'created_at')
            ->get()
            ->each(function ($sale) use (&$revenueByDate, $allocatedRevenue): void {
                $date = date('Y-m-d', strtotime((string) $sale->created_at));
                $revenueByDate[$date] = ($revenueByDate[$date] ?? 0) + $allocatedRevenue[$sale->id];
            });

        return $revenueByDate;
    }

    private static function revenue(?int $branchId, ?int $categoryId, ?array $dateRange): int
    {
        if (! $categoryId) {
            return self::toCents(self::completedSales($branchId, $dateRange)->sum('grand_total'));
        }

        return array_sum(self::allocatedCategoryRevenueBySale($branchId, $categoryId, $dateRange));
    }

    private static function cogs(?int $branchId, ?int $categoryId, ?array $dateRange): int
    {
        return self::toCents(
            self::productSaleItems($branchId, $categoryId, $dateRange)
                ->sum(DB::raw('sale_items.cost_at_moment * sale_items.quantity'))
        );
    }

    private static function expenses(?int $branchId, ?int $categoryId, ?array $dateRange): int
    {
        $query = DB::table('expenses');

        self::applyDateRange($query, 'expense_date', $dateRange);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } elseif ($categoryId) {
            $query->whereIn('branch_id', function ($subQuery) use ($categoryId) {
                $subQuery->select('id')
                    ->from('branches')
                    ->where('product_category_id', $categoryId);
            });
        }

        return self::toCents($query->sum('amount'));
    }

    private static function orders(?int $branchId, ?int $categoryId, ?array $dateRange): int
    {
        $query = self::completedSales($branchId, $dateRange);

        if ($categoryId) {
            $query->where(function ($salesQuery) use ($categoryId) {
                $salesQuery->whereExists(function ($subQuery) use ($categoryId) {
                    $subQuery->selectRaw('1')
                        ->from('sale_items')
                        ->join('products', 'sale_items.product_id', '=', 'products.id')
                        ->whereColumn('sale_items.sale_id', 'sales.id')
                        ->where('products.product_category_id', $categoryId);
                });

                if (self::isMotorShopCategory($categoryId)) {
                    $salesQuery->orWhereExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('motor_shop_sale_services')
                            ->whereColumn('motor_shop_sale_services.sale_id', 'sales.id');
                    });
                }
            });
        }

        return (int) $query->count();
    }

    private static function completedSales(?int $branchId, ?array $dateRange)
    {
        $query = DB::table('sales')
            ->where('status', Status::Completed->value);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        self::applyDateRange($query, 'created_at', $dateRange);

        return $query;
    }

    private static function productSaleItems(?int $branchId, ?int $categoryId, ?array $dateRange)
    {
        $query = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where('sales.status', Status::Completed->value);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        }

        if ($categoryId) {
            $query->where('products.product_category_id', $categoryId);
        }

        self::applyDateRange($query, 'sales.created_at', $dateRange);

        return $query;
    }

    private static function motorShopServices(?int $branchId, ?array $dateRange)
    {
        $query = DB::table('motor_shop_sale_services')
            ->join('sales', 'motor_shop_sale_services.sale_id', '=', 'sales.id')
            ->where('sales.status', Status::Completed->value);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        }

        self::applyDateRange($query, 'sales.created_at', $dateRange);

        return $query;
    }

    /**
     * Allocates a sale's paid grand total to the selected module by the selected
     * lines' share of the full pre-discount sale subtotal.
     *
     * @param  array<int, mixed>|null  $dateRange
     * @return array<int, int>
     */
    private static function allocatedCategoryRevenueBySale(?int $branchId, int $categoryId, ?array $dateRange): array
    {
        $selectedTotals = self::productSaleItems($branchId, $categoryId, $dateRange)
            ->select('sale_items.sale_id', DB::raw('SUM(sale_items.subtotal) as total'))
            ->groupBy('sale_items.sale_id')
            ->pluck('total', 'sale_id')
            ->map(fn ($total) => self::toCents($total))
            ->all();

        if (self::isMotorShopCategory($categoryId)) {
            self::motorShopServices($branchId, $dateRange)
                ->select('motor_shop_sale_services.sale_id', DB::raw('SUM(motor_shop_sale_services.subtotal) as total'))
                ->groupBy('motor_shop_sale_services.sale_id')
                ->pluck('total', 'sale_id')
                ->each(function ($total, $saleId) use (&$selectedTotals): void {
                    $selectedTotals[$saleId] = ($selectedTotals[$saleId] ?? 0) + self::toCents($total);
                });
        }

        $saleIds = array_keys(array_filter($selectedTotals, fn (int $total): bool => $total > 0));

        if (empty($saleIds)) {
            return [];
        }

        $productTotals = DB::table('sale_items')
            ->whereIn('sale_id', $saleIds)
            ->select('sale_id', DB::raw('SUM(subtotal) as total'))
            ->groupBy('sale_id')
            ->pluck('total', 'sale_id')
            ->map(fn ($total) => self::toCents($total))
            ->all();

        $serviceTotals = DB::table('motor_shop_sale_services')
            ->whereIn('sale_id', $saleIds)
            ->select('sale_id', DB::raw('SUM(subtotal) as total'))
            ->groupBy('sale_id')
            ->pluck('total', 'sale_id')
            ->map(fn ($total) => self::toCents($total))
            ->all();

        $allocatedRevenue = [];

        self::completedSales($branchId, $dateRange)
            ->whereIn('id', $saleIds)
            ->select('id', 'grand_total')
            ->get()
            ->each(function ($sale) use (&$allocatedRevenue, $productTotals, $selectedTotals, $serviceTotals): void {
                $saleId = (int) $sale->id;
                $saleSubtotal = ($productTotals[$saleId] ?? 0) + ($serviceTotals[$saleId] ?? 0);

                if ($saleSubtotal <= 0) {
                    return;
                }

                $selectedSubtotal = min($selectedTotals[$saleId] ?? 0, $saleSubtotal);
                $allocatedRevenue[$saleId] = (int) round(self::toCents($sale->grand_total) * ($selectedSubtotal / $saleSubtotal));
            });

        return $allocatedRevenue;
    }

    private static function isMotorShopCategory(int $categoryId): bool
    {
        return DB::table('product_categories')
            ->where('id', $categoryId)
            ->value('name') === CategoryType::MotorShop->value;
    }

    private static function applyDateRange($query, string $column, ?array $dateRange): void
    {
        if (! empty($dateRange) && count($dateRange) >= 2) {
            $query->whereBetween($column, [$dateRange[0], $dateRange[1]]);
        }
    }

    private static function toCents(mixed $value): int
    {
        return (int) round((float) ($value ?? 0));
    }
}
