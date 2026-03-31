<?php

namespace App\Livewire\Admin\Pages;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\ProductCategory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Traits\HasAuth;
use App\Traits\HasDataTable;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Money\Money;

#[Layout('components.layouts.admin', ['title' => 'Dashboard'])]
class Dashboard extends Component
{
    use HasAuth, HasDataTable, WithPagination;

    // Filters
    public ?int $branchId = null;   // Null = All Branches
    public ?int $categoryId = null; // Null = All Modules (Pharmacy, Grocery, etc.)
    public ?array $view_purchase = null;
    public string $dateRange = 'today';

    #[Computed]
    public function branches()
    {
        return Branch::orderBy('name')->get();
    }
    #[Computed]
    public function paginatedBranches()
    {
        return Branch::orderBy('name', 'asc')
            ->paginate(5, ['*'], 'branches_page'); // Unique pagination name
    }

    #[Computed]
    public function paginatedPurchases()
    {
        [$startDate, $endDate] = $this->getDateRange();

        return Purchase::with(['branch', 'supplier', 'user', 'purchaseItems.product', 'purchaseItems.unit']) // Load branch relationship
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->latest() // Order by latest
            ->paginate(5, ['*'], 'po_page'); // Unique pagination name
    }

    #[Computed]
    public function categories()
    {
        return ProductCategory::orderBy('name')->get();
    }

    /**
     * Get the date constraint based on the selected range.
     */
    protected function getDateRange(): array
    {
        return match ($this->dateRange) {
            'today' => [Carbon::today(), Carbon::now()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()->endOfDay()],
            '7days' => [Carbon::now()->subDays(7)->startOfDay(), Carbon::now()],
            '30days' => [Carbon::now()->subDays(30)->startOfDay(), Carbon::now()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            default => [Carbon::today(), Carbon::now()],
        };
    }

    #[Computed]
    public function stats()
    {
        [$startDate, $endDate] = $this->getDateRange();

        // 1. Revenue, Cost, & Orders Calculation
        if ($this->categoryId) {
            $salesData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->where('products.product_category_id', $this->categoryId)
                ->when($this->branchId, fn($q) => $q->where('sales.branch_id', $this->branchId))
                ->select(
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    // Multiply cost by quantity to get the total cost of goods sold
                    DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cost'),
                    DB::raw('COUNT(DISTINCT sales.id) as total_orders')
                )->first();
        } else {
            // Global (We join sale_items here as well to accurately get the historical cost)
            $salesData = SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->whereBetween('sales.created_at', [$startDate, $endDate])
                ->when($this->branchId, fn($q) => $q->where('sales.branch_id', $this->branchId))
                ->select(
                    DB::raw('SUM(sale_items.subtotal) as total_revenue'),
                    DB::raw('SUM(sale_items.cost_at_moment * sale_items.quantity) as total_cost'),
                    DB::raw('COUNT(DISTINCT sales.id) as total_orders')
                )->first();
        }
        // Extract the values
        $revenue = $salesData->total_revenue ?? 0;
        $cost = $salesData->total_cost ?? 0;

        // Calculate Profit & Margin
        $grossProfit = $revenue - $cost;
        $marginPercentage = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;

        // 2. Total Products Count
        $productsCount = Product::when($this->categoryId, fn($q) => $q->where('product_category_id', $this->categoryId))
            ->count();

        // 3. Low Stock Items
        $lowStockCount = Product::when($this->categoryId, fn($q) => $q->where('product_category_id', $this->categoryId))
            ->whereHas('inventoryBatches', function($q) {
                $q->when($this->branchId, fn($sub) => $sub->where('branch_id', $this->branchId))
                  ->select('product_id')
                  ->groupBy('product_id')
                  ->havingRaw('SUM(quantity_on_hand) <= 10');
            })->count();

        return [
            'revenue' => Money::PHP((string) round((float) $revenue )),
            'gross_profit' => Money::PHP((string) round((float) $grossProfit )),
            'margin' => $marginPercentage,
            'orders' => $salesData->total_orders ?? 0,
            'products' => $productsCount,
            'low_stock' => $lowStockCount,
        ];
    }

    #[Computed]
    public function recentTransactions()
    {
        // For recent transactions, if a category is selected, we only show sales that include items from that category
        return Sale::with(['customer', 'paymentMethod', 'branch'])
            ->when($this->branchId, fn($q) => $q->where('branch_id', $this->branchId))
            ->when($this->categoryId, function($q) {
                $q->whereHas('saleItems.product', function($subQ) {
                    $subQ->where('product_category_id', $this->categoryId);
                });
            })
            ->latest()
            ->take(6)
            ->get();
    }

    #[Computed]
    public function topSellingProducts()
    {
        [$startDate, $endDate] = $this->getDateRange();

        return SaleItem::join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereBetween('sales.created_at', [$startDate, $endDate])
            ->when($this->branchId, fn($q) => $q->where('sales.branch_id', $this->branchId))
            ->when($this->categoryId, fn($q) => $q->where('products.product_category_id', $this->categoryId))
            ->select(
                'products.brand_name',
                'categories.name as category_name',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.subtotal) as total_revenue')
            )
            ->groupBy('products.id', 'products.brand_name', 'categories.name')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();
    }

    protected function getAdditionalPageResetProperties(): array
    {
        return ['branchId', 'categoryId', 'dateRange'];
    }
}
